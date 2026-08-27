<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Patient;
use App\Models\PatientCheckin;
use App\Models\PatientServicePlan;
use App\Models\PatientServicePlanAllocation;
use App\Models\PatientServicePlanPayment;
use App\Models\Service;
use App\Models\SessionType;
use App\Models\Specialty;
use App\Models\Therapist;
use App\Models\TherapistWorkPeriod;
use App\Models\TherapyProgram;
use App\Models\TherapySession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PatientWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_open_patient_specific_workspace_without_data_leakage(): void
    {
        $user = $this->userWithPermissions(['view patients']);
        $patient = $this->patient('مالك كريم');
        $otherPatient = $this->patient('طفل آخر سري');

        $this->invoicePayment($otherPatient, '999.00');

        $this->actingAs($user)->get(route('patients.workspace', $patient))
            ->assertOk()
            ->assertSeeText('مالك كريم')
            ->assertSeeText('ملف الحالة الذكي')
            ->assertDontSeeText('طفل آخر سري')
            ->assertDontSeeText('999.00');
    }

    public function test_workspace_reports_real_plan_and_financial_aggregates(): void
    {
        $user = $this->userWithPermissions(['view patients', 'manage patient service plans']);
        $patient = $this->patient('حالة التجميع');
        [$plan, $item] = $this->activePlan($patient, planned: 4, authorized: 2, consumed: 1);
        $payment = $this->invoicePayment($patient, '250.00');
        $planPayment = PatientServicePlanPayment::create([
            'patient_service_plan_id' => $plan->id,
            'invoice_payment_id' => $payment->id,
            'amount_snapshot' => '250.00',
            'created_by' => $user->id,
        ]);
        PatientServicePlanAllocation::create([
            'patient_service_plan_payment_id' => $planPayment->id,
            'patient_service_plan_item_id' => $item->id,
            'allocated_amount' => '200.00',
            'authorized_quantity' => 2,
        ]);
        $this->invoicePayment($patient, '30.00');

        $this->actingAs($user)->get(route('patients.workspace', $patient))
            ->assertOk()
            ->assertViewHas('workspace', function (array $workspace) {
                return $workspace['plan']['planned'] === 4
                    && $workspace['plan']['authorized'] === 2
                    && $workspace['plan']['consumed'] === 1
                    && $workspace['plan']['remaining_executable'] === 1
                    && $workspace['plan']['unfunded'] === 2
                    && $workspace['financial']['plan_total'] === '400.00'
                    && $workspace['financial']['paid'] === '250.00'
                    && $workspace['financial']['allocated'] === '200.00'
                    && $workspace['financial']['available_credit'] === '50.00'
                    && $workspace['financial']['remaining'] === '150.00'
                    && $workspace['financial']['unallocated'] === '30.00';
            });
    }

    public function test_workspace_no_plan_empty_state_and_recommendation_are_truthful(): void
    {
        $user = $this->userWithPermissions(['view patients', 'manage patient service plans']);
        $patient = $this->patient('بلا خطة');

        $this->actingAs($user)->get(route('patients.workspace', $patient))
            ->assertOk()
            ->assertSeeText('لا توجد خطة نشطة')
            ->assertSeeText('بانتظار إسناد التقييم')
            ->assertDontSeeText('إنشاء خطة خدمات')
            ->assertDontSeeText('حفظ كمسودة');
    }

    public function test_upcoming_appointments_belong_only_to_workspace_patient(): void
    {
        $user = $this->userWithPermissions(['view patients', 'view appointments']);
        $patient = $this->patient('صاحب الموعد');
        $otherPatient = $this->patient('صاحب موعد آخر');
        $therapist = User::factory()->create(['name' => 'أخصائي الحالة']);
        [, $item] = $this->activePlan($patient);
        [, $otherItem] = $this->activePlan($otherPatient);

        $this->v2Appointment($patient, $item, $therapist, now()->addDays(2));
        $this->v2Appointment($otherPatient, $otherItem, $therapist, now()->addDays(3));

        $this->actingAs($user)->get(route('patients.workspace', $patient))
            ->assertOk()
            ->assertViewHas('workspace', fn (array $workspace) => $workspace['upcomingAppointments']->pluck('patient_id')->all() === [$patient->id]
            )
            ->assertSeeText('خدمة تخاطب')
            ->assertDontSeeText('صاحب موعد آخر');
    }

    public function test_quick_actions_preserve_patient_context_and_respect_permissions(): void
    {
        $patient = $this->patient('سياق ثابت');
        $authorized = $this->userWithPermissions([
            'view patients',
            'manage invoices',
            'manage checkins',
            'manage patient service plans',
            'view appointments',
            'create appointments',
        ]);
        $this->activePlan($patient);

        $this->actingAs($authorized)->get(route('patients.workspace', $patient))
            ->assertOk()
            ->assertSeeText('إنشاء فاتورة')
            ->assertSeeText('حجز موعد')
            ->assertSeeText('إدارة الخطة')
            ->assertSeeText('تأكيد الحضور')
            ->assertSeeText('سيتم تفعيله مع مسار الاستقبال الجديد')
            ->assertSee('data-workspace-open="finance:invoice"', false)
            ->assertSee('data-workspace-open="plan:plan"', false)
            ->assertSee('data-workspace-open="appointments:appointment"', false)
            ->assertDontSee('href="'.route('reception.index').'" class="workspace-action"', false);

        $limited = $this->userWithPermissions(['view patients']);
        $this->actingAs($limited)->get(route('patients.workspace', $patient))
            ->assertOk()
            ->assertDontSeeText('حفظ الفاتورة')
            ->assertDontSeeText('حجز وتأكيد الموعد')
            ->assertDontSeeText('حفظ كمسودة')
            ->assertDontSeeText('سيتم تفعيله مع مسار الاستقبال الجديد');

        $this->actingAs($limited)->post(URL::signedRoute('invoices.store', [
            'workspace_patient' => $patient->id,
        ]), [
            'workspace' => 1,
            'patient_id' => $patient->id,
            'issue_date' => now()->toDateString(),
            'items' => [['description' => 'محاولة غير مصرحة', 'quantity' => 1, 'unit_price' => 10]],
        ])->assertForbidden();
    }

    public function test_workspace_main_section_navigation_is_whitelisted_and_permission_aware(): void
    {
        $patient = $this->patient('حالة أقسام مساحة العمل');
        $this->activePlan($patient);
        $authorized = $this->userWithPermissions([
            'view patients',
            'manage patient service plans',
            'view finance',
            'manage invoices',
            'view appointments',
            'create appointments',
            'view therapy',
        ]);

        $this->actingAs($authorized)->get(route('patients.workspace', [
            'patient' => $patient,
            'section' => 'finance',
        ]))
            ->assertOk()
            ->assertSee('data-workspace-section-navigation', false)
            ->assertSee("initialSection: 'finance'", false)
            ->assertSee('data-workspace-section-tab="overview"', false)
            ->assertSee('data-workspace-section-tab="clinical"', false)
            ->assertSee('data-workspace-section-tab="plan"', false)
            ->assertSee('data-workspace-section-tab="finance"', false)
            ->assertSee('data-workspace-section-tab="appointments"', false)
            ->assertSee('data-workspace-section-tab="therapy"', false)
            ->assertSee('data-workspace-section-tab="guardian"', false)
            ->assertSee('data-workspace-section-tab="activity"', false)
            ->assertSee('data-workspace-dirty-indicator', false)
            ->assertSee('data-workspace-dirty-track', false);

        $this->actingAs($authorized)->get(route('patients.workspace', [
            'patient' => $patient,
            'section' => 'not-a-section',
        ]))
            ->assertOk()
            ->assertSee("initialSection: 'overview'", false);

        $limited = $this->userWithPermissions(['view patients']);
        $this->actingAs($limited)->get(route('patients.workspace', $patient))
            ->assertOk()
            ->assertSee('data-workspace-section-tab="overview"', false)
            ->assertSee('data-workspace-section-tab="clinical"', false)
            ->assertSee('data-workspace-section-tab="guardian"', false)
            ->assertSee('data-workspace-section-tab="activity"', false)
            ->assertDontSee('data-workspace-section-tab="plan"', false)
            ->assertDontSee('data-workspace-section-tab="finance"', false)
            ->assertDontSee('data-workspace-section-tab="appointments"', false)
            ->assertDontSee('data-workspace-section-tab="therapy"', false);
    }

    public function test_workspace_validation_panel_reopens_its_parent_section(): void
    {
        $user = $this->userWithPermissions(['view patients', 'manage invoices']);
        $patient = $this->patient('حالة رجوع التحقق');
        $this->activePlan($patient);
        $workspaceUrl = route('patients.workspace', ['patient' => $patient, 'section' => 'overview']);

        $response = $this->actingAs($user)->from($workspaceUrl)->post(URL::signedRoute('invoices.store', [
            'workspace_patient' => $patient->id,
        ]), [
            'workspace' => 1,
            'workspace_panel' => 'invoice',
            'workspace_section' => 'finance',
            'patient_id' => $patient->id,
            'issue_date' => now()->toDateString(),
            'invoice_item_mode' => 'manual',
            'items' => [],
        ]);

        $response->assertRedirect($workspaceUrl)->assertSessionHasErrors('items');
        $this->get($workspaceUrl)
            ->assertOk()
            ->assertSee("initialSection: 'finance'", false)
            ->assertSee("initialPanel: 'invoice'", false);
    }

    public function test_historical_attendance_and_sessions_do_not_complete_current_workflow_stages(): void
    {
        $user = $this->userWithPermissions(['view patients']);
        $patient = $this->patient('حالة بسجل قديم');
        $this->activePlan($patient);

        PatientCheckin::create([
            'patient_id' => $patient->id,
            'checkin_at' => now()->subMonths(2),
            'checked_by' => $user->id,
            'method' => 'manual',
        ]);

        $program = TherapyProgram::create([
            'name' => 'برنامج علاجي قديم',
            'patient_id' => $patient->id,
            'therapist_id' => $user->id,
            'status' => TherapyProgram::STATUS_COMPLETED,
            'start_date' => now()->subMonths(3)->toDateString(),
            'end_date' => now()->subMonths(2)->toDateString(),
        ]);

        TherapySession::create([
            'therapy_program_id' => $program->id,
            'session_date' => now()->subMonths(2)->toDateString(),
            'status' => 'مكتملة',
        ]);

        $this->actingAs($user)->get(route('patients.workspace', $patient))
            ->assertOk()
            ->assertViewHas('workspace', function (array $workspace) {
                $workflow = collect($workspace['workflow'])->keyBy('label');

                return $workflow['الحجز']['complete'] === false
                    && $workflow['المتابعة']['complete'] === false;
            });
    }

    public function test_entity_names_link_to_existing_detail_pages_for_authorized_users(): void
    {
        $user = $this->userWithPermissions([
            'view patients',
            'view finance',
            'manage patient service plans',
        ]);
        $patient = $this->patient('مريض برابط طبيعي');
        [$plan] = $this->activePlan($patient);
        $payment = $this->invoicePayment($patient, '175.00');

        $this->actingAs($user)->get(route('patients.index'))
            ->assertOk()
            ->assertSee('<a href="'.route('patients.workspace', $patient).'" class="clinic-entity-link">'.$patient->name.'</a>', false);

        $this->actingAs($user)->get(route('guardians.index'))
            ->assertOk()
            ->assertSee('<a href="'.route('guardians.show', $patient->guardian).'" class="clinic-entity-link">'.$patient->guardian->name.'</a>', false);

        $this->actingAs($user)->get(route('invoices.index'))
            ->assertOk()
            ->assertSee('<a href="'.route('invoices.show', $payment->invoice).'" class="clinic-entity-link">'.$payment->invoice->invoice_number.'</a>', false);

        $this->actingAs($user)->get(route('patients.service-plans.index', $patient))
            ->assertOk()
            ->assertSee('<a href="'.route('patient-service-plans.show', $plan).'" class="clinic-entity-link">#'.$plan->id.'</a>', false);
    }

    public function test_limited_user_does_not_receive_service_plan_detail_link(): void
    {
        $user = $this->userWithPermissions(['view patients']);
        $patient = $this->patient('حالة صلاحيات محدودة');
        [$plan] = $this->activePlan($patient);

        $this->actingAs($user)->get(route('patients.workspace', $patient))
            ->assertOk()
            ->assertSeeText('خطة #'.$plan->id)
            ->assertDontSee(route('patient-service-plans.show', $plan), false);
    }

    public function test_existing_invoice_and_appointment_forms_accept_safe_patient_context(): void
    {
        $user = $this->userWithPermissions([
            'view patients',
            'manage invoices',
            'view appointments',
            'create appointments',
        ]);
        $patient = $this->patient('مريض محدد مسبقًا');

        $this->actingAs($user)->get(route('invoices.create', [
            'patient_id' => $patient->id,
            'workspace' => 1,
        ]))
            ->assertOk()
            ->assertViewHas('selectedPatientId', $patient->id)
            ->assertSee('<input type="hidden" name="patient_id" value="'.$patient->id.'">', false);

        $this->actingAs($user)->get(route('appointments.index', ['patient_id' => $patient->id]))
            ->assertOk()
            ->assertViewHas('selectedPatientId', $patient->id);

        $this->actingAs($user)->post(URL::signedRoute('invoices.store', [
            'workspace_patient' => $patient->id,
        ]), [
            'patient_id' => $patient->id,
            'issue_date' => now()->toDateString(),
            'workspace' => 1,
            'items' => [[
                'description' => 'خدمة من مساحة العمل',
                'quantity' => 1,
                'unit_price' => '100.00',
            ]],
        ])->assertRedirect(route('patients.workspace', ['patient' => $patient, 'section' => 'finance']));

        $this->actingAs($user)->post(route('invoices.store'), [
            'patient_id' => $patient->id,
            'issue_date' => now()->toDateString(),
            'items' => [[
                'description' => 'فاتورة من المسار العام',
                'quantity' => 1,
                'unit_price' => '75.00',
            ]],
        ])->assertRedirect(route('invoices.index'));
    }

    public function test_workspace_can_create_and_activate_plan_with_existing_controller_rules(): void
    {
        $user = $this->userWithPermissions(['view patients', 'manage patient service plans']);
        $patient = $this->patient('حالة إنشاء الخطة');
        $specialty = Specialty::create(['name' => 'تخصص الخطة', 'is_active' => true]);
        $service = Service::create([
            'specialty_id' => $specialty->id,
            'name' => 'خدمة الخطة المضمنة',
            'default_duration_minutes' => 30,
            'customer_price' => '90.00',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(URL::signedRoute('patients.service-plans.store', [
            'patient' => $patient,
            'workspace_patient' => $patient->id,
        ]), [
            'workspace' => 1,
            'workspace_panel' => 'plan',
            'items' => [[
                'service_id' => $service->id,
                'position' => 1,
                'planned_quantity' => 3,
                'customer_unit_price' => '90.00',
                'discount_amount' => '0.00',
            ]],
        ])->assertRedirect(route('patients.workspace', ['patient' => $patient, 'section' => 'plan']));

        $plan = $patient->servicePlans()->sole();
        $this->assertSame(PatientServicePlan::STATUS_DRAFT, $plan->status);

        $this->actingAs($user)->post(URL::signedRoute('patient-service-plans.activate', [
            'patientServicePlan' => $plan,
            'workspace_patient' => $patient->id,
        ]), [
            'workspace' => 1,
            'workspace_panel' => 'plan',
        ])->assertRedirect(route('patients.workspace', ['patient' => $patient, 'section' => 'plan']));

        $this->assertSame(PatientServicePlan::STATUS_ACTIVE, $plan->fresh()->status);
    }

    public function test_legacy_draft_plan_remains_operational_and_is_not_awaiting_assignment(): void
    {
        $user = $this->userWithPermissions([
            'view patients',
            'manage patient service plans',
            'view finance',
            'manage invoices',
            'view appointments',
            'create appointments',
        ]);
        $legacyPatient = $this->patient('حالة بخطة قديمة');
        $newPatient = $this->patient('حالة جديدة فعلًا');
        $specialty = Specialty::create(['name' => 'تخصص خطة قديمة', 'is_active' => true]);
        $service = Service::create([
            'specialty_id' => $specialty->id,
            'name' => 'خدمة خطة قديمة',
            'default_duration_minutes' => 30,
            'customer_price' => '120.00',
            'is_active' => true,
        ]);
        $plan = PatientServicePlan::create([
            'patient_id' => $legacyPatient->id,
            'clinical_evaluation_id' => null,
            'status' => PatientServicePlan::STATUS_DRAFT,
        ]);
        $plan->items()->create([
            'service_id' => $service->id,
            'position' => 1,
            'planned_quantity' => 3,
            'customer_unit_price' => '120.00',
            'discount_amount' => '0.00',
            'final_unit_price' => '120.00',
        ]);

        $this->actingAs($user)->get(route('patients.workspace', $legacyPatient))
            ->assertOk()
            ->assertViewHas('workspace', fn (array $workspace) => $workspace['clinical']['key'] === 'legacy_plan'
                && $workspace['currentPlan']->is($plan))
            ->assertDontSeeText('بانتظار إسناد التقييم')
            ->assertSeeText('خدمة خطة قديمة')
            ->assertSeeText('تفعيل الخطة')
            ->assertSee('data-workspace-direct-action', false)
            ->assertSeeText('المالية والفواتير')
            ->assertSeeText('المواعيد');

        $this->actingAs($user)->get(route('patients.index', ['stage' => 'awaiting_assignment']))
            ->assertOk()
            ->assertSeeText($newPatient->name)
            ->assertDontSeeText($legacyPatient->name);

        $this->actingAs($user)->post(URL::signedRoute('patient-service-plans.activate', [
            'patientServicePlan' => $plan,
            'workspace_patient' => $legacyPatient->id,
        ]), [
            'workspace' => 1,
            'workspace_panel' => 'plan',
        ])->assertRedirect(route('patients.workspace', ['patient' => $legacyPatient, 'section' => 'plan']));

        $this->assertSame(PatientServicePlan::STATUS_ACTIVE, $plan->fresh()->status);
        $this->assertDatabaseCount('patient_clinical_evaluations', 0);
        $this->assertDatabaseCount('patient_clinical_evaluation_assignments', 0);
    }

    public function test_workspace_finance_lists_only_current_patient_invoices(): void
    {
        $user = $this->userWithPermissions(['view patients', 'view finance']);
        $patient = $this->patient('صاحب الفاتورة');
        $otherPatient = $this->patient('مريض مالي آخر');
        $invoice = $this->invoicePayment($patient, '120.00')->invoice;
        $otherInvoice = $this->invoicePayment($otherPatient, '880.00')->invoice;

        $this->actingAs($user)->get(route('patients.workspace', $patient))
            ->assertOk()
            ->assertSeeText($invoice->invoice_number)
            ->assertDontSeeText($otherInvoice->invoice_number)
            ->assertDontSeeText('880.00');
    }

    public function test_workspace_payment_reuses_invoice_rules_and_rejects_cross_patient_context(): void
    {
        $user = $this->userWithPermissions(['view patients', 'view finance', 'manage invoices']);
        $patient = $this->patient('حالة الدفع');
        $otherPatient = $this->patient('حالة دفع أخرى');
        $invoice = Invoice::create([
            'patient_id' => $patient->id,
            'invoice_number' => 'INV-PAY-'.uniqid(),
            'issue_date' => now()->toDateString(),
            'status' => 'غير مدفوعة',
            'total' => '100.00',
        ]);
        $otherInvoice = Invoice::create([
            'patient_id' => $otherPatient->id,
            'invoice_number' => 'INV-OTHER-'.uniqid(),
            'issue_date' => now()->toDateString(),
            'status' => 'غير مدفوعة',
            'total' => '100.00',
        ]);
        $payload = [
            'workspace' => 1,
            'workspace_panel' => 'payment',
            'amount' => '40.00',
            'payment_date' => now()->toDateString(),
            'method' => 'كاش',
        ];

        $this->actingAs($user)->post(URL::signedRoute('invoices.payments', [
            'invoice' => $invoice,
            'workspace_patient' => $patient->id,
        ]), $payload)->assertRedirect(route('patients.workspace', ['patient' => $patient, 'section' => 'finance']));

        $this->assertDatabaseHas('invoice_payments', ['invoice_id' => $invoice->id, 'amount' => 40]);

        $this->actingAs($user)->from(route('patients.workspace', $patient))->post(URL::signedRoute('invoices.payments', [
            'invoice' => $invoice,
            'workspace_patient' => $patient->id,
        ]), array_replace($payload, ['amount' => '70.00']))
            ->assertRedirect(route('patients.workspace', $patient))
            ->assertSessionHasErrors('amount');

        $this->actingAs($user)->post(URL::signedRoute('invoices.payments', [
            'invoice' => $otherInvoice,
            'workspace_patient' => $patient->id,
        ]), $payload)->assertForbidden();

        $this->assertDatabaseMissing('invoice_payments', ['invoice_id' => $otherInvoice->id]);
    }

    public function test_workspace_invoice_uses_patient_plan_snapshot_and_rejects_foreign_plan_item(): void
    {
        $user = $this->userWithPermissions(['view patients', 'view finance', 'manage invoices']);
        $patient = $this->patient('حالة فاتورة الخطة');
        $otherPatient = $this->patient('حالة خطة أخرى');
        [, $planItem] = $this->activePlan($patient);
        [$draftPlan, $draftItem] = $this->activePlan($patient);
        [$completedPlan, $completedItem] = $this->activePlan($patient);
        [, $foreignItem] = $this->activePlan($otherPatient);
        $draftPlan->update(['status' => PatientServicePlan::STATUS_DRAFT]);
        $completedPlan->update(['status' => PatientServicePlan::STATUS_COMPLETED]);
        $planItem->service->update(['customer_price' => '350.00']);

        $this->actingAs($user)->get(route('patients.workspace', $patient))
            ->assertOk()
            ->assertViewHas('workspace', function (array $workspace) use ($planItem, $draftItem, $completedItem, $foreignItem) {
                return $workspace['invoicePlanItems']->contains('id', $planItem->id)
                    && ! $workspace['invoicePlanItems']->contains('id', $draftItem->id)
                    && ! $workspace['invoicePlanItems']->contains('id', $completedItem->id)
                    && ! $workspace['invoicePlanItems']->contains('id', $foreignItem->id);
            })
            ->assertSeeText('خدمة من خطة الحالة')
            ->assertSeeText('بند إضافي حر');

        $this->actingAs($user)->get(route('invoices.create', [
            'patient_id' => $patient->id,
            'workspace' => 1,
        ]))
            ->assertOk()
            ->assertViewHas('planItems', function ($planItems) use ($planItem, $draftItem, $completedItem) {
                return $planItems->contains('id', $planItem->id)
                    && ! $planItems->contains('id', $draftItem->id)
                    && ! $planItems->contains('id', $completedItem->id);
            });

        $action = URL::signedRoute('invoices.store', ['workspace_patient' => $patient->id]);
        $this->actingAs($user)->post($action, [
            'workspace' => 1,
            'patient_id' => $patient->id,
            'issue_date' => now()->toDateString(),
            'items' => [[
                'mode' => 'plan',
                'patient_service_plan_item_id' => $planItem->id,
                'quantity' => 2,
                'unit_price' => '999.00',
            ]],
        ])->assertRedirect(route('patients.workspace', ['patient' => $patient, 'section' => 'finance']));

        $invoice = Invoice::query()->where('patient_id', $patient->id)->latest('id')->firstOrFail();
        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'description' => $planItem->service->name,
            'quantity' => 2,
            'unit_price' => 100,
            'total' => 200,
        ]);

        foreach ([$draftItem, $completedItem, $foreignItem] as $invalidItem) {
            $this->actingAs($user)->post($action, [
                'workspace' => 1,
                'patient_id' => $patient->id,
                'issue_date' => now()->toDateString(),
                'items' => [[
                    'mode' => 'plan',
                    'patient_service_plan_item_id' => $invalidItem->id,
                    'quantity' => 1,
                ]],
            ])->assertSessionHasErrors('items.0.patient_service_plan_item_id');
        }

        $this->assertDatabaseCount('invoices', 1);
    }

    public function test_workspace_allocation_uses_existing_allocator_and_rejects_foreign_payment(): void
    {
        $user = $this->userWithPermissions(['view patients', 'manage patient service plans']);
        $patient = $this->patient('حالة التخصيص');
        $otherPatient = $this->patient('حالة تخصيص أخرى');
        [$plan] = $this->activePlan($patient, planned: 2);
        $payment = $this->invoicePayment($patient, '100.00');
        $foreignPayment = $this->invoicePayment($otherPatient, '100.00');
        $action = URL::signedRoute('patient-service-plans.allocate-payment', [
            'patientServicePlan' => $plan,
            'workspace_patient' => $patient->id,
        ]);

        $this->actingAs($user)->post($action, [
            'workspace' => 1,
            'workspace_panel' => 'allocation',
            'invoice_payment_id' => $payment->id,
        ])->assertRedirect(route('patients.workspace', ['patient' => $patient, 'section' => 'plan']));

        $this->assertDatabaseHas('patient_service_plan_payments', [
            'patient_service_plan_id' => $plan->id,
            'invoice_payment_id' => $payment->id,
            'amount_snapshot' => 100,
        ]);

        $this->actingAs($user)->from(route('patients.workspace', $patient))->post($action, [
            'workspace' => 1,
            'workspace_panel' => 'allocation',
            'invoice_payment_id' => $foreignPayment->id,
        ])->assertSessionHasErrors('invoice_payment_id');

        $this->assertDatabaseMissing('patient_service_plan_payments', [
            'invoice_payment_id' => $foreignPayment->id,
        ]);
    }

    public function test_workspace_booking_fixes_patient_and_reuses_service_plan_booking(): void
    {
        $user = $this->userWithPermissions(['view patients', 'view appointments', 'create appointments']);
        $patient = $this->patient('حالة الحجز');
        $otherPatient = $this->patient('حالة حجز أخرى');
        [, $item] = $this->activePlan($patient, planned: 2, authorized: 1);
        [, $foreignItem] = $this->activePlan($otherPatient, planned: 1, authorized: 1);
        $therapistUser = User::factory()->create();
        $therapist = Therapist::create([
            'user_id' => $therapistUser->id,
            'name' => 'أخصائي حجز الحالة',
            'is_active' => true,
        ]);
        $therapist->services()->attach([$item->service_id, $foreignItem->service_id]);
        foreach (array_keys(TherapistWorkPeriod::WEEKDAYS) as $weekday) {
            $therapist->workPeriods()->create([
                'weekday' => $weekday,
                'starts_at' => '08:00',
                'ends_at' => '22:00',
            ]);
        }
        $action = URL::signedRoute('appointments.store', ['workspace_patient' => $patient->id]);
        $payload = [
            'workspace' => 1,
            'workspace_panel' => 'appointment',
            'patient_id' => $patient->id,
            'patient_service_plan_item_id' => $item->id,
            'therapist_id' => $therapistUser->id,
            'scheduled_at' => now()->addDays(3)->setTime(10, 0)->format('Y-m-d H:i:s'),
        ];

        $this->actingAs($user)->post($action, $payload)
            ->assertRedirect(route('patients.workspace', ['patient' => $patient, 'section' => 'appointments']));

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id,
            'patient_service_plan_item_id' => $item->id,
            'therapist_id' => $therapistUser->id,
        ]);

        $this->actingAs($user)->from(route('patients.workspace', $patient))->post($action, array_replace($payload, [
            'patient_service_plan_item_id' => $foreignItem->id,
            'scheduled_at' => now()->addDays(4)->setTime(10, 0)->format('Y-m-d H:i:s'),
        ]))->assertSessionHasErrors('patient_service_plan_item_id');

        $this->assertDatabaseMissing('appointments', [
            'patient_id' => $patient->id,
            'patient_service_plan_item_id' => $foreignItem->id,
        ]);
    }

    public function test_therapist_workspace_access_remains_scoped_to_assigned_patients(): void
    {
        $therapist = $this->userWithPermissions(['view patients']);
        $therapist->assignRole('أخصائي تخاطب');
        $assignedPatient = $this->patient('حالة الأخصائي');
        $otherPatient = $this->patient('حالة غير مسندة');
        $sessionType = SessionType::create([
            'name' => 'جلسة مسندة',
            'duration_minutes' => 30,
            'price' => 100,
        ]);

        Appointment::create([
            'patient_id' => $assignedPatient->id,
            'therapist_id' => $therapist->id,
            'session_type_id' => $sessionType->id,
            'scheduled_at' => now()->addDay(),
            'end_at' => now()->addDay()->addMinutes(30),
            'status' => 'مجدول',
        ]);

        $this->actingAs($therapist)->get(route('patients.workspace', $assignedPatient))->assertOk();
        $this->actingAs($therapist)->get(route('patients.workspace', $otherPatient))->assertForbidden();
    }

    public function test_signed_workspace_mutation_rejects_patient_outside_therapist_scope(): void
    {
        $therapist = $this->userWithPermissions(['view patients', 'manage invoices']);
        $therapist->assignRole('أخصائي تخاطب');
        $assignedPatient = $this->patient('حالة مسندة للتعديل');
        $otherPatient = $this->patient('حالة غير مسندة للتعديل');
        $sessionType = SessionType::create([
            'name' => 'جلسة صلاحيات مساحة العمل',
            'duration_minutes' => 30,
            'price' => 100,
        ]);

        Appointment::create([
            'patient_id' => $assignedPatient->id,
            'therapist_id' => $therapist->id,
            'session_type_id' => $sessionType->id,
            'scheduled_at' => now()->addDay(),
            'end_at' => now()->addDay()->addMinutes(30),
            'status' => 'مجدول',
        ]);

        $this->actingAs($therapist)->post(URL::signedRoute('invoices.store', [
            'workspace_patient' => $otherPatient->id,
        ]), [
            'workspace' => 1,
            'patient_id' => $otherPatient->id,
            'issue_date' => now()->toDateString(),
            'items' => [['description' => 'فاتورة غير مصرح بها', 'quantity' => 1, 'unit_price' => 100]],
        ])->assertForbidden();

        $this->assertDatabaseMissing('invoices', ['patient_id' => $otherPatient->id]);
    }

    public function test_payable_invoices_include_older_records_without_cross_patient_leakage(): void
    {
        $user = $this->userWithPermissions(['view patients', 'view finance', 'manage invoices']);
        $patient = $this->patient('حالة بفواتير كثيرة');
        $otherPatient = $this->patient('حالة بفواتير منفصلة');
        $olderInvoice = null;

        for ($index = 0; $index < 11; $index++) {
            $invoice = Invoice::create([
                'patient_id' => $patient->id,
                'invoice_number' => 'INV-MANY-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'issue_date' => now()->subDays(20 - $index)->toDateString(),
                'status' => 'غير مدفوعة',
                'total' => '100.00',
            ]);

            $olderInvoice ??= $invoice;
        }

        $foreignInvoice = Invoice::create([
            'patient_id' => $otherPatient->id,
            'invoice_number' => 'INV-FOREIGN-PAYABLE',
            'issue_date' => now()->toDateString(),
            'status' => 'غير مدفوعة',
            'total' => '500.00',
        ]);

        $this->actingAs($user)->get(route('patients.workspace', $patient))
            ->assertOk()
            ->assertViewHas('workspace', function (array $workspace) use ($olderInvoice, $foreignInvoice) {
                return $workspace['invoices']->count() === 10
                    && ! $workspace['invoices']->contains('id', $olderInvoice->id)
                    && $workspace['payableInvoices']->contains('id', $olderInvoice->id)
                    && ! $workspace['payableInvoices']->contains('id', $foreignInvoice->id);
            })
            ->assertDontSeeText($foreignInvoice->invoice_number);
    }

    public function test_workspace_header_and_plan_summary_keep_navigation_local(): void
    {
        $user = $this->userWithPermissions(['view patients', 'manage patient service plans']);
        $patient = $this->patient('حالة تنقل داخلي');
        [$plan] = $this->activePlan($patient);

        $this->actingAs($user)->get(route('patients.workspace', $patient))
            ->assertOk()
            ->assertDontSee('<h1 class="truncate text-2xl font-bold text-text sm:text-3xl"><a', false)
            ->assertSee('href="'.route('patients.show', $patient).'"', false)
            ->assertSee('>الملف الطبي الكامل</a>', false)
            ->assertSee('href="'.route('patients.workspace', ['patient' => $patient, 'section' => 'plan']).'"', false)
            ->assertDontSee('href="'.route('patient-service-plans.show', $plan).'"', false);
    }

    public function test_workspace_plan_editor_has_signed_return_context(): void
    {
        $user = $this->userWithPermissions(['view patients', 'manage patient service plans']);
        $patient = $this->patient('حالة رجوع المحرر');
        [$plan, $item] = $this->activePlan($patient);
        $editUrl = URL::signedRoute('patient-service-plans.edit', [
            'patientServicePlan' => $plan,
            'workspace_patient' => $patient->id,
            'workspace' => 1,
        ]);

        $this->actingAs($user)->get($editUrl)
            ->assertOk()
            ->assertSeeText('رجوع إلى ملف الحالة')
            ->assertSee('href="'.route('patients.workspace', $patient).'"', false);

        $this->actingAs($user)->put(URL::signedRoute('patient-service-plans.update', [
            'patientServicePlan' => $plan,
            'workspace_patient' => $patient->id,
        ]), [
            'workspace' => 1,
            'notes' => 'تعديل آمن من مساحة العمل',
            'items' => [[
                'id' => $item->id,
                'service_id' => $item->service_id,
                'position' => 1,
                'planned_quantity' => $item->planned_quantity,
            ]],
        ])->assertRedirect(route('patients.workspace', ['patient' => $patient, 'section' => 'plan']));

        $this->assertSame('100.00', $plan->items()->firstOrFail()->customer_unit_price);
    }

    public function test_invoice_detail_has_deterministic_back_navigation(): void
    {
        $user = $this->userWithPermissions(['view finance']);
        $patient = $this->patient('حالة رجوع الفاتورة');
        $invoice = $this->invoicePayment($patient, '75.00')->invoice;

        $this->actingAs($user)->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertSee('href="'.route('invoices.index').'"', false)
            ->assertSeeText('رجوع');
    }

    public function test_legacy_permission_and_invoice_create_route_remain_unchanged(): void
    {
        $user = $this->userWithPermissions(['create appointments']);
        $patient = $this->patient('حجز آمن');
        $therapist = User::factory()->create();
        $sessionType = SessionType::create([
            'name' => 'جلسة قديمة',
            'duration_minutes' => 30,
            'price' => 100,
        ]);

        $this->actingAs($user)->post(route('appointments.store'), [
            'patient_id' => $patient->id,
            'therapist_id' => $therapist->id,
            'session_type_id' => $sessionType->id,
            'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'legacy_booking_reason' => 'سبب إداري',
        ])->assertForbidden();

        $route = Route::getRoutes()->match(Request::create('/invoices/create', 'GET'));
        $this->assertSame('invoices.create', $route->getName());
    }

    private function userWithPermissions(array $permissions): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'أخصائي تخاطب', 'guard_name' => 'web']);
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $user->givePermissionTo($permissions);

        return $user;
    }

    private function patient(string $name): Patient
    {
        $guardian = Guardian::create(['name' => 'ولي أمر '.$name, 'phone' => '01000000000']);

        return Patient::create([
            'guardian_id' => $guardian->id,
            'name' => $name,
            'birth_date' => '2018-01-01',
            'gender' => 'male',
            'diagnosis' => 'تقييم تخاطب',
            'barcode' => 'PAT-'.uniqid(),
            'qr_code' => 'PAT-'.uniqid(),
            'is_active' => true,
        ]);
    }

    private function activePlan(Patient $patient, int $planned = 2, int $authorized = 0, int $consumed = 0): array
    {
        $specialty = Specialty::create(['name' => 'تخصص '.uniqid(), 'is_active' => true]);
        $service = Service::create([
            'specialty_id' => $specialty->id,
            'name' => 'خدمة تخاطب',
            'default_duration_minutes' => 30,
            'customer_price' => '100.00',
            'is_active' => true,
        ]);
        $plan = PatientServicePlan::create([
            'patient_id' => $patient->id,
            'status' => PatientServicePlan::STATUS_ACTIVE,
        ]);
        $item = $plan->items()->create([
            'service_id' => $service->id,
            'position' => 1,
            'planned_quantity' => $planned,
            'customer_unit_price' => '100.00',
            'discount_amount' => '0.00',
            'final_unit_price' => '100.00',
            'authorized_quantity' => $authorized,
            'consumed_quantity' => $consumed,
        ]);

        return [$plan, $item];
    }

    private function invoicePayment(Patient $patient, string $amount): InvoicePayment
    {
        $invoice = Invoice::create([
            'patient_id' => $patient->id,
            'invoice_number' => 'INV-'.uniqid(),
            'issue_date' => now()->toDateString(),
            'status' => 'مدفوعة جزئياً',
            'total' => $amount,
        ]);

        return InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'payment_date' => now()->toDateString(),
            'method' => 'كاش',
        ]);
    }

    private function v2Appointment(Patient $patient, $item, User $therapist, $scheduledAt): Appointment
    {
        return Appointment::create([
            'patient_id' => $patient->id,
            'therapist_id' => $therapist->id,
            'patient_service_plan_item_id' => $item->id,
            'session_type_id' => null,
            'scheduled_at' => $scheduledAt,
            'end_at' => $scheduledAt->copy()->addMinutes(30),
            'status' => 'مجدول',
            'financially_confirmed_at' => now(),
            'confirmation_deposit_percentage_snapshot' => 50,
            'confirmation_deposit_amount_snapshot' => '50.00',
        ]);
    }
}
