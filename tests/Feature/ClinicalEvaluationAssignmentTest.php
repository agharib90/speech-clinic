<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\Patient;
use App\Models\PatientClinicalEvaluation;
use App\Models\PatientClinicalEvaluationAssignment;
use App\Models\PatientServicePlan;
use App\Models\Service;
use App\Models\Specialty;
use App\Models\Therapist;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ClinicalEvaluationAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_assignment_permission_is_scoped_to_reception_and_management(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->assertTrue(Permission::findByName('manage clinical evaluation assignments')->exists);
        $this->assertTrue(Role::findByName('مدير النظام')->hasPermissionTo('manage clinical evaluation assignments'));
        $this->assertTrue(Role::findByName('موظف استقبال')->hasPermissionTo('manage clinical evaluation assignments'));
        $this->assertFalse(Role::findByName('أخصائي تخاطب')->hasPermissionTo('manage clinical evaluation assignments'));
    }

    public function test_reception_assigns_only_eligible_active_clinician_and_preserves_reassignment_history(): void
    {
        $reception = $this->userWithPermissions(['view patients', 'manage clinical evaluation assignments']);
        $first = $this->clinician();
        $second = $this->clinician();
        $nonClinical = User::factory()->create();
        $inactive = $this->clinician(activeTherapist: false);
        $patient = $this->patient('PAT-ASSIGN-1');

        $this->actingAs($reception)->post(route('patients.clinical-evaluation-assignment.store', $patient), [
            'assigned_to' => $nonClinical->id,
        ])->assertSessionHasErrors('assigned_to');
        $this->actingAs($reception)->post(route('patients.clinical-evaluation-assignment.store', $patient), [
            'assigned_to' => $inactive->id,
        ])->assertSessionHasErrors('assigned_to');

        $this->actingAs($reception)->post(route('patients.clinical-evaluation-assignment.store', $patient), [
            'assigned_to' => $first->id,
        ])->assertRedirect(route('patients.workspace', ['patient' => $patient, 'section' => 'clinical']));

        $firstAssignment = PatientClinicalEvaluationAssignment::sole();
        $this->assertSame(PatientClinicalEvaluationAssignment::STATUS_PENDING, $firstAssignment->status);
        $this->assertSame($reception->id, $firstAssignment->assigned_by);

        $this->actingAs($reception)->post(route('patients.clinical-evaluation-assignment.store', $patient), [
            'assigned_to' => $first->id,
        ])->assertRedirect(route('patients.workspace', ['patient' => $patient, 'section' => 'clinical']));
        $this->assertDatabaseCount('patient_clinical_evaluation_assignments', 1);

        $this->actingAs($reception)->post(route('patients.clinical-evaluation-assignment.store', $patient), [
            'assigned_to' => $second->id,
        ])->assertRedirect(route('patients.workspace', ['patient' => $patient, 'section' => 'clinical']));

        $this->assertDatabaseCount('patient_clinical_evaluation_assignments', 2);
        $this->assertDatabaseHas('patient_clinical_evaluation_assignments', [
            'id' => $firstAssignment->id,
            'status' => PatientClinicalEvaluationAssignment::STATUS_CANCELLED,
        ]);
        $this->assertNotNull($firstAssignment->fresh()->cancelled_at);
        $this->assertDatabaseHas('patient_clinical_evaluation_assignments', [
            'patient_id' => $patient->id,
            'assigned_to' => $second->id,
            'status' => PatientClinicalEvaluationAssignment::STATUS_PENDING,
        ]);
    }

    public function test_assignment_grants_only_patient_scoped_access_and_reassignment_moves_it(): void
    {
        $manager = $this->userWithPermissions(['view patients', 'manage clinical evaluation assignments']);
        $first = $this->clinician(asTherapistRole: true);
        $second = $this->clinician(asTherapistRole: true);
        $patient = $this->patient('PAT-SCOPED-A');
        $otherPatient = $this->patient('PAT-SCOPED-B');

        $this->actingAs($manager)->post(route('patients.clinical-evaluation-assignment.store', $patient), ['assigned_to' => $first->id]);

        $this->actingAs($first)->get(route('patients.workspace', $patient))->assertOk();
        $this->actingAs($first)->get(route('patients.workspace', $otherPatient))->assertForbidden();
        $this->actingAs($second)->get(route('patients.workspace', $patient))->assertForbidden();

        $this->actingAs($manager)->post(route('patients.clinical-evaluation-assignment.store', $patient), ['assigned_to' => $second->id]);

        $this->actingAs($first)->get(route('patients.workspace', $patient))->assertForbidden();
        $this->actingAs($second)->get(route('patients.workspace', $patient))->assertOk();

        $this->actingAs($second)->get(route('patients.index'))
            ->assertOk()
            ->assertSeeText($patient->name)
            ->assertDontSeeText($otherPatient->name);
    }

    public function test_first_draft_and_completion_advance_the_linked_assignment_atomically(): void
    {
        $manager = $this->userWithPermissions(['view patients', 'manage clinical evaluation assignments']);
        $clinician = $this->clinician(asTherapistRole: true);
        $replacement = $this->clinician();
        $patient = $this->patient('PAT-LIFECYCLE');
        $this->actingAs($manager)->post(route('patients.clinical-evaluation-assignment.store', $patient), ['assigned_to' => $clinician->id]);

        $this->actingAs($clinician)->post(route('patients.clinical-evaluation.save-draft', $patient), [
            'clinical_summary' => 'المسودة الأولى',
        ])->assertRedirect(route('patients.workspace', ['patient' => $patient, 'section' => 'clinical']));

        $evaluation = PatientClinicalEvaluation::sole();
        $assignment = PatientClinicalEvaluationAssignment::sole();
        $this->assertSame(PatientClinicalEvaluationAssignment::STATUS_IN_PROGRESS, $assignment->status);
        $this->assertSame($evaluation->id, $assignment->clinical_evaluation_id);
        $this->assertNotNull($assignment->started_at);
        $startedAt = $assignment->started_at;

        $this->actingAs($clinician)->get(route('clinical.evaluations.index', ['status' => 'in_progress']))
            ->assertOk()->assertSeeText($patient->name)->assertSeeText('قيد الاستكمال');

        $this->actingAs($clinician)->post(route('patients.clinical-evaluation.save-draft', $patient), [
            'clinical_summary' => 'مسودة مستكملة',
        ])->assertRedirect(route('patients.workspace', ['patient' => $patient, 'section' => 'clinical']));
        $this->assertDatabaseCount('patient_clinical_evaluations', 1);
        $this->assertDatabaseCount('patient_clinical_evaluation_assignments', 1);
        $this->assertTrue($assignment->fresh()->started_at->equalTo($startedAt));

        $this->actingAs($manager)->post(route('patients.clinical-evaluation-assignment.store', $patient), [
            'assigned_to' => $replacement->id,
        ])->assertSessionHasErrors('assigned_to');

        $this->actingAs($clinician)->post(route('patients.clinical-evaluation.complete', [$patient, $evaluation]), [
            'clinical_summary' => 'الملخص النهائي',
        ])->assertRedirect(route('patients.workspace', ['patient' => $patient, 'section' => 'clinical']));

        $assignment->refresh();
        $this->assertSame(PatientClinicalEvaluationAssignment::STATUS_COMPLETED, $assignment->status);
        $this->assertNotNull($assignment->completed_at);
        $this->assertDatabaseHas('patient_clinical_evaluations', [
            'id' => $evaluation->id,
            'status' => PatientClinicalEvaluation::STATUS_COMPLETED,
            'clinical_summary' => 'الملخص النهائي',
        ]);
        $this->actingAs($clinician)->get(route('clinical.evaluations.index', ['status' => 'completed']))
            ->assertOk()->assertSeeText($patient->name)->assertSeeText('مكتمل');
    }

    public function test_clinician_and_reception_queues_are_strictly_scoped(): void
    {
        $manager = $this->userWithPermissions(['view patients', 'manage clinical evaluation assignments']);
        $clinician = $this->clinician(asTherapistRole: true);
        $otherClinician = $this->clinician(asTherapistRole: true);
        $patient = $this->patient('PAT-QUEUE-A');
        $other = $this->patient('PAT-QUEUE-B');
        $this->actingAs($manager)->post(route('patients.clinical-evaluation-assignment.store', $patient), ['assigned_to' => $clinician->id]);
        $this->actingAs($manager)->post(route('patients.clinical-evaluation-assignment.store', $other), ['assigned_to' => $otherClinician->id]);

        $this->actingAs($clinician)->get(route('clinical.evaluations.index'))
            ->assertOk()->assertSeeText($patient->name)->assertDontSeeText($other->name);

        $evaluation = $this->completedEvaluation($patient, $clinician);
        $service = $this->service();
        $approved = $this->plan($patient, $evaluation, $service, approved: true);
        $this->plan($other, null, $service, approved: false);

        $this->actingAs($manager)->get(route('clinical.handoffs.index'))
            ->assertOk()->assertSeeText($patient->name)->assertDontSeeText($other->name);

        $approved->update(['status' => PatientServicePlan::STATUS_ACTIVE]);
        $this->actingAs($manager)->get(route('clinical.handoffs.index'))->assertDontSeeText($patient->name);
    }

    public function test_workspace_stages_filters_and_theme_aware_controls_are_truthful(): void
    {
        $manager = $this->userWithPermissions(['view patients', 'manage clinical evaluation assignments']);
        $clinician = $this->clinician(asTherapistRole: true);
        $unassigned = $this->patient('PAT-STAGE-UNASSIGNED');
        $pending = $this->patient('PAT-STAGE-PENDING');
        $this->actingAs($manager)->post(route('patients.clinical-evaluation-assignment.store', $pending), ['assigned_to' => $clinician->id]);

        $this->actingAs($manager)->get(route('patients.workspace', $unassigned))
            ->assertOk()->assertSeeText('بانتظار إسناد التقييم')->assertSeeText('إسناد التقييم')
            ->assertSee('border-success bg-success text-success-contrast', false)
            ->assertSee('border-primary bg-primary-soft text-primary', false)
            ->assertDontSee('border-accent bg-accent-soft text-accent', false);
        $this->actingAs($clinician)->get(route('patients.workspace', $pending))
            ->assertOk()->assertSeeText('بانتظار التقييم')->assertSeeText('بدء التقييم')
            ->assertSee('class="clinic-field mt-1 min-h-48 w-full resize-y"', false)
            ->assertSeeText('الخطوة التالية الموصى بها')
            ->assertSee('data-workspace-open="clinical:clinical"', false)
            ->assertSee('data-workspace-dirty-track', false);

        $this->actingAs($manager)->get(route('patients.index', ['stage' => 'awaiting_assignment']))
            ->assertOk()->assertSeeText($unassigned->name)->assertDontSeeText($pending->name);
        $this->actingAs($manager)->get(route('patients.index', ['stage' => 'awaiting_evaluation']))
            ->assertOk()->assertSeeText($pending->name)->assertDontSeeText($unassigned->name);
    }

    public function test_operational_workspace_exposes_re_evaluation_assignment_only_to_authorized_user(): void
    {
        $manager = $this->userWithPermissions([
            'view patients',
            'manage clinical evaluation assignments',
        ]);
        $viewer = $this->userWithPermissions(['view patients']);
        $clinician = $this->clinician(asTherapistRole: true);
        $patient = $this->patient('PAT-ACTIVE-REEVAL-ACTION');

        $activePlan = PatientServicePlan::create([
            'patient_id' => $patient->id,
            'status' => PatientServicePlan::STATUS_ACTIVE,
        ]);

        $this->actingAs($manager)
            ->get(route('patients.workspace', $patient))
            ->assertOk()
            ->assertSeeText('الخطة العلاجية قيد التنفيذ')
            ->assertSeeText('بدء إعادة تقييم')
            ->assertSeeText('إسناد إعادة التقييم')
            ->assertSeeText($clinician->name);

        $this->actingAs($viewer)
            ->get(route('patients.workspace', $patient))
            ->assertOk()
            ->assertSeeText('الخطة العلاجية قيد التنفيذ')
            ->assertDontSeeText('بدء إعادة تقييم')
            ->assertDontSeeText('إسناد إعادة التقييم');

        $this->actingAs($manager)
            ->post(route('patients.clinical-evaluation-assignment.store', $patient), [
                'assigned_to' => $clinician->id,
            ])
            ->assertRedirect(route('patients.workspace', ['patient' => $patient, 'section' => 'clinical']));

        $this->actingAs($manager)
            ->get(route('patients.workspace', $patient))
            ->assertOk()
            ->assertViewHas('workspace', fn (array $workspace) =>
                $workspace['clinical']['key'] === 'operational'
                && $workspace['clinical']['secondary_key'] === 'awaiting_evaluation'
                && $workspace['currentPlan']->is($activePlan)
            )
            ->assertSeeText('الخطة العلاجية قيد التنفيذ')
            ->assertSeeText('بانتظار تقييم جديد')
            ->assertDontSeeText('بدء إعادة تقييم')
            ->assertDontSeeText('إسناد إعادة التقييم');

        $activePlan->refresh();

        $this->assertSame(
            PatientServicePlan::STATUS_ACTIVE,
            $activePlan->status
        );
    }
    public function test_active_treatment_remains_primary_while_pending_re_evaluation_overlaps_filters(): void
    {
        $manager = $this->userWithPermissions(['view patients', 'manage clinical evaluation assignments']);
        $clinician = $this->clinician(asTherapistRole: true);
        $patient = $this->patient('PAT-ACTIVE-REEVAL');
        PatientServicePlan::create(['patient_id' => $patient->id, 'status' => PatientServicePlan::STATUS_ACTIVE]);
        $this->actingAs($manager)->post(route('patients.clinical-evaluation-assignment.store', $patient), ['assigned_to' => $clinician->id]);

        $this->actingAs($manager)->get(route('patients.workspace', $patient))
            ->assertOk()
            ->assertViewHas('workspace', fn (array $workspace) => $workspace['clinical']['key'] === 'operational'
                && $workspace['clinical']['secondary_key'] === 'awaiting_evaluation')
            ->assertSeeText('الخطة العلاجية قيد التنفيذ')
            ->assertSeeText('بانتظار تقييم جديد');

        $this->actingAs($manager)->get(route('patients.index', ['stage' => 'operational']))
            ->assertOk()->assertSeeText($patient->name)->assertSeeText('إعادة تقييم');
        $this->actingAs($manager)->get(route('patients.index', ['stage' => 'awaiting_evaluation']))
            ->assertOk()->assertSeeText($patient->name);
    }

    public function test_historical_access_cannot_start_re_evaluation_without_a_fresh_assignment(): void
    {
        $reception = $this->userWithPermissions(['view patients', 'manage clinical evaluation assignments']);
        $clinician = $this->clinician(asTherapistRole: true);
        $otherClinician = $this->clinician(asTherapistRole: true);
        $patient = $this->patient('PAT-HISTORICAL-ACCESS');

        $this->actingAs($reception)->post(route('patients.clinical-evaluation-assignment.store', $patient), ['assigned_to' => $clinician->id]);
        $this->actingAs($clinician)->post(route('patients.clinical-evaluation.save-draft', $patient), ['clinical_summary' => 'التقييم الأول']);
        $firstEvaluation = PatientClinicalEvaluation::sole();
        $this->actingAs($clinician)->post(route('patients.clinical-evaluation.complete', [$patient, $firstEvaluation]), ['clinical_summary' => 'اكتمل التقييم الأول']);

        $this->actingAs($clinician)->get(route('patients.workspace', $patient))->assertOk();
        $this->actingAs($clinician)->post(route('patients.clinical-evaluation.save-draft', $patient), [
            'clinical_summary' => 'محاولة إعادة تقييم بلا إسناد',
        ])->assertSessionHasErrors('clinical_summary');
        $this->assertDatabaseCount('patient_clinical_evaluations', 1);

        $this->actingAs($otherClinician)->post(route('patients.clinical-evaluation.save-draft', $patient), [
            'clinical_summary' => 'محاولة مختص غير مسند',
        ])->assertForbidden();

        $this->actingAs($reception)->post(route('patients.clinical-evaluation-assignment.store', $patient), ['assigned_to' => $clinician->id]);
        $this->actingAs($clinician)->post(route('patients.clinical-evaluation.save-draft', $patient), [
            'clinical_summary' => 'إعادة تقييم مسندة',
        ])->assertRedirect(route('patients.workspace', ['patient' => $patient, 'section' => 'clinical']));
        $this->assertDatabaseCount('patient_clinical_evaluations', 2);

        $admin = $this->userWithPermissions(['view patients', 'edit patients', 'manage clinical evaluations']);
        $administrativePatient = $this->patient('PAT-ADMIN-CONTINUITY');
        $this->actingAs($admin)->post(route('patients.clinical-evaluation.save-draft', $administrativePatient), [
            'clinical_summary' => 'مسودة إدارية بلا إسناد',
        ])->assertRedirect(route('patients.workspace', ['patient' => $administrativePatient, 'section' => 'clinical']));
        $this->assertDatabaseMissing('patient_clinical_evaluation_assignments', ['patient_id' => $administrativePatient->id]);
    }

    public function test_unassigned_administrative_draft_blocks_new_assignment_without_changing_owner(): void
    {
        $admin = $this->userWithPermissions(['view patients', 'edit patients', 'manage clinical evaluations']);
        $reception = $this->userWithPermissions(['view patients', 'manage clinical evaluation assignments']);
        $clinician = $this->clinician();
        $patient = $this->patient('PAT-ADMIN-DRAFT');

        $this->actingAs($admin)->post(route('patients.clinical-evaluation.save-draft', $patient), [
            'clinical_summary' => 'مسودة إدارية قائمة',
        ])->assertRedirect(route('patients.workspace', ['patient' => $patient, 'section' => 'clinical']));
        $draft = PatientClinicalEvaluation::sole();

        $this->actingAs($reception)->post(route('patients.clinical-evaluation-assignment.store', $patient), [
            'assigned_to' => $clinician->id,
        ])->assertSessionHasErrors('assigned_to');

        $this->assertDatabaseCount('patient_clinical_evaluation_assignments', 0);
        $this->assertSame($admin->id, $draft->fresh()->evaluated_by);
        $this->assertSame('مسودة إدارية قائمة', $draft->clinical_summary);
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

    private function clinician(bool $activeTherapist = true, bool $asTherapistRole = false): User
    {
        $user = $this->userWithPermissions(['view patients', 'manage clinical evaluations']);
        Therapist::create(['user_id' => $user->id, 'name' => $user->name, 'is_active' => $activeTherapist]);
        if ($asTherapistRole) {
            Role::firstOrCreate(['name' => 'أخصائي تخاطب', 'guard_name' => 'web']);
            $user->assignRole('أخصائي تخاطب');
        }

        return $user;
    }

    private function patient(string $barcode): Patient
    {
        $guardian = Guardian::create(['name' => 'ولي أمر '.$barcode, 'phone' => '010'.str_pad((string) (Guardian::count() + 1), 8, '0', STR_PAD_LEFT)]);

        return Patient::create(['guardian_id' => $guardian->id, 'name' => 'حالة '.$barcode, 'birth_date' => '2020-01-01', 'gender' => 'male', 'barcode' => $barcode, 'qr_code' => $barcode, 'is_active' => true]);
    }

    private function completedEvaluation(Patient $patient, User $user): PatientClinicalEvaluation
    {
        return PatientClinicalEvaluation::create(['patient_id' => $patient->id, 'status' => PatientClinicalEvaluation::STATUS_COMPLETED, 'clinical_summary' => 'مكتمل', 'evaluated_by' => $user->id, 'completed_by' => $user->id, 'completed_at' => now()]);
    }

    private function service(): Service
    {
        $specialty = Specialty::create(['name' => 'تخصص '.uniqid(), 'is_active' => true]);

        return Service::create(['specialty_id' => $specialty->id, 'name' => 'خدمة سريرية', 'default_duration_minutes' => 45, 'customer_price' => '100.00', 'is_active' => true]);
    }

    private function plan(Patient $patient, ?PatientClinicalEvaluation $evaluation, Service $service, bool $approved): PatientServicePlan
    {
        $plan = PatientServicePlan::create(['patient_id' => $patient->id, 'clinical_evaluation_id' => $evaluation?->id, 'status' => PatientServicePlan::STATUS_DRAFT, 'clinical_approved_by' => $approved ? $evaluation?->completed_by : null, 'clinical_approved_at' => $approved ? now() : null]);
        $plan->items()->create(['service_id' => $service->id, 'position' => 1, 'planned_quantity' => 4, 'customer_unit_price' => '100.00', 'discount_amount' => '0.00', 'final_unit_price' => '100.00']);

        return $plan;
    }
}
