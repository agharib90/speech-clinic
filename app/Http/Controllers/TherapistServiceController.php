<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Specialty;
use App\Models\Therapist;
use App\Models\TherapistServiceRate;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TherapistServiceController extends Controller
{
    public function edit(Therapist $therapist)
    {
        $therapist->load([
            'specialties',
            'services',
            'serviceRates.service.specialty',
            'serviceRates.creator',
        ]);

        $specialties = Specialty::query()
            ->where('is_active', true)
            ->with(['services' => fn ($query) => $query->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get();

        $currentRates = $therapist->services->mapWithKeys(function (Service $service) use ($therapist) {
            return [$service->id => TherapistServiceRate::resolveFor($therapist, $service, today())];
        });

        return view('hr.therapists.services', compact('therapist', 'specialties', 'currentRates'));
    }

    public function update(Request $request, Therapist $therapist)
    {
        $data = $request->validate([
            'specialty_ids' => ['nullable', 'array'],
            'specialty_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('specialties', 'id')->where('is_active', true)->whereNull('deleted_at'),
            ],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('services', 'id')->where('is_active', true)->whereNull('deleted_at'),
            ],
        ]);

        $specialtyIds = collect($data['specialty_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $serviceIds = collect($data['service_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $services = Service::query()->with('specialty')->whereKey($serviceIds)->get();

        $outsideSpecialties = $services->reject(
            fn (Service $service) => $specialtyIds->contains($service->specialty_id)
        );

        if ($outsideSpecialties->isNotEmpty()) {
            throw ValidationException::withMessages([
                'service_ids' => 'لا يمكن إسناد خدمة لا تنتمي إلى تخصصات الأخصائي المحددة.',
            ]);
        }

        $rateRules = [];
        foreach ($serviceIds as $serviceId) {
            $rateRules["rates.{$serviceId}.amount"] = ['required', 'numeric', 'gt:0', 'max:99999999.99'];
            $rateRules["rates.{$serviceId}.effective_from"] = ['required', 'date'];
        }
        $request->validate($rateRules);

        foreach ($services as $service) {
            $rate = $request->input("rates.{$service->id}");
            $effectiveFrom = CarbonImmutable::parse($rate['effective_from'])->toDateString();
            $existingRate = TherapistServiceRate::query()
                ->where('therapist_id', $therapist->id)
                ->where('service_id', $service->id)
                ->whereDate('effective_from', $effectiveFrom)
                ->first();

            if ($existingRate && (float) $existingRate->amount !== (float) $rate['amount']) {
                throw ValidationException::withMessages([
                    "rates.{$service->id}.effective_from" => sprintf(
                        'يوجد استحقاق مسجل لهذه الخدمة يبدأ من %s بقيمة %s ج.م. لا يمكن استبداله بسعر مختلف من نفس التاريخ. اختر تاريخ سريان جديدًا للحفاظ على سجل الاستحقاقات.',
                        $existingRate->effective_from->format('d/m/Y'),
                        number_format((float) $existingRate->amount, 2)
                    ),
                ]);
            }
        }

        DB::transaction(function () use ($request, $therapist, $specialtyIds, $serviceIds, $services) {
            $therapist->specialties()->sync($specialtyIds);
            $therapist->services()->sync($serviceIds);

            foreach ($services as $service) {
                $rate = $request->input("rates.{$service->id}");

                TherapistServiceRate::start(
                    $therapist,
                    $service,
                    $rate['amount'],
                    $rate['effective_from'],
                    auth()->id()
                );
            }
        });

        return back()
            ->with('success', 'تم حفظ تخصصات وخدمات واستحقاقات الأخصائي بنجاح')
            ->with('workspace_tab', 'services');
    }
}
