<x-app-layout>
    <x-slot name="title">خدمات واستحقاقات الأخصائي</x-slot>

    <div class="mx-auto max-w-5xl space-y-6">
        @if(session('success'))<div class="rounded-lg border border-success bg-success-soft px-4 py-3 text-sm text-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="rounded-lg border border-danger bg-danger-soft px-4 py-3 text-sm text-danger">تعذر الحفظ. راجع الحقول المحددة.</div>@endif

        <div class="flex items-center justify-between gap-3">
            <div><h1 class="text-2xl font-bold text-text">{{ $therapist->name }}</h1><p class="mt-1 text-sm text-text-muted">التخصصات والخدمات وسعر استحقاق الأخصائي، مستقل عن سعر البيع للعميل.</p></div>
            <a href="{{ route('therapists.show', ['therapist' => $therapist, 'tab' => 'services']) }}" class="clinic-btn-secondary">العودة لمساحة العمل</a>
        </div>

        @include('hr.therapists._services-management')

    </div>
</x-app-layout>
