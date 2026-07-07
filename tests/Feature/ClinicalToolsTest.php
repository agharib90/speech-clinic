<?php

namespace Tests\Feature;

use App\Models\ArticulationAssessment;
use App\Models\Appointment;
use App\Models\ClinicalProgressPoint;
use App\Models\Guardian;
use App\Models\Patient;
use App\Models\SessionMilestone;
use App\Models\SessionType;
use App\Models\StutteringAssessment;
use App\Models\TherapyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ClinicalToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_case_history_can_be_saved_for_patient(): void
    {
        $user = $this->actingAdmin(['edit patients']);
        $patient = $this->createPatient();

        $response = $this->actingAs($user)->post(route('patients.case-history.store', $patient), [
            'taken_at' => '2026-07-01',
            'main_concerns' => 'تأخر في تكوين الجمل',
            'developmental_milestones' => 'المشي في العمر المتوقع والكلام متأخر',
            'language_environment' => 'العربية في المنزل',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('case_histories', [
            'patient_id' => $patient->id,
            'taken_by' => $user->id,
            'main_concerns' => 'تأخر في تكوين الجمل',
        ]);
    }

    public function test_articulation_assessment_summarizes_sound_statuses(): void
    {
        $user = $this->actingAdmin(['edit therapy']);
        $program = $this->createProgram($user);

        $response = $this->actingAs($user)->post(route('programs.articulation-assessments.store', $program), [
            'assessed_at' => '2026-07-02',
            'responses' => [
                ['sound' => 'ب', 'status' => 'correct'],
                ['sound' => 'ر', 'status' => 'substituted', 'substitution' => 'ل'],
                ['sound' => 'ق', 'status' => 'omitted'],
                ['sound' => 'س', 'status' => 'distorted'],
            ],
        ]);

        $response->assertRedirect();

        $assessment = ArticulationAssessment::firstOrFail();
        $this->assertSame(4, $assessment->total_items);
        $this->assertSame(1, $assessment->correct_count);
        $this->assertSame(1, $assessment->substitution_count);
        $this->assertSame(1, $assessment->omission_count);
        $this->assertSame(1, $assessment->distortion_count);
        $this->assertSame('25.00', $assessment->accuracy_percent);
    }

    public function test_stuttering_assessment_calculates_score_and_severity(): void
    {
        $user = $this->actingAdmin(['edit therapy']);
        $program = $this->createProgram($user);

        $response = $this->actingAs($user)->post(route('programs.stuttering-assessments.store', $program), [
            'assessed_at' => '2026-07-03',
            'sample_context' => 'حوار حر',
            'syllables_count' => 200,
            'stuttered_syllables_count' => 12,
            'duration_score' => 5,
            'physical_concomitants_score' => 4,
        ]);

        $response->assertRedirect();

        $assessment = StutteringAssessment::firstOrFail();
        $this->assertSame('6.00', $assessment->stuttering_percentage);
        $this->assertSame(6, $assessment->frequency_score);
        $this->assertSame(15, $assessment->total_score);
        $this->assertSame('متوسطة', $assessment->severity);
    }

    public function test_progress_points_and_discharge_summary_are_stored(): void
    {
        $user = $this->actingAdmin(['edit therapy']);
        $program = $this->createProgram($user);

        $this->actingAs($user)->post(route('programs.progress-points.store', $program), [
            'recorded_at' => '2026-07-01',
            'domain' => 'النطق',
            'score' => 30,
        ])->assertRedirect();

        $this->actingAs($user)->post(route('programs.progress-points.store', $program), [
            'recorded_at' => '2026-07-05',
            'domain' => 'النطق',
            'score' => 55,
        ])->assertRedirect();

        $this->assertSame(2, ClinicalProgressPoint::where('therapy_program_id', $program->id)->count());

        $this->actingAs($user)->post(route('programs.discharge-summary.store', $program), [
            'discharge_date' => '2026-07-06',
            'reason' => 'تحقق الأهداف',
            'final_status' => 'تحسن واضح في الوضوح',
            'goals_outcome' => 'تم تحقيق الأهداف الأساسية',
            'follow_up_plan' => 'متابعة بعد شهر',
            'mark_completed' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('discharge_summaries', [
            'therapy_program_id' => $program->id,
            'reason' => 'تحقق الأهداف',
        ]);
        $this->assertSame('مكتمل', $program->fresh()->status);
        $this->assertSame('2026-07-06', $program->fresh()->end_date);
    }

    public function test_patient_show_renders_case_history_section(): void
    {
        $user = $this->actingAdmin(['view patients', 'edit patients']);
        $patient = $this->createPatient();

        $response = $this->actingAs($user)->get(route('patients.show', $patient));

        $response->assertOk();
        $response->assertSee('التاريخ التطوري وتاريخ الحالة');
    }

    public function test_program_show_renders_clinical_tools(): void
    {
        $user = $this->actingAdmin(['view therapy', 'edit therapy']);
        $program = $this->createProgram($user);
        SessionMilestone::create([
            'therapy_program_id' => $program->id,
            'recorded_at' => '2026-07-01 09:30:00',
            'skill_area' => 'النطق',
            'baseline_score' => 25,
            'current_score' => 65,
        ]);

        $response = $this->actingAs($user)->get(route('programs.show', $program));

        $response->assertOk();
        $response->assertSee('07/01');
        $response->assertSee('بنك اختبار النطق العربي');
        $response->assertSee('رسم التقدم عبر الوقت');
    }

    public function test_progress_report_view_renders_session_milestones(): void
    {
        $user = $this->actingAdmin(['view therapy']);
        $program = $this->createProgram($user);
        SessionMilestone::create([
            'therapy_program_id' => $program->id,
            'recorded_at' => '2026-07-02 10:00:00',
            'skill_area' => 'language',
            'baseline_score' => 30,
            'current_score' => 70,
        ]);

        $program->load(['milestones', 'sessions', 'patient.guardian', 'therapist']);

        $html = view('clinical.programs.progress-pdf', compact('program'))->render();

        $this->assertStringContainsString('language', $html);
        $this->assertStringContainsString('70.00%', $html);
        $this->assertStringContainsString('2026-07-02', $html);
    }

    public function test_program_index_shows_completed_programs_by_default(): void
    {
        $user = $this->actingAdmin(['view therapy']);
        $activeProgram = $this->createProgram($user);
        $completedProgram = $this->createProgram($user);
        $completedProgram->update(['status' => TherapyProgram::STATUS_COMPLETED]);

        $response = $this->actingAs($user)->get(route('programs.index'));

        $response->assertOk();
        $response->assertSee($activeProgram->patient->name);
        $response->assertSee($completedProgram->patient->name);
    }

    public function test_completed_program_is_not_reused_when_converting_new_appointment(): void
    {
        $user = $this->actingAdmin(['edit appointments']);
        $completedProgram = $this->createProgram($user);
        $completedProgram->update(['status' => TherapyProgram::STATUS_COMPLETED]);
        $sessionType = SessionType::create([
            'name' => 'جلسة نطق',
            'duration_minutes' => 30,
            'price' => 150,
        ]);
        $appointment = Appointment::create([
            'patient_id' => $completedProgram->patient_id,
            'therapist_id' => $user->id,
            'session_type_id' => $sessionType->id,
            'scheduled_at' => '2026-07-07 10:00:00',
            'end_at' => '2026-07-07 10:30:00',
            'status' => 'مجدول',
        ]);

        $this->actingAs($user)->post(route('appointments.update-status', $appointment), [
            'status' => 'مكتمل',
        ])->assertRedirect();

        $activeProgram = TherapyProgram::where('patient_id', $completedProgram->patient_id)
            ->where('status', TherapyProgram::STATUS_ACTIVE)
            ->firstOrFail();

        $this->assertSame(2, TherapyProgram::where('patient_id', $completedProgram->patient_id)->count());
        $this->assertDatabaseHas('therapy_sessions', [
            'appointment_id' => $appointment->id,
            'therapy_program_id' => $activeProgram->id,
        ]);
    }

    public function test_therapist_cannot_store_clinical_tool_for_another_therapists_program(): void
    {
        $therapist = $this->actingTherapist(['edit therapy']);
        $owner = User::factory()->create();
        $program = $this->createProgram($owner);

        $response = $this->actingAs($therapist)->post(route('programs.progress-points.store', $program), [
            'recorded_at' => '2026-07-01',
            'domain' => 'النطق',
            'score' => 30,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('clinical_progress_points', 0);
    }

    private function actingAdmin(array $permissions): User
    {
        return $this->userWithRole('مدير النظام', $permissions);
    }

    private function actingTherapist(array $permissions): User
    {
        return $this->userWithRole('أخصائي تخاطب', $permissions);
    }

    private function userWithRole(string $roleName, array $permissions): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $role->syncPermissions($permissions);

        $user = User::factory()->create();
        $user->assignRole($role);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    private function createPatient(): Patient
    {
        $guardian = Guardian::create([
            'name' => 'ولي أمر تجريبي',
            'phone' => '01000000000',
        ]);

        return Patient::create([
            'guardian_id' => $guardian->id,
            'name' => 'طفل تجريبي',
            'birth_date' => '2020-01-01',
            'gender' => 'male',
            'barcode' => 'PAT-TEST-' . uniqid(),
            'qr_code' => 'PAT-TEST',
            'is_active' => true,
        ]);
    }

    private function createProgram(User $therapist): TherapyProgram
    {
        return TherapyProgram::create([
            'name' => 'برنامج تجريبي',
            'patient_id' => $this->createPatient()->id,
            'therapist_id' => $therapist->id,
            'disorder_type' => 'نطق',
            'goals' => 'تحسين وضوح الكلام',
            'status' => 'جاري',
            'start_date' => '2026-07-01',
            'sessions_per_week' => 1,
            'session_price' => 100,
        ]);
    }
}
