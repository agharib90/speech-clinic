<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ClinicalProgressPoint;
use App\Models\Guardian;
use App\Models\Patient;
use App\Models\PatientCheckin;
use App\Models\SessionType;
use App\Models\TherapyProgram;
use App\Models\TherapySession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SecurityAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_reports_permission_or_therapist_role(): void
    {
        $plainUser = User::factory()->create();

        $this->actingAs($plainUser)
            ->get(route('dashboard'))
            ->assertForbidden();

        $reportsUser = $this->userWithPermissions(['view reports']);

        $this->actingAs($reportsUser)
            ->get(route('dashboard'))
            ->assertOk();

        $therapist = $this->userWithRole('أخصائي تخاطب', []);

        $this->actingAs($therapist)
            ->get(route('dashboard'))
            ->assertRedirect(route('therapist.dashboard'));
    }

    public function test_user_management_link_and_access_are_admin_only(): void
    {
        $admin = $this->userWithRole('مدير النظام', ['view reports']);
        $settingsUser = $this->userWithPermissions(['view reports', 'manage settings']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('إدارة المستخدمين');

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk();

        $this->actingAs($settingsUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSeeText('إدارة المستخدمين');

        $this->actingAs($settingsUser)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_super_admin_password_is_not_reset_from_users_screen(): void
    {
        $admin = $this->userWithRole('مدير النظام', []);
        $targetAdmin = $this->userWithRole('مدير النظام', []);
        $originalPassword = $targetAdmin->password;

        $this->actingAs($admin)
            ->post(route('users.reset-password', $targetAdmin))
            ->assertRedirect()
            ->assertSessionHas('error')
            ->assertSessionMissing('temporary_password');

        $this->assertSame($originalPassword, $targetAdmin->fresh()->password);
    }

    public function test_therapist_cases_page_only_shows_assigned_cases(): void
    {
        $therapist = $this->userWithRole('أخصائي تخاطب', ['view patients', 'view appointments', 'view therapy']);
        $otherTherapist = $this->userWithRole('أخصائي تخاطب', ['view patients', 'view appointments', 'view therapy']);
        $sessionType = $this->createSessionType();

        $ownedPatient = $this->createPatient('Owned Cases Child');
        $otherPatient = $this->createPatient('Other Cases Child');
        $ownedProgram = $this->createProgram($therapist, $ownedPatient);
        $ownedProgram->update(['name' => 'Owned Cases Program']);
        $otherProgram = $this->createProgram($otherTherapist, $otherPatient);
        $otherProgram->update(['name' => 'Other Cases Program']);

        $this->createAppointment($therapist, $ownedPatient, $sessionType);
        $this->createAppointment($otherTherapist, $otherPatient, $sessionType);

        $this->actingAs($therapist)
            ->get(route('therapist.cases'))
            ->assertOk()
            ->assertSeeText('حالاتي')
            ->assertSeeText($ownedPatient->name)
            ->assertSeeText('Owned Cases Program')
            ->assertDontSeeText($otherPatient->name)
            ->assertDontSeeText('Other Cases Program');

        $this->actingAs($therapist)
            ->get(route('therapist.cases', ['search' => 'Other Cases']))
            ->assertOk()
            ->assertDontSeeText($otherPatient->name);
    }

    public function test_therapist_dashboard_shows_only_scoped_progress_and_no_admin_or_finance_navigation(): void
    {
        $therapist = $this->userWithRole('أخصائي تخاطب', ['view patients', 'view appointments', 'view therapy']);
        $otherTherapist = $this->userWithRole('أخصائي تخاطب', ['view patients', 'view appointments', 'view therapy']);

        $ownedProgram = $this->createProgram($therapist, $this->createPatient('Dashboard Owned Child'));
        $otherProgram = $this->createProgram($otherTherapist, $this->createPatient('Dashboard Other Child'));

        ClinicalProgressPoint::create([
            'therapy_program_id' => $ownedProgram->id,
            'recorded_by' => $therapist->id,
            'recorded_at' => '2026-07-06',
            'domain' => 'Owned Progress Domain',
            'score' => 75,
            'notes' => 'Owned progress note',
        ]);

        ClinicalProgressPoint::create([
            'therapy_program_id' => $otherProgram->id,
            'recorded_by' => $otherTherapist->id,
            'recorded_at' => '2026-07-06',
            'domain' => 'Other Progress Domain',
            'score' => 80,
            'notes' => 'Other progress note',
        ]);

        $this->actingAs($therapist)
            ->get(route('therapist.dashboard'))
            ->assertOk()
            ->assertSeeText('حالاتي')
            ->assertSeeText('Owned Progress Domain')
            ->assertSeeText('Owned progress note')
            ->assertDontSeeText('Other Progress Domain')
            ->assertDontSeeText('Other progress note')
            ->assertDontSeeText('إدارة المستخدمين')
            ->assertDontSeeText('الفواتير والمالية');
    }

    public function test_therapist_appointments_are_scoped_and_other_appointments_cannot_be_updated(): void
    {
        $therapist = $this->userWithRole('أخصائي تخاطب', ['view appointments', 'edit appointments']);
        $otherTherapist = $this->userWithRole('أخصائي تخاطب', ['view appointments', 'edit appointments']);
        $sessionType = $this->createSessionType();
        $ownedAppointment = $this->createAppointment($therapist, $this->createPatient('Owned Child'), $sessionType);
        $otherAppointment = $this->createAppointment($otherTherapist, $this->createPatient('Other Child'), $sessionType);

        $this->actingAs($therapist)
            ->get(route('appointments.index', ['date' => '2026-07-07']))
            ->assertOk()
            ->assertSee($ownedAppointment->patient->name)
            ->assertDontSee($otherAppointment->patient->name);

        $this->actingAs($therapist)
            ->post(route('appointments.update-status', $otherAppointment), ['status' => 'مكتمل'])
            ->assertForbidden();
    }

    public function test_therapist_patients_are_scoped_and_other_patient_show_is_forbidden(): void
    {
        $therapist = $this->userWithRole('أخصائي تخاطب', ['view patients']);
        $otherTherapist = $this->userWithRole('أخصائي تخاطب', ['view patients']);
        $ownedPatient = $this->createPatient('Owned Patient');
        $otherPatient = $this->createPatient('Other Patient');

        $this->createProgram($therapist, $ownedPatient);
        $this->createProgram($otherTherapist, $otherPatient);

        $this->actingAs($therapist)
            ->get(route('patients.index'))
            ->assertOk()
            ->assertSee($ownedPatient->name)
            ->assertDontSee($otherPatient->name);

        $this->actingAs($therapist)
            ->get(route('patients.show', $otherPatient))
            ->assertForbidden();
    }

    public function test_therapist_cannot_create_home_task_for_another_therapists_session(): void
    {
        $therapist = $this->userWithRole('أخصائي تخاطب', ['manage home tasks']);
        $otherTherapist = $this->userWithRole('أخصائي تخاطب', ['manage home tasks']);
        $session = $this->createTherapySession($this->createProgram($otherTherapist, $this->createPatient('Other Session Patient')));

        $this->actingAs($therapist)
            ->post(route('tasks.store'), [
                'therapy_session_id' => $session->id,
                'description' => 'Practice sound /r/',
                'due_date' => '2026-07-10',
            ])
            ->assertForbidden();
    }

    public function test_attachment_upload_requires_program_access_and_uses_safe_storage_name(): void
    {
        Storage::fake('public');

        $therapist = $this->userWithRole('أخصائي تخاطب', ['edit therapy']);
        $otherTherapist = $this->userWithRole('أخصائي تخاطب', ['edit therapy']);
        $ownedProgram = $this->createProgram($therapist, $this->createPatient('Owned Attachment Patient'));
        $otherProgram = $this->createProgram($otherTherapist, $this->createPatient('Other Attachment Patient'));

        $this->actingAs($therapist)
            ->post(route('attachments.store', $otherProgram), [
                'attachment_type' => 'مرفق_عام',
                'file' => UploadedFile::fake()->create('other-note.pdf', 1, 'application/pdf'),
            ])
            ->assertForbidden();

        $this->actingAs($therapist)
            ->post(route('attachments.store', $ownedProgram), [
                'attachment_type' => 'مرفق_عام',
                'file' => UploadedFile::fake()->create('original-note.pdf', 1, 'application/pdf'),
            ])
            ->assertRedirect();

        $attachment = $ownedProgram->attachments()->firstOrFail();

        $this->assertSame('original-note.pdf', $attachment->file_name);
        $this->assertStringStartsWith('attachments/', $attachment->file_path);
        $this->assertStringNotContainsString('original-note', $attachment->file_path);
        Storage::disk('public')->assertExists($attachment->file_path);
    }

    public function test_reception_page_renders_current_checkins_with_datetime_casts(): void
    {
        $this->travelTo('2026-07-06 12:00:00');

        $receptionist = $this->userWithPermissions(['manage checkins']);
        $patient = $this->createPatient('Reception Patient');

        PatientCheckin::create([
            'patient_id' => $patient->id,
            'checkin_at' => '2026-07-06 11:45:00',
            'checked_by' => $receptionist->id,
            'method' => 'barcode',
        ]);

        $this->actingAs($receptionist)
            ->get(route('reception.index'))
            ->assertOk()
            ->assertSee($patient->name)
            ->assertSee('11:45');
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

    private function createPatient(string $name): Patient
    {
        $guardian = Guardian::create([
            'name' => "{$name} Guardian",
            'phone' => '01000000000',
        ]);

        return Patient::create([
            'guardian_id' => $guardian->id,
            'name' => $name,
            'birth_date' => '2020-01-01',
            'gender' => 'male',
            'barcode' => 'PAT-SEC-' . uniqid(),
            'qr_code' => 'PAT-SEC',
            'is_active' => true,
        ]);
    }

    private function createSessionType(): SessionType
    {
        return SessionType::create([
            'name' => 'Speech Session',
            'duration_minutes' => 30,
            'price' => 150,
        ]);
    }

    private function createAppointment(User $therapist, Patient $patient, SessionType $sessionType): Appointment
    {
        return Appointment::create([
            'patient_id' => $patient->id,
            'therapist_id' => $therapist->id,
            'session_type_id' => $sessionType->id,
            'scheduled_at' => '2026-07-07 10:00:00',
            'end_at' => '2026-07-07 10:30:00',
            'status' => 'مجدول',
        ]);
    }

    private function createProgram(User $therapist, Patient $patient): TherapyProgram
    {
        return TherapyProgram::create([
            'name' => 'Security Test Program',
            'patient_id' => $patient->id,
            'therapist_id' => $therapist->id,
            'disorder_type' => 'Speech',
            'goals' => 'Improve clarity',
            'status' => TherapyProgram::STATUS_ACTIVE,
            'start_date' => '2026-07-01',
            'sessions_per_week' => 1,
            'session_price' => 100,
        ]);
    }

    private function createTherapySession(TherapyProgram $program): TherapySession
    {
        return TherapySession::create([
            'therapy_program_id' => $program->id,
            'session_date' => '2026-07-07',
            'session_number' => 1,
            'duration_minutes' => 30,
            'status' => 'مكتملة',
        ]);
    }
}
