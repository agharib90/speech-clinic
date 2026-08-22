<x-app-layout>
    <x-slot name="title">خدمات واستحقاقات الأخصائي</x-slot>

    @php
        $selectedSpecialties = old('specialty_ids', $therapist->specialties->pluck('id')->all());
        $selectedServices = old('service_ids', $therapist->services->pluck('id')->all());
    @endphp

    <div class="mx-auto max-w-5xl space-y-6" x-data="{ specialties: @js(array_map('intval', $selectedSpecialties)) }">
        @if(session('success'))<div class="rounded-lg border border-success bg-success-soft px-4 py-3 text-sm text-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="rounded-lg border border-danger bg-danger-soft px-4 py-3 text-sm text-danger">تعذر الحفظ. راجع الحقول المحددة.</div>@endif

        <div class="flex items-center justify-between gap-3">
            <div><h1 class="text-2xl font-bold text-text">{{ $therapist->name }}</h1><p class="mt-1 text-sm text-text-muted">التخصصات والخدمات وسعر استحقاق الأخصائي، مستقل عن سعر البيع للعميل.</p></div>
            <a href="{{ route('therapists.index') }}" class="rounded-lg border border-surface-border px-4 py-2 text-sm text-text hover:bg-surface-muted">العودة للفريق</a>
        </div>

        <form method="POST" action="{{ route('therapists.services.update', $therapist) }}" class="space-y-6">
            @csrf @method('PUT')
            <section class="clinic-card p-5">
                <h2 class="font-bold text-text">تخصصات الأخصائي</h2>
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

            <section class="space-y-4">
                @foreach($specialties as $specialty)
                    <div class="clinic-card overflow-hidden">
                        <div class="border-b border-surface-border bg-surface-muted px-5 py-3"><h2 class="font-bold text-text">خدمات {{ $specialty->name }}</h2></div>
                        <div class="divide-y divide-surface-border">
                            @forelse($specialty->services as $service)
                                @php $currentRate = $currentRates->get($service->id); @endphp
                                <div class="grid gap-4 p-5 md:grid-cols-[minmax(180px,1fr)_180px_180px] md:items-end" :class="specialties.includes({{ $specialty->id }}) ? '' : 'opacity-50'">
                                    <label class="flex items-center gap-2 text-sm font-medium text-text">
                                        <input type="checkbox" name="service_ids[]" value="{{ $service->id }}" @checked(in_array($service->id, $selectedServices)) :disabled="!specialties.includes({{ $specialty->id }})" class="rounded border-surface-border text-primary focus:ring-primary">
                                        {{ $service->name }}
                                    </label>
                                    <label><span class="mb-1 block text-xs font-medium text-text-muted">سعر استحقاق الأخصائي</span><input type="number" step="0.01" min="0.01" name="rates[{{ $service->id }}][amount]" value="{{ old("rates.{$service->id}.amount", $currentRate?->amount) }}" class="clinic-field w-full"></label>
                                    <label><span class="mb-1 block text-xs font-medium text-text-muted">تاريخ السريان</span><input type="date" name="rates[{{ $service->id }}][effective_from]" value="{{ old("rates.{$service->id}.effective_from", $currentRate?->effective_from?->format('Y-m-d') ?? today()->format('Y-m-d')) }}" class="clinic-field w-full"></label>
                                </div>
                            @empty
                                <p class="p-5 text-sm text-text-muted">لا توجد خدمات نشطة في هذا التخصص.</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
                @error('service_ids')<p class="text-sm text-danger">{{ $message }}</p>@enderror
            </section>

            <div class="flex justify-end"><button class="rounded-lg bg-primary px-5 py-2.5 text-sm font-medium text-primary-contrast hover:bg-primary-hover">حفظ الخدمات والاستحقاقات</button></div>
        </form>

        <section class="clinic-card overflow-hidden">
            <div class="border-b border-surface-border p-5"><h2 class="font-bold text-text">سجل أسعار الاستحقاق</h2></div>
            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-surface-border text-sm">
                <thead class="bg-surface-muted text-right text-xs text-text-muted"><tr><th class="px-5 py-3">الخدمة</th><th class="px-5 py-3">المبلغ</th><th class="px-5 py-3">من</th><th class="px-5 py-3">إلى</th></tr></thead>
                <tbody class="divide-y divide-surface-border">@forelse($therapist->serviceRates->sortByDesc('effective_from') as $rate)<tr><td class="px-5 py-3 text-text">{{ $rate->service->name }}</td><td class="px-5 py-3 font-semibold text-text">{{ number_format((float) $rate->amount, 2) }} ج.م</td><td class="px-5 py-3 text-text-muted">{{ $rate->effective_from->format('Y-m-d') }}</td><td class="px-5 py-3 text-text-muted">{{ $rate->effective_to?->format('Y-m-d') ?? 'مستمر' }}</td></tr>@empty<tr><td colspan="4" class="px-5 py-8 text-center text-text-muted">لا يوجد سجل استحقاقات بعد.</td></tr>@endforelse</tbody>
            </table></div>
        </section>
    </div>
</x-app-layout>
