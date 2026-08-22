<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Patient;
use App\Models\PatientServicePlan;
use App\Models\PatientServicePlanItem;
use App\Models\Service;
use App\Models\Therapist;
use App\Models\User;
use Illuminate\Support\Collection;

class PatientWorkspaceBuilder
{
    public function __construct(
        private readonly AppointmentServicePlanBookingService $bookingService
    ) {}

    public function build(Patient $patient, User $user): array
    {
        $patient->load([
            'guardian',
            'therapyPrograms' => fn ($query) => $query
                ->with('therapist')
                ->withCount(['sessions', 'attachments'])
                ->latest(),
        ]);

        $activePlan = $patient->servicePlans()
            ->where('status', PatientServicePlan::STATUS_ACTIVE)
            ->with([
                'items.service.specialty',
                'items.service.therapists' => fn ($query) => $query
                    ->where('therapists.is_active', true)
                    ->whereNotNull('therapists.user_id'),
                'planPayments.invoicePayment.invoice',
                'planPayments.allocations.item',
            ])
            ->latest('id')
            ->first();

        $currentPlan = $activePlan ?: $patient->servicePlans()
            ->where('status', PatientServicePlan::STATUS_DRAFT)
            ->with([
                'items.service.specialty',
                'items.service.therapists' => fn ($query) => $query
                    ->where('therapists.is_active', true)
                    ->whereNotNull('therapists.user_id'),
                'planPayments.invoicePayment.invoice',
                'planPayments.allocations.item',
            ])
            ->latest('id')
            ->first();

        $upcomingAppointments = $patient->appointments()
            ->with(['therapist', 'sessionType', 'patientServicePlanItem.service'])
            ->where('scheduled_at', '>=', now())
            ->where('status', '!=', 'ملغى')
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        $patientAppointments = $patient->appointments()
            ->with(['therapist', 'sessionType', 'patientServicePlanItem.service'])
            ->latest('scheduled_at')
            ->limit(10)
            ->get();

        $invoices = Invoice::query()
            ->with(['items', 'payments'])
            ->where('patient_id', $patient->id)
            ->latest('issue_date')
            ->latest('id')
            ->limit(10)
            ->get()
            ->each(function (Invoice $invoice) {
                $paidCents = $invoice->payments->sum(
                    fn ($payment) => PatientServicePlanAllocator::decimalToCents((string) $payment->amount)
                );
                $totalCents = PatientServicePlanAllocator::decimalToCents((string) $invoice->total);

                $invoice->setAttribute('paid_amount', PatientServicePlanAllocator::centsToDecimal($paidCents));
                $invoice->setAttribute('remaining_amount', PatientServicePlanAllocator::centsToDecimal(
                    max(0, $totalCents - $paidCents)
                ));
            });

        $payableInvoices = Invoice::query()
            ->select(['id', 'patient_id', 'invoice_number', 'issue_date', 'status', 'total'])
            ->withSum('payments', 'amount')
            ->where('patient_id', $patient->id)
            ->whereNotIn('status', ['مدفوعة', 'ملغاة'])
            ->latest('issue_date')
            ->latest('id')
            ->get()
            ->each(function (Invoice $invoice) {
                $paidCents = PatientServicePlanAllocator::decimalToCents(
                    (string) ($invoice->payments_sum_amount ?? 0)
                );
                $totalCents = PatientServicePlanAllocator::decimalToCents((string) $invoice->total);

                $invoice->setAttribute('remaining_amount', PatientServicePlanAllocator::centsToDecimal(
                    max(0, $totalCents - $paidCents)
                ));
            });

        $latestOutstandingInvoice = Invoice::query()
            ->where('patient_id', $patient->id)
            ->whereNotIn('status', ['مدفوعة', 'ملغاة'])
            ->latest('id')
            ->first();

        $unallocatedPayments = InvoicePayment::query()
            ->with('invoice')
            ->whereHas('invoice', fn ($query) => $query->where('patient_id', $patient->id))
            ->whereDoesntHave('servicePlanPayment')
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        $plan = $this->planSummary($currentPlan);
        $bookingOptions = $this->bookingOptions($activePlan, $user);
        $financial = $this->financialSummary($plan, $unallocatedPayments);
        $invoiceFinancial = $this->invoiceFinancialSummary($patient);

        $services = $user->can('manage patient service plans')
            ? Service::query()
                ->with('specialty')
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
            : collect();

        $invoicePlanItems = $user->can('manage invoices')
            ? PatientServicePlanItem::query()
                ->with(['plan:id,patient_id,status', 'service:id,specialty_id,name', 'service.specialty:id,name'])
                ->whereHas('plan', fn ($query) => $query
                    ->where('patient_id', $patient->id)
                    ->where('status', PatientServicePlan::STATUS_ACTIVE))
                ->where('final_unit_price', '>', 0)
                ->latest('id')
                ->get()
            : collect();

        return [
            'activePlan' => $activePlan,
            'currentPlan' => $currentPlan,
            'plan' => $plan,
            'financial' => $financial,
            'invoiceFinancial' => $invoiceFinancial,
            'invoices' => $invoices,
            'payableInvoices' => $payableInvoices,
            'unallocatedPayments' => $unallocatedPayments,
            'alerts' => $this->alerts($currentPlan, $plan, $financial, $bookingOptions, $upcomingAppointments),
            'recommendation' => $this->recommendation(
                $patient,
                $user,
                $currentPlan,
                $financial,
                $bookingOptions,
                $upcomingAppointments,
                $latestOutstandingInvoice
            ),
            'workflow' => $this->workflow($patient, $currentPlan, $plan),
            'bookingOptions' => $bookingOptions,
            'upcomingAppointments' => $upcomingAppointments,
            'patientAppointments' => $patientAppointments,
            'services' => $services,
            'invoicePlanItems' => $invoicePlanItems,
            'recentActivity' => $this->recentActivity($patient),
            'latestOutstandingInvoice' => $latestOutstandingInvoice,
        ];
    }

    private function planSummary(?PatientServicePlan $plan): array
    {
        if (! $plan) {
            return [
                'planned' => 0,
                'authorized' => 0,
                'consumed' => 0,
                'remaining_executable' => 0,
                'unfunded' => 0,
                'total_cents' => 0,
                'paid_cents' => 0,
                'allocated_cents' => 0,
                'available_credit_cents' => 0,
                'remaining_amount_cents' => 0,
                'progress_percentage' => 0,
            ];
        }

        $planned = $plan->items->sum('planned_quantity');
        $authorized = $plan->items->sum('authorized_quantity');
        $consumed = $plan->items->sum('consumed_quantity');
        $totalCents = $plan->items->sum(fn ($item) => PatientServicePlanAllocator::decimalToCents($item->final_unit_price) * $item->planned_quantity
        );
        $paidCents = $plan->planPayments->sum(fn ($payment) => PatientServicePlanAllocator::decimalToCents($payment->amount_snapshot)
        );
        $allocatedCents = $plan->planPayments
            ->flatMap->allocations
            ->sum(fn ($allocation) => PatientServicePlanAllocator::decimalToCents($allocation->allocated_amount));

        return [
            'planned' => $planned,
            'authorized' => $authorized,
            'consumed' => $consumed,
            'remaining_executable' => $plan->items->sum(fn ($item) => $item->remainingAuthorizedQuantity()),
            'unfunded' => $plan->items->sum(fn ($item) => $item->unpaidQuantity()),
            'total_cents' => $totalCents,
            'paid_cents' => $paidCents,
            'allocated_cents' => $allocatedCents,
            'available_credit_cents' => max(0, $paidCents - $allocatedCents),
            'remaining_amount_cents' => max(0, $totalCents - $paidCents),
            'progress_percentage' => $planned > 0 ? min(100, (int) round(($consumed / $planned) * 100)) : 0,
        ];
    }

    private function bookingOptions(?PatientServicePlan $plan, User $user): Collection
    {
        if (! $plan) {
            return collect();
        }

        return $plan->items
            ->filter(fn ($item) => $item->consumed_quantity < $item->planned_quantity)
            ->map(function ($item) use ($user) {
                $eligibility = $this->bookingService->eligibility($item);
                $therapists = $item->service->therapists
                    ->when($user->hasRole('أخصائي تخاطب'), fn ($items) => $items->where('user_id', $user->id))
                    ->map(fn (Therapist $therapist) => [
                        'user_id' => $therapist->user_id,
                        'name' => $therapist->name,
                    ])
                    ->values();
                $hasTherapist = $therapists->isNotEmpty();

                return [
                    'item_id' => $item->id,
                    'id' => $item->id,
                    'patient_id' => $item->plan->patient_id,
                    'service_name' => $item->service->name,
                    'specialty_name' => $item->service->specialty->name,
                    'final_unit_price' => $item->final_unit_price,
                    'required_deposit' => PatientServicePlanAllocator::centsToDecimal(
                        $eligibility['required_deposit_cents']
                    ),
                    'financially_eligible' => $eligibility['can_book'],
                    'has_eligible_therapist' => $hasTherapist,
                    'can_book' => $eligibility['can_book'] && $hasTherapist,
                    'funding_type' => $eligibility['funding_type'],
                    'state' => match (true) {
                        ! $eligibility['can_book'] => 'الرصيد غير كافٍ لتأكيد الموعد',
                        ! $hasTherapist => 'لا يوجد أخصائي مسند لهذه الخدمة',
                        $eligibility['funding_type'] === 'full' => 'مدفوعة بالكامل',
                        default => 'مقدم الحجز متاح',
                    },
                    'booking_state' => match (true) {
                        ! $eligibility['can_book'] => 'الرصيد غير كافٍ لتأكيد الموعد',
                        ! $hasTherapist => 'لا يوجد أخصائي مسند لهذه الخدمة',
                        $eligibility['funding_type'] === 'full' => 'مدفوعة بالكامل',
                        default => 'مقدم الحجز متاح',
                    },
                    'therapists' => $therapists,
                ];
            })
            ->values();
    }

    private function financialSummary(array $plan, Collection $unallocatedPayments): array
    {
        $unallocatedCents = $unallocatedPayments->sum(fn ($payment) => PatientServicePlanAllocator::decimalToCents($payment->amount)
        );

        return [
            'plan_total' => PatientServicePlanAllocator::centsToDecimal($plan['total_cents']),
            'paid' => PatientServicePlanAllocator::centsToDecimal($plan['paid_cents']),
            'allocated' => PatientServicePlanAllocator::centsToDecimal($plan['allocated_cents']),
            'available_credit' => PatientServicePlanAllocator::centsToDecimal($plan['available_credit_cents']),
            'remaining' => PatientServicePlanAllocator::centsToDecimal($plan['remaining_amount_cents']),
            'unallocated' => PatientServicePlanAllocator::centsToDecimal($unallocatedCents),
            'unallocated_cents' => $unallocatedCents,
        ];
    }

    private function invoiceFinancialSummary(Patient $patient): array
    {
        $invoiceTotal = Invoice::query()
            ->where('patient_id', $patient->id)
            ->where('status', '!=', 'ملغاة')
            ->sum('total');
        $paidTotal = InvoicePayment::query()
            ->whereHas('invoice', fn ($query) => $query
                ->where('patient_id', $patient->id)
                ->where('status', '!=', 'ملغاة'))
            ->sum('amount');

        $invoiceCents = PatientServicePlanAllocator::decimalToCents((string) $invoiceTotal);
        $paidCents = PatientServicePlanAllocator::decimalToCents((string) $paidTotal);

        return [
            'total_invoiced' => PatientServicePlanAllocator::centsToDecimal($invoiceCents),
            'total_paid' => PatientServicePlanAllocator::centsToDecimal($paidCents),
            'outstanding' => PatientServicePlanAllocator::centsToDecimal(max(0, $invoiceCents - $paidCents)),
        ];
    }

    private function workflow(Patient $patient, ?PatientServicePlan $plan, array $summary): array
    {
        $hasBooking = Appointment::query()
            ->where('patient_id', $patient->id)
            ->whereNotNull('patient_service_plan_item_id')
            ->exists();

        return [
            ['label' => 'الخطة', 'complete' => (bool) $plan],
            ['label' => 'الدفع', 'complete' => $summary['paid_cents'] > 0],
            ['label' => 'التخصيص', 'complete' => $summary['allocated_cents'] > 0],
            ['label' => 'الحجز', 'complete' => $hasBooking],
            // Reception V2 and Completion V2 do not yet prove these stages belong to the active plan.
            ['label' => 'الحضور', 'complete' => false],
            ['label' => 'الجلسة', 'complete' => false],
        ];
    }

    private function alerts(
        ?PatientServicePlan $plan,
        array $summary,
        array $financial,
        Collection $bookingOptions,
        Collection $upcomingAppointments
    ): Collection {
        $alerts = collect();

        if (! $plan) {
            $alerts->push(['tone' => 'warning', 'text' => 'لا توجد خطة خدمات نشطة لهذه الحالة.']);
        } elseif ($plan->items->isEmpty()) {
            $alerts->push(['tone' => 'warning', 'text' => 'الخطة النشطة لا تحتوي خدمات بعد.']);
        }

        if ($financial['unallocated_cents'] > 0) {
            $alerts->push(['tone' => 'warning', 'text' => 'توجد دفعة غير مخصصة تحتاج إلى ربطها بخطة الخدمات.']);
        }

        if ($bookingOptions->contains(fn ($option) => $option['financially_eligible'] && ! $option['has_eligible_therapist'])) {
            $alerts->push(['tone' => 'warning', 'text' => 'توجد خدمة ممولة بلا أخصائي مسند.']);
        } elseif ($bookingOptions->contains(fn ($option) => $option['can_book'])) {
            $alerts->push(['tone' => 'success', 'text' => 'توجد خدمة جاهزة لحجز موعد مؤكد ماليًا.']);
        } elseif ($plan && $summary['unfunded'] > 0) {
            $alerts->push(['tone' => 'danger', 'text' => 'الرصيد الحالي غير كافٍ للانتقال إلى الحجز التالي.']);
        }

        if ($upcomingAppointments->isNotEmpty()) {
            $alerts->push(['tone' => 'primary', 'text' => 'يوجد موعد قادم يحتاج إلى المتابعة.']);
        }

        return $alerts->take(4);
    }

    private function recommendation(
        Patient $patient,
        User $user,
        ?PatientServicePlan $plan,
        array $financial,
        Collection $bookingOptions,
        Collection $upcomingAppointments,
        ?Invoice $outstandingInvoice
    ): array {
        if (! $plan) {
            return $user->can('manage patient service plans')
                ? $this->action('إنشاء خطة خدمات', 'ابدأ بتحديد الخدمات والكميات المطلوبة للحالة.', route('patients.service-plans.create', $patient), 'plan')
                : $this->action('مراجعة بيانات الحالة', 'لا توجد خطة نشطة، ويجب أن ينشئها مستخدم مخوّل.', route('patients.show', $patient));
        }

        if ($plan->status === PatientServicePlan::STATUS_ACTIVE
            && $financial['unallocated_cents'] > 0
            && $user->can('manage patient service plans')) {
            return $this->action('تخصيص الدفعة', 'اربط الدفعة المتاحة بالخدمات حسب ترتيب الخطة.', route('patient-service-plans.show', $plan), 'allocation');
        }

        if ($bookingOptions->contains(fn ($option) => $option['can_book'])
            && $user->can('view appointments')
            && $user->can('create appointments')) {
            return $this->action('حجز موعد', 'الخدمة والتغطية المالية والأخصائي متاحون للحجز.', route('appointments.index', ['patient_id' => $patient->id]), 'appointment');
        }

        if ($financial['remaining'] !== '0.00' && $user->can('manage invoices')) {
            $url = $outstandingInvoice && $user->can('view finance')
                ? route('invoices.show', $outstandingInvoice)
                : route('invoices.create', ['patient_id' => $patient->id, 'workspace' => 1]);

            return $this->action('استكمال التغطية المالية', 'الخطة تحتاج إلى دفعة إضافية قبل التقدم.', $url, $outstandingInvoice ? 'payment' : 'invoice');
        }

        if ($upcomingAppointments->isNotEmpty() && $user->can('view appointments')) {
            return $this->action('عرض الموعد القادم', 'راجع تفاصيل أقرب موعد مسجل للحالة.', route('appointments.index', [
                'patient_id' => $patient->id,
                'date' => $upcomingAppointments->first()->scheduled_at->toDateString(),
            ]), 'appointments');
        }

        return $user->can('manage patient service plans')
            ? $this->action('مراجعة خطة الخدمات', 'راجع تقدم الخدمات والرصيد الحالي للحالة.', route('patient-service-plans.show', $plan), 'plan')
            : $this->action('متابعة الحالة', 'لا توجد خطوة تشغيلية متاحة ضمن صلاحياتك الحالية.', route('patients.show', $patient));
    }

    private function action(string $title, string $description, string $url, ?string $panel = null): array
    {
        return compact('title', 'description', 'url', 'panel');
    }

    private function recentActivity(Patient $patient): Collection
    {
        $payments = InvoicePayment::query()
            ->with('invoice')
            ->whereHas('invoice', fn ($query) => $query->where('patient_id', $patient->id))
            ->latest('created_at')
            ->limit(4)
            ->get()
            ->map(fn ($payment) => [
                'type' => 'payment',
                'title' => 'تسجيل دفعة بقيمة '.number_format((float) $payment->amount, 2).' ج.م',
                'detail' => $payment->invoice->invoice_number,
                'at' => $payment->created_at,
            ]);

        $appointments = $patient->appointments()
            ->latest('created_at')
            ->limit(4)
            ->get()
            ->map(fn ($appointment) => [
                'type' => 'appointment',
                'title' => 'حجز موعد جديد',
                'detail' => $appointment->scheduled_at->format('Y-m-d H:i'),
                'at' => $appointment->created_at,
            ]);

        $plans = $patient->servicePlans()
            ->latest('created_at')
            ->limit(3)
            ->get()
            ->map(fn ($plan) => [
                'type' => 'plan',
                'title' => 'إنشاء خطة خدمات',
                'detail' => PatientServicePlan::STATUS_LABELS[$plan->status] ?? $plan->status,
                'at' => $plan->created_at,
            ]);

        return $payments->concat($appointments)->concat($plans)
            ->sortByDesc('at')
            ->take(6)
            ->values();
    }
}
