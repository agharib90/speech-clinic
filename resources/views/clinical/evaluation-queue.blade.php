<x-app-layout>
    <x-slot name="title">تقييماتي السريرية</x-slot>

    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div><h1 class="text-2xl font-bold text-text">تقييماتي السريرية</h1><p class="mt-1 text-sm text-text-muted">الحالات المسندة إليك للتقييم السريري.</p></div>
        <form method="GET">
            <select name="status" class="clinic-field w-full sm:w-52" onchange="this.form.submit()" aria-label="حالة التقييم">
                <option value="">كل الحالات</option>
                <option value="pending" @selected($status === 'pending')>بانتظار البدء</option>
                <option value="in_progress" @selected($status === 'in_progress')>قيد الاستكمال</option>
                <option value="completed" @selected($status === 'completed')>مكتملة</option>
            </select>
        </form>
    </div>

    <div class="overflow-x-auto rounded-lg border border-surface-border bg-surface-elevated">
        <table class="clinic-table min-w-[780px] text-right">
            <thead><tr><th>الحالة</th><th>ولي الأمر</th><th>تاريخ الإسناد</th><th>بدء التقييم</th><th>الوضع</th><th></th></tr></thead>
            <tbody>
                @forelse($assignments as $assignment)
                    <tr>
                        <td><a href="{{ route('patients.workspace', $assignment->patient) }}" class="clinic-entity-link font-semibold">{{ $assignment->patient->name }}</a><div class="mt-1 text-xs text-text-subtle">{{ $assignment->patient->barcode }}</div></td>
                        <td>{{ $assignment->patient->guardian?->name ?? 'غير محدد' }}</td>
                        <td>{{ $assignment->assigned_at->format('Y-m-d H:i') }}</td>
                        <td>{{ $assignment->started_at?->format('Y-m-d H:i') ?? 'لم يبدأ' }}</td>
                        <td><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $assignment->status === \App\Models\PatientClinicalEvaluationAssignment::STATUS_COMPLETED ? 'bg-success-soft text-success' : 'bg-primary-soft text-primary' }}">{{ \App\Models\PatientClinicalEvaluationAssignment::STATUS_LABELS[$assignment->status] }}</span></td>
                        <td><a href="{{ route('patients.workspace', $assignment->patient) }}" class="clinic-btn-secondary">{{ match($assignment->status) { 'pending' => 'بدء التقييم', 'in_progress' => 'استكمال التقييم', default => 'عرض التقييم المكتمل' } }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-text-muted">لا توجد تقييمات في هذه القائمة.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $assignments->links() }}</div>
</x-app-layout>
