<x-app-layout>
    <x-slot name="title">خطط جاهزة للاستقبال</x-slot>

    <div class="mb-6"><h1 class="text-2xl font-bold text-text">خطط جاهزة للاستقبال</h1><p class="mt-1 text-sm text-text-muted">خطط اعتمدها الفريق السريري وتنتظر الإجراء التشغيلي من الاستقبال.</p></div>

    <div class="overflow-x-auto rounded-lg border border-surface-border bg-surface-elevated">
        <table class="clinic-table min-w-[860px] text-right">
            <thead><tr><th>الحالة</th><th>وقت الاعتماد</th><th>اعتمدها</th><th>الخدمات والكميات</th><th></th></tr></thead>
            <tbody>
                @forelse($plans as $plan)
                    <tr>
                        <td><a href="{{ route('patients.workspace', $plan->patient) }}" class="clinic-entity-link font-semibold">{{ $plan->patient->name }}</a><div class="mt-1 text-xs text-text-subtle">{{ $plan->patient->barcode }}</div></td>
                        <td>{{ $plan->clinical_approved_at->format('Y-m-d H:i') }}</td>
                        <td>{{ $plan->clinicalApprover?->name ?? 'حساب سابق' }}</td>
                        <td><span class="font-semibold text-text">{{ $plan->items_count }} خدمة</span><div class="mt-1 text-xs text-text-muted">{{ $plan->items->map(fn ($item) => $item->service->name.' × '.$item->planned_quantity)->join('، ') }}</div></td>
                        <td><a href="{{ route('patients.workspace', $plan->patient) }}" class="clinic-btn-primary">فتح ملف الحالة</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-10 text-center text-text-muted">لا توجد خطط تنتظر الاستقبال الآن.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $plans->links() }}</div>
</x-app-layout>
