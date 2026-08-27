<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Guardian;
use App\Models\Patient;
use App\Models\PatientClinicalEvaluation;
use App\Models\PatientServicePlan;
use App\Models\Service;
use App\Models\Specialty;
use App\Models\TherapyProgram;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ClinicalEvaluationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_clinical_permission_is_assigned_to_clinical_role_but_not_reception(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->assertTrue(Role::findByName('مدير النظام')->hasPermissionTo('manage clinical evaluations'));
        $this->assertTrue(Role::findByName('أخصائي تخاطب')->hasPermissionTo('manage clinical evaluations'));
        $this->assertFalse(Role::findByName('موظف استقبال')->hasPermissionTo('manage clinical evaluations'));
        $this->assertFalse(Role::findByName('مسؤول مالي')->hasPermissionTo('manage clinical evaluations'));
    }

    public function test_new_patient_waits_for_evaluation_assignment_and_reception_cannot_mutate_evaluation(): void
    {
        $reception = $this->userWithPermissions(['view patients']);
        $patient = $this->patient();

        $this->actingAs($reception)->get(route('patients.workspace', $patient))
            ->assertOk()
            ->assertSeeText('بانتظار إسناد التقييم')
            ->assertDontSeeText('بدء التقييم')
            ->assertDontSee(route('patients.clinical-evaluation.save-draft', $patient), false);

        $this->actingAs($reception)->post(route('patients.clinical-evaluation.save-draft', $patient), [
            'clinical_summary' => 'محاولة غير مخولة',
        ])->assertForbidden();

        $this->assertDatabaseCount('patient_clinical_evaluations', 0);
    }

    public function test_authorized_user_can_save_resume_and_complete_one_evaluation_draft(): void
    {
        $clinician = $this->userWithPermissions(['view patients', 'edit patients', 'manage clinical evaluations']);
        $patient = $this->patient();

        $this->actingAs($clinician)->post(route('patients.clinical-evaluation.save-draft', $patient), [
            'clinical_summary' => 'ملخص أولي محفوظ',
        ])->assertRedirect(route('patients.workspace', ['patient' => $patient, 'section' => 'clinical']));

        $evaluation = PatientClinicalEvaluation::sole();
        $this->assertSame(PatientClinicalEvaluation::STATUS_DRAFT, $evaluation->status);
        $this->assertSame($clinician->id, $evaluation->evaluated_by);

        $this->actingAs($clinician)->post(route('patients.clinical-evaluation.save-draft', $patient), [
            'clinical_summary' => 'ملخص مستكمل',
        ])->assertRedirect(route('patients.workspace', ['patient' => $patient, 'section' => 'clinical']));

        $this->assertDatabaseCount('patient_clinical_evaluations', 1);
        $this->assertDatabaseHas('patient_clinical_evaluations', [
            'id' => $evaluation->id,
            'clinical_summary' => 'ملخص مستكمل',
        ]);

        $this->actingAs($clinician)->get(route('patients.workspace', $patient))
            ->assertOk()
            ->assertSeeText('التقييم قيد الاستكمال')
            ->assertSee('ملخص مستكمل')
            ->assertSee('formaction="'.route('patients.clinical-evaluation.complete', [$patient, $evaluation]).'"', false)
            ->assertDontSee('id="complete-clinical-evaluation"', false);

        $this->actingAs($clinician)->post(route('patients.clinical-evaluation.complete', [$patient, $evaluation]), [
            'clinical_summary' => 'ملخص الإكمال الأحدث',
        ])
            ->assertRedirect(route('patients.workspace', ['patient' => $patient, 'section' => 'clinical']));

        $evaluation->refresh();
        $this->assertTrue($evaluation->isCompleted());
        $this->assertSame($clinician->id, $evaluation->completed_by);
        $this->assertNotNull($evaluation->completed_at);
        $this->assertSame('ملخص الإكمال الأحدث', $evaluation->clinical_summary);

        $this->actingAs($clinician)->get(route('patients.workspace', $patient))
            ->assertOk()
            ->assertSeeText('الخطة العلاجية قيد الإعداد')
            ->assertSeeText('ملخص الإكمال الأحدث')
            ->assertDontSee('name="clinical_summary"', false);

        $this->assertNoOperationalSideEffects();
    }

    public function test_completed_evaluation_allows_a_priced_clinical_plan_and_safe_handoff(): void
    {
        $clinician = $this->userWithPermissions(['view patients', 'manage clinical evaluations']);
        $patient = $this->patient();
        $evaluation = $this->completedEvaluation($patient, $clinician);
        $service = $this->service('جلسة تخاطب سريرية', '175.00');

        $this->actingAs($clinician)->post(route('patients.clinical-plan.save-draft', $patient), [
            'clinical_evaluation_id' => $evaluation->id,
            'items' => [[
                'service_id' => $service->id,
                'position' => 1,
                'planned_quantity' => 6,
                'customer_unit_price' => '1.00',
                'discount_amount' => '174.00',
                'authorized_quantity' => 6,
                'consumed_quantity' => 6,
            ]],
        ])->assertRedirect(route('patients.workspace', ['patient' => $patient, 'section' => 'clinical']));

        $plan = PatientServicePlan::sole();
        $item = $plan->items()->sole();
        $this->assertSame(PatientServicePlan::STATUS_DRAFT, $plan->status);
        $this->assertSame($evaluation->id, $plan->clinical_evaluation_id);
        $this->assertSame('175.00', $item->customer_unit_price);
        $this->assertSame('0.00', $item->discount_amount);
        $this->assertSame(0, $item->authorized_quantity);
        $this->assertSame(0, $item->consumed_quantity);

        $this->actingAs($clinician)->post(route('patients.clinical-plan.approve', [$patient, $plan]), [
            'clinical_evaluation_id' => $evaluation->id,
            'items' => [[
                'service_id' => $service->id,
                'position' => 1,
                'planned_quantity' => 8,
                'customer_unit_price' => '1.00',
                'discount_amount' => '174.00',
                'authorized_quantity' => 8,
                'consumed_quantity' => 8,
            ]],
        ])
            ->assertRedirect(route('patients.workspace', ['patient' => $patient, 'section' => 'clinical']));

        $plan->refresh();
        $item = $plan->items()->sole();
        $this->assertSame(PatientServicePlan::STATUS_DRAFT, $plan->status);
        $this->assertSame($clinician->id, $plan->clinical_approved_by);
        $this->assertNotNull($plan->clinical_approved_at);
        $this->assertSame(8, $item->planned_quantity);
        $this->assertSame('175.00', $item->customer_unit_price);
        $this->assertSame('0.00', $item->discount_amount);
        $this->assertSame(0, $item->authorized_quantity);
        $this->assertSame(0, $item->consumed_quantity);
        $this->assertNoOperationalSideEffects();

        $reception = $this->userWithPermissions(['view patients']);
        $this->actingAs($reception)->get(route('patients.workspace', $patient))
            ->assertOk()
            ->assertSeeText('الخطة جاهزة للاستقبال')
            ->assertSeeText('جلسة تخاطب سريرية')
            ->assertSeeText('8')
            ->assertDontSee(route('patients.clinical-plan.save-draft', $patient), false);
    }

    public function test_draft_evaluation_cannot_be_approved_and_approved_structure_is_immutable(): void
    {
        $clinician = $this->userWithPermissions(['view patients', 'manage clinical evaluations']);
        $patient = $this->patient();
        $service = $this->service();
        $draftEvaluation = PatientClinicalEvaluation::create([
            'patient_id' => $patient->id,
            'status' => PatientClinicalEvaluation::STATUS_DRAFT,
            'evaluated_by' => $clinician->id,
        ]);
        $plan = PatientServicePlan::create([
            'patient_id' => $patient->id,
            'clinical_evaluation_id' => $draftEvaluation->id,
            'status' => PatientServicePlan::STATUS_DRAFT,
            'created_by' => $clinician->id,
        ]);
        $item = $plan->items()->create([
            'service_id' => $service->id,
            'position' => 1,
            'planned_quantity' => 4,
            'customer_unit_price' => '100.00',
            'discount_amount' => '0.00',
            'final_unit_price' => '100.00',
        ]);

        $approvalPayload = [
            'clinical_evaluation_id' => $draftEvaluation->id,
            'items' => [[
                'service_id' => $service->id,
                'position' => 1,
                'planned_quantity' => 4,
            ]],
        ];

        $this->actingAs($clinician)->post(route('patients.clinical-plan.approve', [$patient, $plan]), $approvalPayload)
            ->assertSessionHasErrors('evaluation');

        $draftEvaluation->update([
            'status' => PatientClinicalEvaluation::STATUS_COMPLETED,
            'completed_by' => $clinician->id,
            'completed_at' => now(),
        ]);
        $this->actingAs($clinician)->post(route('patients.clinical-plan.approve', [$patient, $plan]), $approvalPayload)
            ->assertRedirect();
        $item = $plan->items()->sole();

        $manager = $this->userWithPermissions(['manage patient service plans']);
        $this->actingAs($manager)->put(route('patient-service-plans.update', $plan), [
            'items' => [[
                'id' => $item->id,
                'service_id' => $service->id,
                'position' => 1,
                'planned_quantity' => 9,
                'discount_amount' => '0.00',
            ]],
        ])->assertSessionHasErrors('plan');

        $this->assertSame(4, $item->fresh()->planned_quantity);
    }

    public function test_clinically_approved_plan_cannot_be_deleted_but_legacy_draft_still_can(): void
    {
        $manager = $this->userWithPermissions(['manage patient service plans']);
        $clinician = $this->userWithPermissions(['view patients', 'manage clinical evaluations']);
        $patient = $this->patient();
        $evaluation = $this->completedEvaluation($patient, $clinician);
        $service = $this->service();
        $approved = $this->plan($patient, $service, PatientServicePlan::STATUS_DRAFT, $evaluation);
        $approved->update(['clinical_approved_by' => $clinician->id, 'clinical_approved_at' => now()]);
        $approvedItemId = $approved->items()->sole()->id;

        $this->assertFalse($approved->fresh()->canDelete());
        $this->actingAs($manager)->delete(route('patient-service-plans.destroy', $approved))
            ->assertSessionHasErrors('plan');
        $this->assertDatabaseHas('patient_service_plans', ['id' => $approved->id]);
        $this->assertDatabaseHas('patient_service_plan_items', ['id' => $approvedItemId]);

        $legacy = $this->plan($patient, $service);
        $this->assertTrue($legacy->canDelete());
        $this->actingAs($manager)->delete(route('patient-service-plans.destroy', $legacy))
            ->assertRedirect();
        $this->assertDatabaseMissing('patient_service_plans', ['id' => $legacy->id]);
    }

    public function test_clinical_plan_requires_approval_before_activation_and_legacy_activation_is_unchanged(): void
    {
        $manager = $this->userWithPermissions(['manage patient service plans']);
        $clinician = $this->userWithPermissions(['manage clinical evaluations']);
        $patient = $this->patient();
        $evaluation = $this->completedEvaluation($patient, $clinician);
        $service = $this->service();
        $clinicalPlan = $this->plan($patient, $service, PatientServicePlan::STATUS_DRAFT, $evaluation);

        $this->actingAs($manager)->post(route('patient-service-plans.activate', $clinicalPlan))
            ->assertSessionHasErrors('status');
        $this->assertSame(PatientServicePlan::STATUS_DRAFT, $clinicalPlan->fresh()->status);
        $this->assertNoOperationalSideEffects();

        $payload = $this->clinicalPlanPayload($evaluation, $service, 3);
        $this->actingAs($clinician)->post(route('patients.clinical-plan.approve', [$patient, $clinicalPlan]), $payload)
            ->assertRedirect();
        $this->actingAs($manager)->post(route('patient-service-plans.activate', $clinicalPlan))
            ->assertRedirect();
        $this->assertSame(PatientServicePlan::STATUS_ACTIVE, $clinicalPlan->fresh()->status);

        $legacy = $this->plan($patient, $service);
        $this->actingAs($manager)->post(route('patient-service-plans.activate', $legacy))
            ->assertRedirect();
        $this->assertSame(PatientServicePlan::STATUS_ACTIVE, $legacy->fresh()->status);
    }

    public function test_evaluation_completion_enforces_owner_and_nested_patient_integrity(): void
    {
        $clinicianA = $this->userWithPermissions(['edit patients', 'manage clinical evaluations']);
        $clinicianB = $this->userWithPermissions(['manage clinical evaluations']);
        $patient = $this->patient();
        $otherPatient = $this->patient('PAT-CLINICAL-2');

        $this->actingAs($clinicianA)->post(route('patients.clinical-evaluation.save-draft', $patient), [
            'clinical_summary' => 'ملكية الأخصائي الأول',
        ]);
        $evaluation = PatientClinicalEvaluation::sole();

        $this->actingAs($clinicianB)->post(route('patients.clinical-evaluation.save-draft', $patient), [
            'clinical_summary' => 'تعديل غير مسموح',
        ])->assertSessionHasErrors('clinical_summary');
        $this->actingAs($clinicianB)->post(route('patients.clinical-evaluation.complete', [$patient, $evaluation]), [
            'clinical_summary' => 'إكمال غير مسموح',
        ])->assertSessionHasErrors('clinical_summary');
        $this->actingAs($clinicianA)->post(route('patients.clinical-evaluation.complete', [$otherPatient, $evaluation]), [
            'clinical_summary' => 'مريض غير مطابق',
        ])->assertNotFound();

        $this->actingAs($clinicianA)->post(route('patients.clinical-evaluation.complete', [$patient, $evaluation]), [
            'clinical_summary' => 'النص النهائي للمالك',
        ])->assertRedirect();
        $completedAt = $evaluation->fresh()->completed_at;
        $this->actingAs($clinicianA)->post(route('patients.clinical-evaluation.complete', [$patient, $evaluation]), [
            'clinical_summary' => 'محاولة تكرار',
        ])->assertRedirect();

        $this->assertSame('النص النهائي للمالك', $evaluation->fresh()->clinical_summary);
        $this->assertTrue($completedAt->equalTo($evaluation->fresh()->completed_at));
    }

    public function test_plan_can_be_created_and_approved_atomically_without_prior_draft_save(): void
    {
        $clinician = $this->userWithPermissions(['view patients', 'manage clinical evaluations']);
        $patient = $this->patient();
        $evaluation = $this->completedEvaluation($patient, $clinician);
        $service = $this->service('خدمة اعتماد مباشر', '225.00');

        $this->actingAs($clinician)->get(route('patients.workspace', $patient))
            ->assertOk()
            ->assertSee('formaction="'.route('patients.clinical-plan.approve-new', $patient).'"', false)
            ->assertDontSee('id="approve-clinical-plan"', false);

        $this->actingAs($clinician)->post(
            route('patients.clinical-plan.approve-new', $patient),
            $this->clinicalPlanPayload($evaluation, $service, 5, true)
        )->assertRedirect(route('patients.workspace', ['patient' => $patient, 'section' => 'clinical']));

        $plan = PatientServicePlan::sole();
        $item = $plan->items()->sole();
        $this->assertTrue($plan->isClinicallyApproved());
        $this->assertSame(PatientServicePlan::STATUS_DRAFT, $plan->status);
        $this->assertSame(5, $item->planned_quantity);
        $this->assertSame('225.00', $item->customer_unit_price);
        $this->assertSame('0.00', $item->discount_amount);
        $this->assertSame(0, $item->authorized_quantity);
        $this->assertSame(0, $item->consumed_quantity);
        $this->assertNoOperationalSideEffects();
    }

    public function test_plan_approval_rejects_cross_patient_evaluation_and_plan_combinations(): void
    {
        $clinician = $this->userWithPermissions(['manage clinical evaluations']);
        $patient = $this->patient();
        $otherPatient = $this->patient('PAT-CLINICAL-2');
        $evaluation = $this->completedEvaluation($patient, $clinician);
        $otherEvaluation = $this->completedEvaluation($otherPatient, $clinician);
        $service = $this->service();
        $plan = $this->plan($patient, $service, PatientServicePlan::STATUS_DRAFT, $evaluation);

        $this->actingAs($clinician)->post(
            route('patients.clinical-plan.approve', [$otherPatient, $plan]),
            $this->clinicalPlanPayload($otherEvaluation, $service, 2)
        )->assertNotFound();
        $this->actingAs($clinician)->post(
            route('patients.clinical-plan.approve', [$patient, $plan]),
            $this->clinicalPlanPayload($otherEvaluation, $service, 2)
        )->assertSessionHasErrors('clinical_evaluation_id');

        $this->assertNull($plan->fresh()->clinical_approved_at);
    }

    public function test_active_treatment_remains_primary_during_re_evaluation_and_future_plan_is_separate(): void
    {
        $clinician = $this->userWithPermissions([
            'view patients',
            'edit patients',
            'manage clinical evaluations',
            'view finance',
            'view appointments',
        ]);
        $patient = $this->patient();
        $activeService = $this->service('خدمة الدورة الحالية');
        $activePlan = $this->plan($patient, $activeService, PatientServicePlan::STATUS_ACTIVE);
        $activeItem = $activePlan->items()->sole();

        $this->actingAs($clinician)->post(route('patients.clinical-evaluation.save-draft', $patient), [
            'clinical_summary' => 'إعادة تقييم مستقلة',
        ]);
        $evaluation = PatientClinicalEvaluation::sole();

        $this->actingAs($clinician)->get(route('patients.workspace', $patient))
            ->assertOk()
            ->assertViewHas('workspace', fn (array $workspace) => $workspace['clinical']['key'] === 'operational'
                && $workspace['clinical']['secondary_key'] === 'evaluation_draft'
                && $workspace['currentPlan']->is($activePlan))
            ->assertSeeText('الخطة العلاجية قيد التنفيذ')
            ->assertSeeText('يوجد تقييم جديد قيد الاستكمال')
            ->assertSeeText('المالية والفواتير')
            ->assertSeeText('المواعيد');

        $this->actingAs($clinician)->post(route('patients.clinical-evaluation.complete', [$patient, $evaluation]), [
            'clinical_summary' => 'إعادة تقييم مكتملة',
        ]);
        $futureService = $this->service('خدمة الدورة التالية', '140.00');
        $this->actingAs($clinician)->post(route('patients.clinical-plan.save-draft', $patient),
            $this->clinicalPlanPayload($evaluation, $futureService, 4)
        )->assertRedirect();

        $futurePlan = PatientServicePlan::query()->whereKeyNot($activePlan->id)->sole();
        $this->assertSame($evaluation->id, $futurePlan->clinical_evaluation_id);
        $this->assertSame(PatientServicePlan::STATUS_ACTIVE, $activePlan->fresh()->status);
        $this->assertSame($activeService->id, $activeItem->fresh()->service_id);
    }

    public function test_booking_and_therapy_program_do_not_fabricate_assignment_stage(): void
    {
        $viewer = $this->userWithPermissions(['view patients']);
        $patient = $this->patient();
        $service = $this->service();
        $activePlan = $this->plan($patient, $service, PatientServicePlan::STATUS_ACTIVE);
        $item = $activePlan->items()->sole();
        $therapist = User::factory()->create();
        TherapyProgram::create([
            'name' => 'برنامج قائم',
            'patient_id' => $patient->id,
            'therapist_id' => $therapist->id,
            'status' => TherapyProgram::STATUS_ACTIVE,
            'start_date' => now()->toDateString(),
        ]);
        Appointment::create([
            'patient_id' => $patient->id,
            'therapist_id' => $therapist->id,
            'patient_service_plan_item_id' => $item->id,
            'scheduled_at' => now()->addDay(),
            'end_at' => now()->addDay()->addMinutes(45),
            'status' => 'مجدول',
        ]);

        $this->actingAs($viewer)->get(route('patients.workspace', $patient))
            ->assertOk()
            ->assertViewHas('workspace', function (array $workspace) {
                $workflow = collect($workspace['workflow'])->keyBy('label');

                return $workflow['الإسناد']['complete'] === false
                    && $workflow['الحجز']['complete'] === true;
            });
    }

    public function test_clinical_permission_does_not_bypass_existing_patient_access_scope(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $therapist = User::factory()->create();
        $therapist->assignRole('أخصائي تخاطب');
        $patient = $this->patient();

        $this->actingAs($therapist)->post(route('patients.clinical-evaluation.save-draft', $patient), [
            'clinical_summary' => 'محاولة وصول غير مسندة',
        ])->assertForbidden();

        $this->assertDatabaseCount('patient_clinical_evaluations', 0);
    }

    private function plan(
        Patient $patient,
        Service $service,
        string $status = PatientServicePlan::STATUS_DRAFT,
        ?PatientClinicalEvaluation $evaluation = null
    ): PatientServicePlan {
        $plan = PatientServicePlan::create([
            'patient_id' => $patient->id,
            'clinical_evaluation_id' => $evaluation?->id,
            'status' => $status,
        ]);
        $plan->items()->create([
            'service_id' => $service->id,
            'position' => 1,
            'planned_quantity' => 2,
            'customer_unit_price' => $service->customer_price,
            'discount_amount' => '0.00',
            'final_unit_price' => $service->customer_price,
        ]);

        return $plan;
    }

    private function clinicalPlanPayload(
        PatientClinicalEvaluation $evaluation,
        Service $service,
        int $quantity,
        bool $includeProtectedValues = false
    ): array {
        $item = [
            'service_id' => $service->id,
            'position' => 1,
            'planned_quantity' => $quantity,
        ];

        if ($includeProtectedValues) {
            $item += [
                'customer_unit_price' => '1.00',
                'final_unit_price' => '1.00',
                'discount_amount' => '99.00',
                'authorized_quantity' => $quantity,
                'consumed_quantity' => $quantity,
            ];
        }

        return [
            'clinical_evaluation_id' => $evaluation->id,
            'items' => [$item],
        ];
    }

    private function completedEvaluation(Patient $patient, User $user): PatientClinicalEvaluation
    {
        return PatientClinicalEvaluation::create([
            'patient_id' => $patient->id,
            'status' => PatientClinicalEvaluation::STATUS_COMPLETED,
            'clinical_summary' => 'تقييم مكتمل',
            'evaluated_by' => $user->id,
            'completed_by' => $user->id,
            'completed_at' => now(),
        ]);
    }

    private function patient(string $barcode = 'PAT-CLINICAL-1'): Patient
    {
        $phone = '010'.str_pad((string) (Guardian::count() + 1), 8, '0', STR_PAD_LEFT);
        $guardian = Guardian::create(['name' => 'ولي أمر الحالة', 'phone' => $phone]);

        return Patient::create([
            'guardian_id' => $guardian->id,
            'name' => 'طفل تحت التقييم',
            'birth_date' => '2020-01-01',
            'gender' => 'male',
            'barcode' => $barcode,
            'qr_code' => $barcode,
            'is_active' => true,
        ]);
    }

    private function service(string $name = 'خدمة سريرية', string $price = '100.00'): Service
    {
        $specialty = Specialty::create(['name' => 'تخصص سريري '.uniqid(), 'is_active' => true]);

        return Service::create([
            'specialty_id' => $specialty->id,
            'name' => $name,
            'default_duration_minutes' => 45,
            'customer_price' => $price,
            'is_active' => true,
        ]);
    }

    private function userWithPermissions(array $permissions): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $user->givePermissionTo($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    private function assertNoOperationalSideEffects(): void
    {
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_payments', 0);
        $this->assertDatabaseCount('patient_service_plan_payments', 0);
        $this->assertDatabaseCount('therapy_sessions', 0);
        $this->assertDatabaseCount('therapist_earnings', 0);
    }
}
