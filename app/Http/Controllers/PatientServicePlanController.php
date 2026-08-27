<?php

namespace App\Http\Controllers;

use App\Models\InvoicePayment;
use App\Models\Patient;
use App\Models\PatientServicePlan;
use App\Models\Service;
use App\Services\PatientServicePlanAllocator;
use App\Services\PatientServicePlanPricingService;
use App\Support\PatientWorkspaceContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PatientServicePlanController extends Controller
{
    public function __construct(private readonly PatientServicePlanPricingService $pricingService) {}

    public function index(Patient $patient)
    {
        $plans = $patient->servicePlans()
            ->with('items')
            ->withCount('items')
            ->latest()
            ->get();

        return view('patients.service-plans.index', compact('patient', 'plans'));
    }

    public function create(Patient $patient)
    {
        $services = $this->availableServices();
        $formItems = old('items', [[
            'id' => null,
            'service_id' => '',
            'position' => 1,
            'planned_quantity' => 1,
            'discount_amount' => '0.00',
        ]]);

        return view('patients.service-plans.create', compact('patient', 'services', 'formItems'));
    }

    public function store(Request $request, Patient $patient)
    {
        $fromWorkspace = PatientWorkspaceContext::validate($request, $patient->id);

        $data = $request->validate([
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('services', 'id')->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->whereNull('deleted_at')),
            ],
            'items.*.position' => ['required', 'integer', 'min:1', 'distinct'],
            'items.*.planned_quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'items.*.discount_amount' => ['nullable', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
        ]);

        $plan = DB::transaction(function () use ($data, $patient, $request) {
            $preparedItems = $this->pricingService->prepareNewItems(
                $data['items'],
                $request->user()->can('manage patient discounts')
            );
            $plan = $patient->servicePlans()->create([
                'status' => PatientServicePlan::STATUS_DRAFT,
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            foreach ($preparedItems as $item) {
                $plan->items()->create($item);
            }

            return $plan;
        });

        if ($fromWorkspace) {
            return redirect()->route('patients.workspace', ['patient' => $patient, 'section' => 'plan'])
                ->with('success', 'تم إنشاء خطة الخدمات كمسودة.');
        }

        return redirect()->route('patient-service-plans.show', $plan)
            ->with('success', 'تم إنشاء خطة الخدمات كمسودة.');
    }

    public function show(PatientServicePlan $patientServicePlan)
    {
        $patientServicePlan->load([
            'patient.guardian',
            'items.service.specialty',
            'planPayments.invoicePayment.invoice',
            'planPayments.allocations.item.service',
        ]);

        $availablePayments = InvoicePayment::query()
            ->with('invoice')
            ->whereHas('invoice', fn ($query) => $query->where('patient_id', $patientServicePlan->patient_id))
            ->whereDoesntHave('servicePlanPayment')
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        return view('patients.service-plans.show', compact('patientServicePlan', 'availablePayments'));
    }

    public function edit(Request $request, PatientServicePlan $patientServicePlan)
    {
        $fromWorkspace = PatientWorkspaceContext::validate($request, (int) $patientServicePlan->patient_id);
        $patientServicePlan->load('patient', 'items.service.specialty');
        $canFullyEdit = $patientServicePlan->canFullyEdit();
        $services = $this->availableServices($patientServicePlan);
        $formItems = old('items', $patientServicePlan->items->map(fn ($item) => [
            'id' => $item->id,
            'service_id' => $item->service_id,
            'position' => $item->position,
            'planned_quantity' => $item->planned_quantity,
            'customer_unit_price' => $item->customer_unit_price,
            'original_service_id' => $item->service_id,
            'discount_amount' => $item->discount_amount,
        ])->values()->all());

        return view('patients.service-plans.edit', compact(
            'patientServicePlan',
            'services',
            'formItems',
            'canFullyEdit',
            'fromWorkspace'
        ));
    }

    public function update(Request $request, PatientServicePlan $patientServicePlan)
    {
        $fromWorkspace = PatientWorkspaceContext::validate($request, (int) $patientServicePlan->patient_id);

        if ($patientServicePlan->isClinicallyApproved() && ! $patientServicePlan->hasProtectedHistory()) {
            return $this->updateClinicallyApprovedPlan($request, $patientServicePlan, $fromWorkspace);
        }

        if (! $patientServicePlan->canFullyEdit()) {
            $this->rejectProtectedStructureChanges($request);
            $data = $request->validate(['notes' => ['nullable', 'string', 'max:3000']]);
            $patientServicePlan->update(['notes' => $data['notes'] ?? null]);

            return redirect()->route(
                $fromWorkspace ? 'patients.workspace' : 'patient-service-plans.show',
                $fromWorkspace
                    ? ['patient' => $patientServicePlan->patient_id, 'section' => 'plan']
                    : $patientServicePlan
            )
                ->with('success', 'تم تحديث ملاحظات الخطة.');
        }

        $data = $request->validate($this->planRules($patientServicePlan));

        DB::transaction(function () use ($data, $patientServicePlan, $request) {
            $lockedPlan = PatientServicePlan::query()->lockForUpdate()->findOrFail($patientServicePlan->id);

            if (! $lockedPlan->canFullyEdit()) {
                throw ValidationException::withMessages([
                    'plan' => 'لا يمكن تعديل بنود أو أسعار الخطة بعد تسجيل دفعة أو استخدام خدمة. يمكنك تعديل الملاحظات فقط.',
                ]);
            }

            $preparedItems = $this->pricingService->prepareExistingItems(
                $lockedPlan,
                $data['items'],
                $request->user()->can('manage patient discounts')
            );

            $lockedPlan->update([
                'starts_at' => array_key_exists('starts_at', $data) ? $data['starts_at'] : $lockedPlan->starts_at,
                'ends_at' => array_key_exists('ends_at', $data) ? $data['ends_at'] : $lockedPlan->ends_at,
                'notes' => $data['notes'] ?? null,
            ]);
            $lockedPlan->items()->delete();

            foreach ($preparedItems as $item) {
                $lockedPlan->items()->create($item);
            }
        });

        return redirect()->route(
            $fromWorkspace ? 'patients.workspace' : 'patient-service-plans.show',
            $fromWorkspace
                ? ['patient' => $patientServicePlan->patient_id, 'section' => 'plan']
                : $patientServicePlan
        )
            ->with('success', 'تم تحديث خطة الخدمات بنجاح.');
    }

    public function destroy(PatientServicePlan $patientServicePlan)
    {
        $patient = $patientServicePlan->patient;

        DB::transaction(function () use ($patientServicePlan) {
            $lockedPlan = PatientServicePlan::query()->lockForUpdate()->findOrFail($patientServicePlan->id);

            if (! $lockedPlan->canDelete()) {
                throw ValidationException::withMessages([
                    'plan' => 'يمكن حذف المسودة غير المستخدمة وغير المعتمدة سريريًا فقط.',
                ]);
            }

            $lockedPlan->items()->delete();
            $lockedPlan->delete();
        });

        return redirect()->route('patients.service-plans.index', $patient)
            ->with('success', 'تم حذف مسودة الخطة بنجاح.');
    }

    public function activate(Request $request, PatientServicePlan $patientServicePlan)
    {
        $fromWorkspace = PatientWorkspaceContext::validate($request, (int) $patientServicePlan->patient_id);

        DB::transaction(function () use ($patientServicePlan) {
            $lockedPlan = PatientServicePlan::query()->lockForUpdate()->findOrFail($patientServicePlan->id);

            if ($lockedPlan->status !== PatientServicePlan::STATUS_DRAFT || ! $lockedPlan->items()->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'لا يمكن تفعيل هذه الخطة.',
                ]);
            }

            if ($lockedPlan->clinical_evaluation_id !== null && ! $lockedPlan->isClinicallyApproved()) {
                throw ValidationException::withMessages([
                    'status' => 'يجب اعتماد الخطة سريريًا وتسليمها للاستقبال قبل تفعيلها.',
                ]);
            }

            $lockedPlan->update(['status' => PatientServicePlan::STATUS_ACTIVE]);
        });

        if ($fromWorkspace) {
            return redirect()->route('patients.workspace', [
                'patient' => $patientServicePlan->patient_id,
                'section' => 'plan',
            ])
                ->with('success', 'تم تفعيل خطة الخدمات.');
        }

        return back()->with('success', 'تم تفعيل خطة الخدمات.');
    }

    public function allocatePayment(
        Request $request,
        PatientServicePlan $patientServicePlan,
        PatientServicePlanAllocator $allocator
    ) {
        $fromWorkspace = PatientWorkspaceContext::validate($request, (int) $patientServicePlan->patient_id);

        $data = $request->validate([
            'invoice_payment_id' => ['required', 'integer', 'exists:invoice_payments,id'],
        ]);

        $allocator->allocate(
            $patientServicePlan,
            InvoicePayment::findOrFail($data['invoice_payment_id']),
            $request->user()->id
        );

        if ($fromWorkspace) {
            return redirect()->route('patients.workspace', [
                'patient' => $patientServicePlan->patient_id,
                'section' => 'plan',
            ])
                ->with('success', 'تم تخصيص الدفعة على خدمات الخطة بالترتيب.');
        }

        return back()->with('success', 'تم تخصيص الدفعة على خدمات الخطة بالترتيب.');
    }

    private function planRules(PatientServicePlan $plan): array
    {
        $existingServiceIds = $plan->items()->pluck('service_id')->all();

        return [
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.service_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('services', 'id')->where(fn ($query) => $query
                    ->whereNull('deleted_at')
                    ->where(function ($serviceQuery) use ($existingServiceIds) {
                        $serviceQuery->where('is_active', true)
                            ->orWhereIn('id', $existingServiceIds);
                    })),
            ],
            'items.*.position' => ['required', 'integer', 'min:1', 'distinct'],
            'items.*.planned_quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'items.*.discount_amount' => ['nullable', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
        ];
    }

    private function rejectProtectedStructureChanges(Request $request): void
    {
        if ($request->hasAny(['starts_at', 'ends_at', 'items'])) {
            throw ValidationException::withMessages([
                'plan' => 'لا يمكن تعديل بنود أو أسعار الخطة بعد تسجيل دفعة أو استخدام خدمة. يمكنك تعديل الملاحظات فقط.',
            ]);
        }
    }

    private function updateClinicallyApprovedPlan(
        Request $request,
        PatientServicePlan $patientServicePlan,
        bool $fromWorkspace
    ) {
        $data = $request->validate($this->planRules($patientServicePlan));

        DB::transaction(function () use ($data, $patientServicePlan, $request) {
            $lockedPlan = PatientServicePlan::query()->lockForUpdate()->findOrFail($patientServicePlan->id);

            if (! $lockedPlan->isClinicallyApproved() || $lockedPlan->hasProtectedHistory()) {
                throw ValidationException::withMessages([
                    'plan' => 'لم تعد الشروط المالية لهذه الخطة قابلة للتعديل.',
                ]);
            }

            $this->assertApprovedClinicalStructureUnchanged($lockedPlan, $data['items']);
            $preparedItems = $this->pricingService->prepareExistingItems(
                $lockedPlan,
                $data['items'],
                $request->user()->can('manage patient discounts')
            );

            $lockedPlan->update([
                'starts_at' => array_key_exists('starts_at', $data) ? $data['starts_at'] : $lockedPlan->starts_at,
                'ends_at' => array_key_exists('ends_at', $data) ? $data['ends_at'] : $lockedPlan->ends_at,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($preparedItems as $item) {
                $lockedPlan->items()
                    ->where('position', $item['position'])
                    ->update([
                        'discount_amount' => $item['discount_amount'],
                        'final_unit_price' => $item['final_unit_price'],
                    ]);
            }
        });

        return redirect()->route(
            $fromWorkspace ? 'patients.workspace' : 'patient-service-plans.show',
            $fromWorkspace
                ? ['patient' => $patientServicePlan->patient_id, 'section' => 'plan']
                : $patientServicePlan
        )->with('success', 'تم تحديث الشروط المالية مع الحفاظ على الخطة السريرية المعتمدة.');
    }

    private function assertApprovedClinicalStructureUnchanged(PatientServicePlan $plan, array $submittedItems): void
    {
        $existing = $plan->items()->orderBy('position')->get()->values();
        $submitted = collect($submittedItems)->sortBy('position')->values();

        $changed = $existing->count() !== $submitted->count()
            || $existing->contains(function ($item, int $index) use ($submitted) {
                $submittedItem = $submitted->get($index);

                return ! $submittedItem
                    || (int) ($submittedItem['id'] ?? 0) !== $item->id
                    || (int) $submittedItem['service_id'] !== $item->service_id
                    || (int) $submittedItem['planned_quantity'] !== $item->planned_quantity
                    || (int) $submittedItem['position'] !== $item->position;
            });

        if ($changed) {
            throw ValidationException::withMessages([
                'plan' => 'لا يمكن تغيير الخدمات أو الكميات أو ترتيب الخطة بعد اعتمادها سريريًا.',
            ]);
        }
    }

    private function availableServices(?PatientServicePlan $plan = null)
    {
        $existingServiceIds = $plan?->items()->pluck('service_id')->all() ?? [];

        return Service::query()
            ->with('specialty')
            ->where(function ($query) use ($existingServiceIds) {
                $query->where('is_active', true);

                if ($existingServiceIds !== []) {
                    $query->orWhereIn('id', $existingServiceIds);
                }
            })
            ->orderBy('name')
            ->get();
    }
}
