<x-app-layout>
    <x-slot name="title">خطط خدمات {{ $patient->name }}</x-slot>

    <div class="mx-auto max-w-5xl space-y-5">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-2xl font-bold text-text">خطط خدمات {{ $patient->name }}</h1>
                <p class="mt-1 text-sm text-text-muted">ترتيب الخدمات والأسعار والوحدات المدفوعة لكل خطة.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('patients.show', $patient) }}" class="clinic-btn-secondary">ملف المريض</a>
                <a href="{{ route('patients.service-plans.create', $patient) }}" class="clinic-btn-primary">خطة جديدة</a>
            </div>
        </div>

        <div class="clinic-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="clinic-table text-right">
                    <thead>
                        <tr><th class="px-5">رقم الخطة</th><th class="px-5">الحالة</th><th class="px-5">الخدمات</th><th class="px-5">الفترة</th><th class="px-5 text-left">الإجمالي</th></tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border">
                        @forelse($plans as $plan)
                            <tr>
                                <td class="px-5 py-4 font-semibold text-text"><a href="{{ route('patient-service-plans.show', $plan) }}" class="clinic-entity-link">#{{ $plan->id }}</a></td>
                                <td class="px-5 py-4"><x-plan-status-badge :status="$plan->status" /></td>
                                <td class="px-5 py-4 text-text-muted">{{ $plan->items_count }}</td>
                                <td class="px-5 py-4 text-text-muted">{{ $plan->starts_at?->format('Y-m-d') ?? 'غير محددة' }}<span class="block text-xs text-text-subtle">إلى {{ $plan->ends_at?->format('Y-m-d') ?? 'نهاية مفتوحة' }}</span></td>
                                <td class="px-5 py-4 text-left font-semibold tabular-nums text-text">{{ number_format((float) $plan->totalAmount(), 2) }} ج.م</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-text-muted">لا توجد خطط خدمات لهذا الطفل بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
