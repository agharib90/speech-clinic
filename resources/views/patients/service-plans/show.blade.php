<x-app-layout>
    <x-slot name="title">خطة خدمات {{ $patientServicePlan->patient->name }}</x-slot>

    <div class="mx-auto max-w-6xl space-y-5">
        @if(session('success'))<div class="rounded-lg border border-success bg-success-soft px-4 py-3 text-sm text-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="rounded-lg border border-danger bg-danger-soft px-4 py-3 text-sm text-danger">{{ $errors->first() }}</div>@endif

        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
            <div>
                <div class="flex flex-wrap items-center gap-2"><h1 class="text-2xl font-bold text-text">خطة خدمات {{ $patientServicePlan->patient->name }}</h1><x-plan-status-badge :status="$patientServicePlan->status" /></div>
                <p class="mt-2 text-sm text-text-muted">الخطة #{{ $patientServicePlan->id }} @if($patientServicePlan->starts_at || $patientServicePlan->ends_at) · {{ $patientServicePlan->starts_at?->format('Y-m-d') ?? 'بداية غير محددة' }} إلى {{ $patientServicePlan->ends_at?->format('Y-m-d') ?? 'نهاية مفتوحة' }} @endif</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('patients.workspace', $patientServicePlan->patient) }}" class="clinic-btn-secondary">رجوع إلى ملف الحالة</a>
                <a href="{{ route('patients.service-plans.index', $patientServicePlan->patient) }}" class="clinic-btn-secondary">كل الخطط</a>
                <a href="{{ route('patient-service-plans.edit', $patientServicePlan) }}" class="clinic-btn-secondary text-primary">تعديل الخطة</a>
                @if($patientServicePlan->status === \App\Models\PatientServicePlan::STATUS_DRAFT
                    && ($patientServicePlan->clinical_evaluation_id === null || $patientServicePlan->isClinicallyApproved()))
                    <form method="POST" action="{{ route('patient-service-plans.activate', $patientServicePlan) }}">@csrf<button class="clinic-btn-primary">تفعيل الخطة</button></form>
                @endif
            </div>
        </div>

        @if($patientServicePlan->hasProtectedHistory())
            <div class="rounded-lg border border-warning bg-warning-soft px-4 py-3 text-sm text-warning"><span class="font-semibold">الخطة محمية ماليًا.</span> يمكن تعديل الملاحظات فقط بعد تسجيل دفعة أو استخدام خدمة.</div>
        @endif

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="clinic-card p-5"><p class="text-xs text-text-muted">إجمالي الخطة</p><p class="mt-1 text-xl font-bold text-text">{{ number_format((float) $patientServicePlan->totalAmount(), 2) }} ج.م</p></div>
            <div class="clinic-card p-5"><p class="text-xs text-text-muted">المدفوع المخصص للخطة</p><p class="mt-1 text-xl font-bold text-success">{{ number_format((float) $patientServicePlan->paidAmount(), 2) }} ج.م</p></div>
            <div class="clinic-card p-5"><p class="text-xs text-text-muted">الرصيد النقدي المتاح</p><p class="mt-1 text-xl font-bold text-accent">{{ number_format((float) $patientServicePlan->availableCredit(), 2) }} ج.م</p></div>
            <div class="clinic-card p-5"><p class="text-xs text-text-muted">المتبقي غير المدفوع</p><p class="mt-1 text-xl font-bold text-warning">{{ number_format((float) $patientServicePlan->unpaidAmount(), 2) }} ج.م</p></div>
        </div>

        <div class="clinic-card overflow-hidden">
            <div class="border-b border-surface-border p-5"><h2 class="font-bold text-text">الخدمات المرتبة</h2></div>
            <div class="overflow-x-auto"><table class="clinic-table">
                <thead class="bg-surface-muted text-right text-xs text-text-muted"><tr><th class="px-4 py-3">#</th><th class="px-4 py-3">الخدمة والتقدم</th><th class="px-4 py-3 text-left">المخطط</th><th class="px-4 py-3 text-left">سعر الوحدة</th><th class="px-4 py-3 text-left">بعد الخصم</th><th class="px-4 py-3 text-left">المدفوع</th><th class="px-4 py-3 text-left">المستخدم</th><th class="px-4 py-3 text-left">المتاح</th><th class="px-4 py-3 text-left">غير مدفوع</th></tr></thead>
                <tbody>@foreach($patientServicePlan->items as $item)<tr>
                    <td class="px-4 py-4 font-semibold text-text">{{ $item->position }}</td><td class="min-w-52 px-4 py-4"><p class="font-semibold text-text">{{ $item->service->name }}</p><p class="text-xs text-text-muted">{{ $item->service->specialty->name }} · {{ $item->authorized_quantity }} من {{ $item->planned_quantity }} مدفوعة</p><div class="mt-2 h-1.5 overflow-hidden rounded-full bg-surface-muted"><div class="h-full rounded-full bg-success" style="width: {{ $item->planned_quantity > 0 ? min(100, ($item->authorized_quantity / $item->planned_quantity) * 100) : 0 }}%"></div></div></td><td class="px-4 py-4 text-left tabular-nums text-text-muted">{{ $item->planned_quantity }}</td><td class="px-4 py-4 text-left tabular-nums text-text-muted">{{ number_format((float) $item->customer_unit_price, 2) }}<span class="block text-[11px] text-text-subtle">سعر محفوظ وقت إضافة الخدمة</span></td><td class="px-4 py-4 text-left tabular-nums font-semibold text-text">{{ number_format((float) $item->final_unit_price, 2) }} @if((float) $item->discount_amount > 0)<span class="block text-xs font-normal text-accent">خصم {{ number_format((float) $item->discount_amount, 2) }}</span>@endif</td><td class="px-4 py-4 text-left tabular-nums text-success">{{ $item->authorized_quantity }}</td><td class="px-4 py-4 text-left tabular-nums text-text-muted">{{ $item->consumed_quantity }}</td><td class="px-4 py-4 text-left tabular-nums font-semibold text-primary">{{ $item->remainingAuthorizedQuantity() }}</td><td class="px-4 py-4 text-left tabular-nums text-warning">{{ $item->unpaidQuantity() }}</td>
                </tr>@endforeach</tbody>
            </table></div>
        </div>

        @if($patientServicePlan->status === \App\Models\PatientServicePlan::STATUS_ACTIVE)
            <div class="clinic-card p-5">
                <h2 class="font-bold text-text">تخصيص دفعة فاتورة</h2>
                <p class="mt-1 text-sm text-text-muted">سجّل الدفعة أولًا من صفحة الفاتورة، ثم اخترها هنا لتوزيعها تلقائيًا حسب ترتيب الخطة.</p>
                <form method="POST" action="{{ route('patient-service-plans.allocate-payment', $patientServicePlan) }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">@csrf
                    <label class="flex-1"><span class="mb-1 block text-sm font-medium text-text">الدفعة غير المخصصة</span><select name="invoice_payment_id" class="clinic-field w-full" required><option value="">اختر دفعة</option>@foreach($availablePayments as $payment)<option value="{{ $payment->id }}">{{ $payment->invoice->invoice_number }} · {{ $payment->payment_date }} · {{ number_format((float) $payment->amount, 2) }} ج.م</option>@endforeach</select></label>
                    <button class="clinic-btn-primary disabled:opacity-50" @disabled($availablePayments->isEmpty())>تخصيص الدفعة</button>
                </form>
                @if($availablePayments->isEmpty())<p class="mt-3 text-sm text-text-muted">لا توجد دفعات فواتير متاحة لهذا المريض.</p>@endif
            </div>
        @endif

        @if($patientServicePlan->planPayments->isEmpty())
            <p class="text-sm text-text-muted">لم يتم تخصيص أي دفعات للخطة بعد.</p>
        @endif

        @if($patientServicePlan->canDelete())
            <div class="border-t border-surface-border pt-5">
                <form method="POST" action="{{ route('patient-service-plans.destroy', $patientServicePlan) }}" onsubmit="return confirm('هل تريد حذف هذه المسودة غير المستخدمة نهائيًا؟ لا يمكن التراجع عن هذا الإجراء.');">
                    @csrf
                    @method('DELETE')
                    <button class="clinic-btn-danger-soft">حذف المسودة</button>
                    <p class="mt-2 text-xs text-text-muted">يتاح الحذف فقط للمسودة التي لا تحتوي على دفعات أو استخدام.</p>
                </form>
            </div>
        @endif
    </div>
</x-app-layout>
