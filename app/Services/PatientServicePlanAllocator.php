<?php

namespace App\Services;

use App\Models\InvoicePayment;
use App\Models\PatientServicePlan;
use App\Models\PatientServicePlanAllocation;
use App\Models\PatientServicePlanPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PatientServicePlanAllocator
{
    public function allocate(PatientServicePlan $plan, InvoicePayment $invoicePayment, ?int $actorId = null): PatientServicePlanPayment
    {
        return DB::transaction(function () use ($plan, $invoicePayment, $actorId) {
            $lockedPlan = PatientServicePlan::query()->lockForUpdate()->findOrFail($plan->id);
            $lockedPayment = InvoicePayment::query()->with('invoice')->lockForUpdate()->findOrFail($invoicePayment->id);

            if ($lockedPlan->status !== PatientServicePlan::STATUS_ACTIVE) {
                throw ValidationException::withMessages([
                    'invoice_payment_id' => 'لا يمكن تخصيص دفعات إلا لخطة نشطة.',
                ]);
            }

            if ($lockedPayment->invoice->patient_id !== $lockedPlan->patient_id) {
                throw ValidationException::withMessages([
                    'invoice_payment_id' => 'الدفعة المختارة لا تخص هذا المريض.',
                ]);
            }

            if (PatientServicePlanPayment::query()->where('invoice_payment_id', $lockedPayment->id)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages([
                    'invoice_payment_id' => 'تم تخصيص هذه الدفعة من قبل.',
                ]);
            }

            $planPayment = PatientServicePlanPayment::create([
                'patient_service_plan_id' => $lockedPlan->id,
                'invoice_payment_id' => $lockedPayment->id,
                'amount_snapshot' => $lockedPayment->amount,
                'created_by' => $actorId,
            ]);

            $paidCents = PatientServicePlanPayment::query()
                ->where('patient_service_plan_id', $lockedPlan->id)
                ->lockForUpdate()
                ->pluck('amount_snapshot')
                ->sum(fn ($amount) => self::decimalToCents((string) $amount));
            $allocatedCents = PatientServicePlanAllocation::query()
                ->whereHas('planPayment', fn ($query) => $query->where('patient_service_plan_id', $lockedPlan->id))
                ->lockForUpdate()
                ->pluck('allocated_amount')
                ->sum(fn ($amount) => self::decimalToCents((string) $amount));
            $availableCents = $paidCents - $allocatedCents;

            if ($availableCents < 0) {
                throw ValidationException::withMessages([
                    'invoice_payment_id' => 'تعذر تخصيص الدفعة بسبب عدم اتساق الرصيد المالي.',
                ]);
            }

            $items = $lockedPlan->items()->lockForUpdate()->get();

            foreach ($items as $item) {
                $remainingQuantity = $item->planned_quantity - $item->authorized_quantity;
                $unitPriceCents = self::decimalToCents($item->final_unit_price);

                if ($remainingQuantity <= 0) {
                    continue;
                }

                if ($availableCents < $unitPriceCents) {
                    break;
                }

                $quantity = min($remainingQuantity, intdiv($availableCents, $unitPriceCents));
                $amountCents = $quantity * $unitPriceCents;

                $item->increment('authorized_quantity', $quantity);
                PatientServicePlanAllocation::create([
                    'patient_service_plan_payment_id' => $planPayment->id,
                    'patient_service_plan_item_id' => $item->id,
                    'allocated_amount' => self::centsToDecimal($amountCents),
                    'authorized_quantity' => $quantity,
                ]);
                $availableCents -= $amountCents;
            }

            return $planPayment->load('allocations.item');
        });
    }

    public static function decimalToCents(string|int $amount): int
    {
        $normalized = trim((string) $amount);

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $normalized)) {
            throw new \InvalidArgumentException('Money values must have at most two decimal places.');
        }

        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    public static function centsToDecimal(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }
}
