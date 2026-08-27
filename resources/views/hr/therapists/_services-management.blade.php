@php
    $selectedSpecialties = old('specialty_ids', $therapist->specialties->pluck('id')->all());
    $selectedServices = old('service_ids', $therapist->services->pluck('id')->all());
@endphp

<div class="space-y-5" x-data="{ specialties: @js(array_map('intval', $selectedSpecialties)) }">
    @can('manage therapist services')
        <form method="POST" action="{{ route('therapists.services.update', $therapist) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <section class="rounded-lg border border-surface-border p-4">
                <h3 class="font-bold text-text">تخصصات الأخصائي</h3>
                <p class="mt-1 text-sm text-text-muted">لن يمكن اختيار خدمة خارج هذه التخصصات.</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($specialties as $specialty)
                        <label class="flex items-center gap-2 rounded-lg border border-surface-border p-3 text-sm text-text">
                            <input type="checkbox" name="specialty_ids[]" value="{{ $specialty->id }}" x-model.number="specialties" @checked(in_array($specialty->id, $selectedSpecialties)) class="rounded border-surface-border text-primary focus:ring-primary">
                            {{ $specialty->name }}
                        </label>
                    @endforeach
                </div>
                @error('specialty_ids')<p class="mt-2 text-sm text-danger">{{ $message }}</p>@enderror
            </section>

            <div class="space-y-4">
                @foreach($specialties as $specialty)
                    <section class="overflow-hidden rounded-lg border border-surface-border">
                        <div class="border-b border-surface-border bg-surface-muted px-4 py-3">
                            <h3 class="font-bold text-text">خدمات {{ $specialty->name }}</h3>
                        </div>
                        <div class="divide-y divide-surface-border">
                            @forelse($specialty->services as $service)
                                @php($currentRate = $currentRates->get($service->id))
                                <div class="grid gap-4 p-4 md:grid-cols-[minmax(180px,1fr)_180px_180px] md:items-start" :class="specialties.includes({{ $specialty->id }}) ? '' : 'opacity-50'">
                                    <label class="flex items-center gap-2 pt-7 text-sm font-medium text-text">
                                        <input type="checkbox" name="service_ids[]" value="{{ $service->id }}" @checked(in_array($service->id, $selectedServices)) :disabled="!specialties.includes({{ $specialty->id }})" class="rounded border-surface-border text-primary focus:ring-primary">
                                        {{ $service->name }}
                                    </label>
                                    <label>
                                        <span class="mb-1 block text-xs font-medium text-text-muted">استحقاق الأخصائي</span>
                                        <input type="number" step="0.01" min="0.01" name="rates[{{ $service->id }}][amount]" value="{{ old("rates.{$service->id}.amount", $currentRate?->amount) }}" class="clinic-field w-full @error("rates.{$service->id}.amount") border-danger @enderror">
                                        @error("rates.{$service->id}.amount")<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                                    </label>
                                    <label>
                                        <span class="mb-1 block text-xs font-medium text-text-muted">تاريخ السريان</span>
                                        <input type="date" name="rates[{{ $service->id }}][effective_from]" value="{{ old("rates.{$service->id}.effective_from", $currentRate?->effective_from?->format('Y-m-d') ?? today()->format('Y-m-d')) }}" class="clinic-field w-full @error("rates.{$service->id}.effective_from") border-danger @enderror">
                                        @error("rates.{$service->id}.effective_from")<p class="mt-1 text-xs leading-5 text-danger">{{ $message }}</p>@enderror
                                    </label>
                                </div>
                            @empty
                                <p class="p-4 text-sm text-text-muted">لا توجد خدمات نشطة في هذا التخصص.</p>
                            @endforelse
                        </div>
                    </section>
                @endforeach
                @error('service_ids')<p class="text-sm text-danger">{{ $message }}</p>@enderror
            </div>

            <div class="flex justify-end">
                <button type="submit" class="clinic-btn-primary">حفظ الخدمات والاستحقاقات</button>
            </div>
        </form>
    @endcan

    <section class="overflow-hidden rounded-lg border border-surface-border">
        <div class="border-b border-surface-border px-4 py-3">
            <h3 class="font-bold text-text">سجل استحقاقات الخدمات</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-surface-border text-sm">
                <thead class="bg-surface-muted text-right text-xs text-text-muted">
                    <tr><th class="px-4 py-3">الخدمة</th><th class="px-4 py-3">المبلغ</th><th class="px-4 py-3">من</th><th class="px-4 py-3">إلى</th></tr>
                </thead>
                <tbody class="divide-y divide-surface-border">
                    @forelse($therapist->serviceRates->sortByDesc('effective_from') as $rate)
                        <tr>
                            <td class="px-4 py-3 text-text">{{ $rate->service->name }}</td>
                            <td class="px-4 py-3 font-semibold text-text">{{ number_format((float) $rate->amount, 2) }} ج.م</td>
                            <td class="px-4 py-3 text-text-muted">{{ $rate->effective_from->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-text-muted">{{ $rate->effective_to?->format('Y-m-d') ?? 'مستمر' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-text-muted">لا يوجد سجل استحقاقات بعد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
