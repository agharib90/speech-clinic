<x-app-layout>
    <x-slot name="title">ملف الحالة الذكي</x-slot>

    @php
        $plan = $workspace['currentPlan'];
        $summary = $workspace['plan'];
        $financial = $workspace['financial'];
        $invoiceFinancial = $workspace['invoiceFinancial'];
        $initialPanel = $errors->any() ? old('workspace_panel') : null;
        $planFormItems = old('workspace_panel') === 'plan' ? old('items', []) : [];
        if ($planFormItems === []) {
            $planFormItems = [[
                'id' => null,
                'service_id' => '',
                'position' => 1,
                'planned_quantity' => 1,
                'customer_unit_price' => '',
                'discount_amount' => '0.00',
            ]];
        }
        $alertClasses = [
            'primary' => 'border-primary/30 bg-primary-soft text-primary',
            'success' => 'border-success/30 bg-success-soft text-success',
            'warning' => 'border-warning/30 bg-warning-soft text-warning',
            'danger' => 'border-danger/30 bg-danger-soft text-danger',
        ];
        $responsibleTherapist = $patient->therapyPrograms
            ->where('status', \App\Models\TherapyProgram::STATUS_ACTIVE)
            ->first()?->therapist?->name;
    @endphp

    <div class="patient-workspace -m-4 min-h-screen p-4 sm:-m-6 sm:p-6 lg:-m-8 lg:p-8" x-data="{ panel: {{ Illuminate\Support\Js::from($initialPanel) }}, invoiceDetail: null, openPanel(name) { this.panel = name; this.$nextTick(() => document.getElementById(`workspace-${name}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' })); } }">
        <div class="mx-auto max-w-[1500px] space-y-5 pb-8">
            @if(session('success'))
                <div class="rounded-lg border border-success/30 bg-success-soft px-4 py-3 text-sm text-success" role="alert">
                    {{ session('success') }}
                </div>
            @endif
            @if($errors->any())
                <div class="rounded-lg border border-danger/30 bg-danger-soft px-4 py-3 text-sm text-danger" role="alert">{{ $errors->first() }}</div>
            @endif

            <header class="workspace-hero overflow-hidden rounded-lg border border-surface-border p-5 sm:p-7">
                <div class="flex flex-col justify-between gap-6 xl:flex-row xl:items-center">
                    <div class="flex min-w-0 items-start gap-4 sm:items-center">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-lg border border-primary/30 bg-primary-soft text-2xl font-bold text-primary sm:h-20 sm:w-20">
                            {{ mb_substr($patient->name, 0, 1) }}
                        </div>
                        <div class="min-w-0">
                            <div class="mb-2 flex flex-wrap items-center gap-2">
                                <span class="rounded-full border border-primary/30 bg-primary-soft px-2.5 py-1 text-xs font-semibold text-primary">مساحة عمل الحالة</span>
                                <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $patient->is_active ? 'border-success/30 bg-success-soft text-success' : 'border-warning/30 bg-warning-soft text-warning' }}">
                                    {{ $patient->is_active ? 'حالة نشطة' : 'حالة غير نشطة' }}
                                </span>
                            </div>
                            <h1 class="truncate text-2xl font-bold text-text sm:text-3xl">{{ $patient->name }}</h1>
                            <p class="mt-1 text-sm text-text-muted">ملف الحالة الذكي · نفّذ المهمة من مكان واحد</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm sm:grid-cols-3 xl:min-w-[620px]">
                        @if($patient->barcode)
                            <div><span class="block text-xs text-text-subtle">كود الحالة</span><span class="font-semibold text-text">{{ $patient->barcode }}</span></div>
                        @endif
                        @if($patient->birth_date)
                            <div><span class="block text-xs text-text-subtle">العمر</span><span class="font-semibold text-text">{{ $patient->birth_date->age }} سنة</span></div>
                        @endif
                        @if($patient->gender)
                            <div><span class="block text-xs text-text-subtle">النوع</span><span class="font-semibold text-text">{{ $patient->gender === 'male' ? 'ذكر' : 'أنثى' }}</span></div>
                        @endif
                        @if($patient->guardian)
                            <div><span class="block text-xs text-text-subtle">ولي الأمر</span><span class="font-semibold text-text">{{ $patient->guardian->name }}</span></div>
                        @endif
                        @if($patient->guardian?->phone)
                            <div><span class="block text-xs text-text-subtle">الهاتف</span><span class="font-semibold text-text" dir="ltr">{{ $patient->guardian->phone }}</span></div>
                        @endif
                        @if($responsibleTherapist)
                            <div><span class="block text-xs text-text-subtle">الأخصائي المسؤول</span><span class="font-semibold text-text">{{ $responsibleTherapist }}</span></div>
                        @endif
                    </div>
                </div>
            </header>

            <section aria-labelledby="quick-actions-title">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 id="quick-actions-title" class="text-sm font-semibold text-text">إجراءات الحالة السريعة</h2>
                    <a href="{{ route('patients.show', $patient) }}" class="text-xs font-medium text-text-muted hover:text-primary">الملف الطبي الكامل</a>
                </div>
                <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
                    @can('manage invoices')
                        @if($patient->is_active)
                            <button type="button" @click="openPanel('invoice')" class="workspace-action w-full">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8v1m0 10v1M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z" /></svg>
                                <span>إنشاء فاتورة</span>
                            </button>
                        @endif
                    @endcan

                    @if(auth()->user()->can('manage invoices') && auth()->user()->can('view finance') && $workspace['latestOutstandingInvoice'])
                        <button type="button" @click="openPanel('payment')" class="workspace-action w-full">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a5 5 0 00-10 0v2m-2 0h14v11H5V9zm7 4v3" /></svg>
                            <span>تسجيل دفعة</span>
                        </button>
                    @endif

                    @can('manage patient service plans')
                        @if($plan && $plan->status === \App\Models\PatientServicePlan::STATUS_ACTIVE && $workspace['unallocatedPayments']->isNotEmpty())
                            <button type="button" @click="openPanel('allocation')" class="workspace-action w-full">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h7m6-5 3 3m0 0-3 3m3-3h-6" /></svg>
                                <span>تخصيص الدفعة</span>
                            </button>
                        @else
                            <button type="button" @click="openPanel('plan')" class="workspace-action w-full">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                <span>{{ $plan ? 'إدارة الخطة' : 'إنشاء خطة' }}</span>
                            </button>
                        @endif
                    @endcan

                    @if(auth()->user()->can('view appointments') && auth()->user()->can('create appointments'))
                        <button type="button" @click="openPanel('appointment')" class="workspace-action w-full">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" /></svg>
                            <span>حجز موعد</span>
                        </button>
                    @endif

                    @can('manage checkins')
                        <div class="workspace-action workspace-action-disabled flex-col gap-1" aria-disabled="true">
                            <span class="flex items-center gap-2"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6" /></svg><span>تأكيد الحضور</span></span>
                            <small>سيتم تفعيله مع مسار الاستقبال الجديد</small>
                        </div>
                    @endcan
                </div>
            </section>

            <section class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                <article class="workspace-card p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-medium text-text-muted">خطة الخدمات</p>
                            <h2 class="mt-1 text-lg font-bold text-text">
                                @if($plan)
                                    <a href="#workspace-plan" class="clinic-entity-link">خطة #{{ $plan->id }} · {{ \App\Models\PatientServicePlan::STATUS_LABELS[$plan->status] ?? $plan->status }}</a>
                                @else
                                    {{ $plan ? 'خطة #'.$plan->id.' · '.(\App\Models\PatientServicePlan::STATUS_LABELS[$plan->status] ?? $plan->status) : 'لا توجد خطة نشطة' }}
                                @endif
                            </h2>
                        </div>
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-soft text-primary">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6m-6 4h6m-6 4h4m-6 8h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                        </span>
                    </div>
                    <div class="mt-5 grid grid-cols-2 gap-4 text-sm">
                        <div><span class="text-text-subtle">المخطط</span><p class="mt-1 text-xl font-bold text-text">{{ $summary['planned'] }}</p></div>
                        <div><span class="text-text-subtle">الممول</span><p class="mt-1 text-xl font-bold text-success">{{ $summary['authorized'] }}</p></div>
                        <div><span class="text-text-subtle">المنفذ</span><p class="mt-1 text-xl font-bold text-text">{{ $summary['consumed'] }}</p></div>
                        <div><span class="text-text-subtle">جاهز للتنفيذ</span><p class="mt-1 text-xl font-bold text-primary">{{ $summary['remaining_executable'] }}</p></div>
                    </div>
                </article>

                <article class="workspace-card p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div><p class="text-xs font-medium text-text-muted">الملخص المالي</p><h2 class="mt-1 text-lg font-bold text-text">{{ number_format((float) $financial['plan_total'], 2) }} ج.م</h2></div>
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-success-soft text-success">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 12v-2m9-4a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                    </div>
                    <dl class="mt-5 space-y-3 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-text-muted">مدفوع للخطة</dt><dd class="font-semibold text-success">{{ number_format((float) $financial['paid'], 2) }} ج.م</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-text-muted">رصيد متاح</dt><dd class="font-semibold text-primary">{{ number_format((float) $financial['available_credit'], 2) }} ج.م</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-text-muted">المبلغ المتبقي</dt><dd class="font-semibold text-text">{{ number_format((float) $financial['remaining'], 2) }} ج.م</dd></div>
                        <div class="flex justify-between gap-3 border-t border-surface-border pt-3"><dt class="text-text-muted">دفعات غير مخصصة</dt><dd class="font-semibold text-warning">{{ number_format((float) $financial['unallocated'], 2) }} ج.م</dd></div>
                    </dl>
                </article>

                <article class="workspace-card p-5">
                    <div class="flex items-center justify-between gap-4">
                        <div><p class="text-xs font-medium text-text-muted">تنبيهات قابلة للتنفيذ</p><h2 class="mt-1 text-lg font-bold text-text">{{ $workspace['alerts']->count() }} تنبيه</h2></div>
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-warning-soft text-warning">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 4h.01m-7.938 3h15.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L2.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        </span>
                    </div>
                    <div class="mt-4 space-y-2">
                        @forelse($workspace['alerts'] as $alert)
                            <p class="rounded-lg border px-3 py-2 text-xs {{ $alertClasses[$alert['tone']] }}">{{ $alert['text'] }}</p>
                        @empty
                            <p class="rounded-lg border border-success/30 bg-success-soft px-3 py-3 text-sm text-success">لا توجد تنبيهات عاجلة الآن.</p>
                        @endforelse
                    </div>
                </article>
            </section>

            <section class="workspace-recommendation rounded-lg border border-primary/30 p-5 sm:p-6">
                <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-center">
                    <div>
                        <p class="text-xs font-semibold text-primary">الخطوة التالية الموصى بها</p>
                        <h2 class="mt-2 text-2xl font-bold text-text">{{ $workspace['recommendation']['title'] }}</h2>
                        <p class="mt-1 text-sm text-text-muted">{{ $workspace['recommendation']['description'] }}</p>
                    </div>
                    @if($workspace['recommendation']['panel'])
                        <button type="button" @click="openPanel('{{ $workspace['recommendation']['panel'] }}')" class="clinic-btn-primary shrink-0">ابدأ الخطوة</button>
                    @else
                        <a href="{{ $workspace['recommendation']['url'] }}" class="clinic-btn-primary shrink-0">ابدأ الخطوة</a>
                    @endif
                </div>

                <div class="mt-7 grid grid-cols-3 gap-y-5 sm:grid-cols-6" aria-label="مراحل سير الحالة">
                    @foreach($workspace['workflow'] as $index => $stage)
                        <div class="relative flex flex-col items-center text-center">
                            @if(! $loop->last)
                                <span class="absolute right-1/2 top-4 hidden h-px w-full bg-surface-border sm:block"></span>
                            @endif
                            <span class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full border text-xs font-bold {{ $stage['complete'] ? 'border-primary bg-primary text-primary-contrast' : 'border-surface-border bg-surface-muted text-text-subtle' }}">
                                {{ $stage['complete'] ? '✓' : $index + 1 }}
                            </span>
                            <span class="mt-2 text-xs font-medium {{ $stage['complete'] ? 'text-primary' : 'text-text-muted' }}">{{ $stage['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            @can('manage patient service plans')
                <section id="workspace-plan" class="workspace-card scroll-mt-4 p-5 sm:p-6">
                    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                        <div><p class="text-xs font-semibold text-primary">خطة الخدمات</p><h2 class="mt-1 text-xl font-bold text-text">{{ $plan ? 'خطة #'.$plan->id : 'لا توجد خطة حالية' }}</h2></div>
                        @if($plan)
                            <div class="flex flex-wrap gap-2">
                                <x-plan-status-badge :status="$plan->status" />
                                @if($plan->canFullyEdit())<a href="{{ URL::signedRoute('patient-service-plans.edit', ['patientServicePlan' => $plan, 'workspace_patient' => $patient->id, 'workspace' => 1]) }}" class="clinic-btn-secondary">تعديل الخطة</a>@endif
                            </div>
                        @endif
                    </div>

                    @if($plan)
                        <div class="mt-5 overflow-x-auto rounded-lg border border-surface-border">
                            <table class="clinic-table min-w-[850px] text-right">
                                <thead><tr><th>الخدمة</th><th>المخطط</th><th>الممول</th><th>المنفذ</th><th>المتاح</th><th>غير ممول</th><th>السعر</th><th>الخصم</th><th>النهائي</th></tr></thead>
                                <tbody>@foreach($plan->items as $item)<tr><td><span class="font-semibold text-text">{{ $item->service->name }}</span><span class="block text-xs text-text-subtle">{{ $item->service->specialty->name }}</span></td><td>{{ $item->planned_quantity }}</td><td class="text-success">{{ $item->authorized_quantity }}</td><td>{{ $item->consumed_quantity }}</td><td class="text-primary">{{ $item->remainingAuthorizedQuantity() }}</td><td class="text-warning">{{ $item->unpaidQuantity() }}</td><td>{{ number_format((float) $item->customer_unit_price, 2) }}</td><td>{{ number_format((float) $item->discount_amount, 2) }}</td><td class="font-semibold">{{ number_format((float) $item->final_unit_price, 2) }} ج.م</td></tr>@endforeach</tbody>
                            </table>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-lg bg-surface-muted px-4 py-3 text-sm">
                            <span class="text-text-muted">إجمالي الخطة <strong class="text-text">{{ number_format((float) $financial['plan_total'], 2) }} ج.م</strong> · الرصيد المتاح <strong class="text-primary">{{ number_format((float) $financial['available_credit'], 2) }} ج.م</strong></span>
                            @if($plan->status === \App\Models\PatientServicePlan::STATUS_DRAFT)
                                <form method="POST" action="{{ URL::signedRoute('patient-service-plans.activate', ['patientServicePlan' => $plan, 'workspace_patient' => $patient->id]) }}">@csrf<input type="hidden" name="workspace" value="1"><input type="hidden" name="workspace_panel" value="plan"><button class="clinic-btn-primary">تفعيل الخطة</button></form>
                            @endif
                        </div>

                        @if($plan->status === \App\Models\PatientServicePlan::STATUS_ACTIVE)
                            <div id="workspace-allocation" class="mt-5 scroll-mt-4" x-show="panel === 'allocation' || {{ $workspace['unallocatedPayments']->isNotEmpty() ? 'true' : 'false' }}" x-cloak>
                                <h3 class="font-bold text-text">دفعات غير مخصصة</h3>
                                <div class="mt-3 grid gap-3 lg:grid-cols-2">
                                    @forelse($workspace['unallocatedPayments'] as $payment)
                                        <form method="POST" action="{{ URL::signedRoute('patient-service-plans.allocate-payment', ['patientServicePlan' => $plan, 'workspace_patient' => $patient->id]) }}" class="flex flex-col justify-between gap-3 rounded-lg border border-warning/30 bg-warning-soft p-4 sm:flex-row sm:items-center">@csrf<input type="hidden" name="workspace" value="1"><input type="hidden" name="workspace_panel" value="allocation"><input type="hidden" name="invoice_payment_id" value="{{ $payment->id }}"><div><p class="font-semibold text-text">{{ number_format((float) $payment->amount, 2) }} ج.م</p><p class="text-xs text-text-muted">{{ $payment->invoice->invoice_number }} · {{ $payment->payment_date }}</p></div><button class="clinic-btn-primary">تخصيص للخطة</button></form>
                                    @empty
                                        <p class="text-sm text-text-muted">لا توجد دفعات تنتظر التخصيص.</p>
                                    @endforelse
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="mt-4 flex flex-col items-start gap-3 rounded-lg border border-warning/30 bg-warning-soft p-4"><p class="text-sm text-warning">أنشئ مسودة الخطة هنا دون مغادرة سياق {{ $patient->name }}.</p><button type="button" class="clinic-btn-primary" @click="panel = panel === 'plan' ? null : 'plan'">إنشاء خطة</button></div>
                        <div class="mt-5" x-show="panel === 'plan'" x-cloak>@include('patients.service-plans._form', ['patient' => $patient, 'services' => $workspace['services'], 'formItems' => $planFormItems, 'workspaceMode' => true])</div>
                    @endif
                </section>
            @endcan

            @if(auth()->user()->can('view finance') || auth()->user()->can('manage invoices'))
                <section id="workspace-finance" class="workspace-card scroll-mt-4 p-5 sm:p-6">
                    <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                        <div><p class="text-xs font-semibold text-primary">المالية والفواتير</p><h2 class="mt-1 text-xl font-bold text-text">السجل المالي لـ {{ $patient->name }}</h2></div>
                        <div class="flex flex-wrap gap-2">@can('manage invoices')<button type="button" class="clinic-btn-primary" @click="panel = panel === 'invoice' ? null : 'invoice'">إنشاء فاتورة</button>@endcan @if(auth()->user()->can('manage invoices') && auth()->user()->can('view finance') && $workspace['payableInvoices']->isNotEmpty())<button type="button" class="clinic-btn-secondary" @click="panel = panel === 'payment' ? null : 'payment'">تسجيل دفعة</button>@endif</div>
                    </div>

                    @can('view finance')
                        <dl class="mt-5 grid gap-3 sm:grid-cols-3"><div class="rounded-lg bg-surface-muted p-4"><dt class="text-xs text-text-muted">إجمالي الفواتير</dt><dd class="mt-1 text-xl font-bold text-text">{{ number_format((float) $invoiceFinancial['total_invoiced'], 2) }} ج.م</dd></div><div class="rounded-lg bg-success-soft p-4"><dt class="text-xs text-success">إجمالي المدفوع</dt><dd class="mt-1 text-xl font-bold text-success">{{ number_format((float) $invoiceFinancial['total_paid'], 2) }} ج.م</dd></div><div class="rounded-lg bg-warning-soft p-4"><dt class="text-xs text-warning">الرصيد المستحق</dt><dd class="mt-1 text-xl font-bold text-warning">{{ number_format((float) $invoiceFinancial['outstanding'], 2) }} ج.م</dd></div></dl>
                    @endcan

                    @can('manage invoices')
                        <div id="workspace-invoice" class="mt-5 scroll-mt-4 rounded-lg border border-surface-border p-4" x-show="panel === 'invoice'" x-cloak>@include('finance.invoices._form', ['workspaceMode' => true, 'patient' => $patient, 'planItems' => $workspace['invoicePlanItems'], 'formAction' => URL::signedRoute('invoices.store', ['workspace_patient' => $patient->id])])</div>
                    @endcan

                    @if(auth()->user()->can('manage invoices') && auth()->user()->can('view finance'))
                        @php
                            $paymentOptions = $workspace['payableInvoices']->map(fn ($invoice) => [
                                'id' => (string) $invoice->id,
                                'label' => $invoice->invoice_number.' — '.number_format((float) $invoice->remaining_amount, 2).' ج.م متبقي',
                                'action' => URL::signedRoute('invoices.payments', ['invoice' => $invoice, 'workspace_patient' => $patient->id]),
                            ])->values();
                        @endphp
                        <div id="workspace-payment" class="mt-5 scroll-mt-4 rounded-lg border border-surface-border p-4" x-show="panel === 'payment'" x-cloak x-data="{ options: {{ Illuminate\Support\Js::from($paymentOptions) }}, invoiceId: {{ Illuminate\Support\Js::from((string) old('workspace_invoice_id', '')) }}, get selected() { return this.options.find(option => option.id === this.invoiceId); } }">
                            <h3 class="font-bold text-text">تسجيل دفعة</h3>
                            <form method="POST" :action="selected?.action || ''" class="mt-4 grid gap-4 md:grid-cols-5 md:items-end">@csrf<input type="hidden" name="workspace" value="1"><input type="hidden" name="workspace_panel" value="payment"><label class="md:col-span-2"><span class="mb-1 block text-sm text-text-muted">الفاتورة</span><select name="workspace_invoice_id" x-model="invoiceId" class="clinic-field w-full" required><option value="">اختر فاتورة لهذه الحالة</option><template x-for="option in options" :key="option.id"><option :value="option.id" x-text="option.label"></option></template></select></label><label><span class="mb-1 block text-sm text-text-muted">المبلغ</span><input type="number" name="amount" min="0.01" step="0.01" value="{{ old('amount') }}" class="clinic-field w-full" required></label><label><span class="mb-1 block text-sm text-text-muted">طريقة الدفع</span><select name="method" class="clinic-field w-full"><option value="كاش">كاش</option><option value="تحويل">تحويل</option><option value="بطاقة">بطاقة</option></select></label><label><span class="mb-1 block text-sm text-text-muted">التاريخ</span><input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" class="clinic-field w-full" required></label><div class="flex justify-end md:col-span-5"><button class="clinic-btn-primary" :disabled="!selected">تسجيل الدفعة</button></div></form>
                        </div>
                    @endif

                    @can('view finance')
                        <div class="mt-5 overflow-x-auto rounded-lg border border-surface-border">
                            <table class="clinic-table min-w-[760px] text-right">
                                <thead><tr><th>رقم الفاتورة</th><th>التاريخ</th><th>الإجمالي</th><th>المدفوع</th><th>المتبقي</th><th>الحالة</th></tr></thead>
                                <tbody>
                                    @forelse($workspace['invoices'] as $invoice)
                                        <tr>
                                            <td><button type="button" class="clinic-entity-link" @click="invoiceDetail = invoiceDetail === {{ $invoice->id }} ? null : {{ $invoice->id }}">{{ $invoice->invoice_number }}</button></td>
                                            <td>{{ $invoice->issue_date }}</td><td>{{ number_format((float) $invoice->total, 2) }}</td><td class="text-success">{{ number_format((float) $invoice->paid_amount, 2) }}</td><td class="text-warning">{{ number_format((float) $invoice->remaining_amount, 2) }}</td><td>{{ $invoice->status }}</td>
                                        </tr>
                                        <tr x-show="invoiceDetail === {{ $invoice->id }}" x-cloak>
                                            <td colspan="6" class="bg-surface-muted p-4">
                                                <div class="grid gap-4 lg:grid-cols-2">
                                                    <div><h4 class="text-sm font-bold text-text">بنود الفاتورة</h4><div class="mt-2 space-y-2">@foreach($invoice->items as $item)<div class="flex justify-between gap-3 text-xs"><span class="text-text">{{ $item->description }} × {{ $item->quantity }}</span><span class="font-semibold text-text">{{ number_format((float) $item->total, 2) }} ج.م</span></div>@endforeach</div></div>
                                                    <div><h4 class="text-sm font-bold text-text">سجل الدفعات</h4><div class="mt-2 space-y-2">@forelse($invoice->payments as $payment)<div class="flex justify-between gap-3 text-xs"><span class="text-text-muted">{{ $payment->payment_date }} · {{ $payment->method }}</span><span class="font-semibold text-success">{{ number_format((float) $payment->amount, 2) }} ج.م</span></div>@empty<p class="text-xs text-text-muted">لا توجد دفعات.</p>@endforelse</div></div>
                                                </div>
                                                <div class="mt-3 flex justify-end gap-2"><a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="clinic-btn-secondary">PDF</a><a href="{{ route('invoices.show', $invoice) }}" class="text-xs font-semibold text-text-muted hover:text-primary">الصفحة الكاملة</a></div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="py-8 text-center text-text-muted">لا توجد فواتير لهذه الحالة.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endcan
                </section>
            @endif

            @can('view appointments')
                <section id="workspace-appointments" class="workspace-card scroll-mt-4 p-5 sm:p-6">
                    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center"><div><p class="text-xs font-semibold text-primary">المواعيد</p><h2 class="mt-1 text-xl font-bold text-text">جدول {{ $patient->name }}</h2></div><div class="flex gap-2">@can('create appointments')<button type="button" class="clinic-btn-primary" @click="panel = panel === 'appointment' ? null : 'appointment'">حجز موعد</button>@endcan<a href="{{ route('appointments.index', ['patient_id' => $patient->id]) }}" class="clinic-btn-secondary">الجدول العام</a></div></div>
                    @can('create appointments')<div id="workspace-appointment" class="mt-5 scroll-mt-4 rounded-lg border border-surface-border p-4" x-show="panel === 'appointment'" x-cloak>@include('appointments._service-plan-form', ['workspaceMode' => true, 'patient' => $patient, 'servicePlanItems' => $workspace['bookingOptions'], 'formAction' => URL::signedRoute('appointments.store', ['workspace_patient' => $patient->id])])</div>@endcan
                    <div class="mt-5 divide-y divide-surface-border rounded-lg border border-surface-border px-4">@forelse($workspace['patientAppointments'] as $appointment)<div class="flex flex-col justify-between gap-3 py-4 sm:flex-row sm:items-center"><div><p class="font-semibold text-text">{{ $appointment->patientServicePlanItem?->service?->name ?? $appointment->sessionType?->name ?? 'خدمة غير محددة' }}</p><p class="mt-1 text-xs text-text-muted">{{ $appointment->scheduled_at->format('Y-m-d H:i') }} · {{ $appointment->therapist?->name ?? 'أخصائي غير محدد' }}</p></div><div class="flex flex-wrap items-center gap-2"><span class="rounded-full border border-surface-border px-2 py-1 text-xs text-text-muted">{{ $appointment->status }}</span>@if(! $appointment->patient_service_plan_item_id && $appointment->legacy_booking_reason)<span class="rounded-full bg-warning-soft px-2 py-1 text-xs text-warning">حجز استثنائي</span>@elseif($appointment->financially_confirmed_at)<span class="rounded-full bg-success-soft px-2 py-1 text-xs text-success">مؤكد ماليًا</span>@endif</div></div>@empty<p class="py-8 text-center text-sm text-text-muted">لا توجد مواعيد مسجلة.</p>@endforelse</div>
                </section>
            @endcan

            <section class="grid gap-4 xl:grid-cols-3">
                @can('view therapy')
                    <article class="workspace-card p-5 xl:col-span-2"><h2 class="text-lg font-bold text-text">البرامج العلاجية</h2><div class="mt-4 divide-y divide-surface-border">@forelse($patient->therapyPrograms as $program)<div class="flex flex-col justify-between gap-2 py-3 first:pt-0 sm:flex-row sm:items-center"><div>@can('view', $program)<a href="{{ route('programs.show', $program) }}" class="clinic-entity-link font-semibold">{{ $program->name }}</a>@else<span class="font-semibold text-text">{{ $program->name }}</span>@endcan<p class="mt-1 text-xs text-text-muted">{{ $program->disorder_type ?: 'نوع الاضطراب غير محدد' }} · {{ $program->therapist?->name ?? 'أخصائي غير محدد' }}</p></div><div class="text-xs text-text-muted">{{ $program->status }} · {{ $program->sessions_count }} جلسة · {{ $program->attachments_count }} مرفق</div></div>@empty<p class="py-8 text-center text-sm text-text-muted">لا توجد برامج علاجية مسجلة.</p>@endforelse</div></article>
                @endcan
                <article class="workspace-card p-5"><h2 class="text-lg font-bold text-text">ولي الأمر والتواصل</h2>@if($patient->guardian)<dl class="mt-4 space-y-3 text-sm"><div><dt class="text-text-muted">الاسم</dt><dd class="mt-1"><a href="{{ route('guardians.show', $patient->guardian) }}" class="clinic-entity-link font-semibold">{{ $patient->guardian->name }}</a></dd></div><div><dt class="text-text-muted">الهاتف</dt><dd class="mt-1 font-semibold text-text" dir="ltr">{{ $patient->guardian->phone ?: 'غير مسجل' }}</dd></div>@if($patient->guardian->phone2)<div><dt class="text-text-muted">هاتف آخر</dt><dd class="mt-1 text-text" dir="ltr">{{ $patient->guardian->phone2 }}</dd></div>@endif @if($patient->guardian->email)<div><dt class="text-text-muted">البريد</dt><dd class="mt-1 break-all text-text">{{ $patient->guardian->email }}</dd></div>@endif @if($patient->guardian->address)<div><dt class="text-text-muted">العنوان</dt><dd class="mt-1 text-text">{{ $patient->guardian->address }}</dd></div>@endif</dl>@else<p class="mt-4 text-sm text-text-muted">لا توجد بيانات ولي أمر مرتبطة.</p>@endif</article>
            </section>

            <section class="workspace-card p-5"><h2 class="text-lg font-bold text-text">النشاط الأخير</h2><div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">@forelse($workspace['recentActivity'] as $activity)<div class="flex gap-3"><span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full {{ $activity['type'] === 'payment' ? 'bg-success' : ($activity['type'] === 'appointment' ? 'bg-primary' : 'bg-warning') }}"></span><div class="min-w-0 flex-1"><p class="truncate text-sm font-medium text-text">{{ $activity['title'] }}</p><div class="mt-1 flex justify-between gap-3 text-[11px] text-text-subtle"><span>{{ $activity['detail'] }}</span><time>{{ $activity['at']->diffForHumans() }}</time></div></div></div>@empty<p class="py-8 text-center text-sm text-text-muted">لا يوجد نشاط مسجل بعد.</p>@endforelse</div></section>
        </div>
    </div>
</x-app-layout>
