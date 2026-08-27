<?php

namespace App\Services;

use App\Models\PatientServicePlan;
use App\Models\Service;
use Illuminate\Validation\ValidationException;

class PatientServicePlanPricingService
{
    public function prepareNewItems(array $items, bool $canManageDiscounts): array
    {
        $services = $this->lockedServicesFor($items);

        return collect($items)
            ->sortBy('position')
            ->values()
            ->map(function (array $item, int $index) use ($services, $canManageDiscounts) {
                $priceCents = $this->officialPriceCents($services->get($item['service_id']), $index);
                $discountCents = PatientServicePlanAllocator::decimalToCents(
                    (string) ($item['discount_amount'] ?? '0')
                );

                abort_if($discountCents > 0 && ! $canManageDiscounts, 403);
                $this->validateDiscount($discountCents, $priceCents, $index);

                return $this->preparedItem($item, $index + 1, $priceCents, $discountCents);
            })
            ->all();
    }

    public function prepareExistingItems(
        PatientServicePlan $plan,
        array $items,
        bool $canManageDiscounts
    ): array {
        $existingItems = $plan->items()->get()->keyBy('id');
        $services = $this->lockedServicesFor($items);

        return collect($items)
            ->sortBy('position')
            ->values()
            ->map(function (array $item, int $index) use ($existingItems, $services, $canManageDiscounts) {
                $existingItem = isset($item['id']) ? $existingItems->get($item['id']) : null;

                if (isset($item['id']) && ! $existingItem) {
                    throw ValidationException::withMessages([
                        "items.{$index}.id" => 'بند الخطة المحدد غير صالح.',
                    ]);
                }

                $priceCents = $existingItem && (int) $existingItem->service_id === (int) $item['service_id']
                    ? PatientServicePlanAllocator::decimalToCents($existingItem->customer_unit_price)
                    : $this->officialPriceCents($services->get($item['service_id']), $index);
                $existingDiscount = $existingItem
                    ? PatientServicePlanAllocator::decimalToCents($existingItem->discount_amount)
                    : 0;
                $hasSubmittedDiscount = array_key_exists('discount_amount', $item);
                $submittedDiscount = $hasSubmittedDiscount
                    ? PatientServicePlanAllocator::decimalToCents((string) ($item['discount_amount'] ?? '0'))
                    : $existingDiscount;

                if (! $canManageDiscounts && $hasSubmittedDiscount && $submittedDiscount !== $existingDiscount) {
                    throw ValidationException::withMessages([
                        "items.{$index}.discount_amount" => 'ليس لديك صلاحية تعديل خصم العميل.',
                    ]);
                }

                $discountCents = $canManageDiscounts ? $submittedDiscount : $existingDiscount;
                $this->validateDiscount($discountCents, $priceCents, $index);

                return $this->preparedItem($item, $index + 1, $priceCents, $discountCents);
            })
            ->all();
    }

    private function lockedServicesFor(array $items)
    {
        return Service::query()
            ->whereIn('id', collect($items)->pluck('service_id')->filter()->unique())
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    private function officialPriceCents(?Service $service, int $index): int
    {
        $priceCents = $service?->customer_price === null
            ? 0
            : PatientServicePlanAllocator::decimalToCents($service->customer_price);

        if ($priceCents <= 0) {
            throw ValidationException::withMessages([
                "items.{$index}.service_id" => 'هذه الخدمة لا تحتوي على سعر عميل معتمد. يرجى تحديد السعر من إعدادات الخدمات أولًا.',
            ]);
        }

        return $priceCents;
    }

    private function validateDiscount(int $discountCents, int $priceCents, int $index): void
    {
        if ($discountCents >= $priceCents) {
            throw ValidationException::withMessages([
                "items.{$index}.discount_amount" => 'يجب أن يكون الخصم أقل من سعر الوحدة.',
            ]);
        }
    }

    private function preparedItem(array $item, int $position, int $priceCents, int $discountCents): array
    {
        return [
            'service_id' => $item['service_id'],
            'position' => $position,
            'planned_quantity' => $item['planned_quantity'],
            'customer_unit_price' => PatientServicePlanAllocator::centsToDecimal($priceCents),
            'discount_amount' => PatientServicePlanAllocator::centsToDecimal($discountCents),
            'final_unit_price' => PatientServicePlanAllocator::centsToDecimal($priceCents - $discountCents),
        ];
    }
}
