<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Guardian;
use App\Models\Patient;
use App\Models\PatientCheckin;
use App\Models\Service;
use App\Models\SessionType;
use App\Models\Specialty;
use App\Models\Therapist;
use App\Models\TherapistEarning;
use App\Models\TherapistServiceRate;
use App\Models\TherapyProgram;
use App\Models\TherapySession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TherapistWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-08-23 12:00:00', config('app.timezone')));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_authorized_user_can_open_workspace_and_see_linked_account(): void
    {
        $viewer = $this->userWithPermissions(['view hr']);
        $linkedUser = User::factory()->create([
            'name' => 'Linked Specialist User',
            'email' => 'linked-specialist@example.test',
        ]);
        $therapist = $this->therapist($linkedUser, ['name' => 'Linked Specialist']);

        $this->actingAs($viewer)
            ->get(route('therapists.show', $therapist))
            ->assertOk()
            ->assertSeeText('مساحة عمل الأخصائي')
            ->assertSeeText('حساب الدخول: مرتبط')
            ->assertSeeText('Linked Specialist User')
            ->assertSeeText('linked-specialist@example.test');
    }

    public function test_appointments_tab_is_primary_and_duplicate_navigation_is_not_rendered(): void
    {
        $viewer = $this->userWithPermissions(['view hr', 'view appointments']);
        $linkedUser = User::factory()->create();
        $therapist = $this->therapist($linkedUser);

        $this->actingAs($viewer)
            ->get(route('therapists.show', $therapist))
            ->assertOk()
            ->assertSeeText('المواعيد')
            ->assertDontSeeText('فتح شاشة المواعيد')
            ->assertDontSee(route('appointments.index', ['therapist_id' => $linkedUser->id]), false);
    }

    public function test_unlinked_therapist_shows_account_and_appointment_empty_state(): void
    {
        $viewer = $this->userWithPermissions(['view hr']);
        $therapist = $this->therapist(null, ['name' => 'Unlinked Specialist']);

        $this->actingAs($viewer)
            ->get(route('therapists.show', ['therapist' => $therapist, 'tab' => 'appointments']))
            ->assertOk()
            ->assertSeeText('حساب الدخول: غير مرتبط')
            ->assertSeeText('لا يمكن عرض مواعيد هذا الأخصائي قبل ربطه بحساب دخول.')
            ->assertSee('data-kpi="today-appointments"', false)
            ->assertSee('data-kpi-value="unavailable"', false);
    }

    public function test_today_and_upcoming_appointments_are_scoped_by_linked_user_id(): void
    {
        $viewer = $this->userWithPermissions(['view hr', 'view appointments', 'view patients']);
        $linkedUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $therapist = $this->therapist($linkedUser);
        $this->therapist($otherUser, ['name' => 'Other Specialist']);
        $sessionType = SessionType::create([
            'name' => 'Workspace Session',
            'duration_minutes' => 30,
            'price' => 100,
        ]);
        $todayPatient = $this->patient('Workspace Today Patient');
        $futurePatient = $this->patient('Workspace Future Patient');
        $otherPatient = $this->patient('Other Therapist Patient');

        $this->appointment($todayPatient, $linkedUser, $sessionType, '2026-08-23 14:00:00');
        $this->appointment($futurePatient, $linkedUser, $sessionType, '2026-08-24 10:00:00');
        $this->appointment($futurePatient, $linkedUser, $sessionType, '2026-08-31 10:00:00');
        $this->appointment($otherPatient, $otherUser, $sessionType, '2026-08-23 15:00:00');
        $this->appointment($otherPatient, $otherUser, $sessionType, '2026-08-25 10:00:00');

        $response = $this->actingAs($viewer)
            ->get(route('therapists.show', ['therapist' => $therapist, 'tab' => 'appointments']));

        $response->assertOk()
            ->assertSeeText('Workspace Today Patient')
            ->assertSeeText('Workspace Future Patient')
            ->assertSeeText('02:00 PM')
            ->assertDontSeeText('Other Therapist Patient')
            ->assertSee('data-kpi="today-appointments"', false)
            ->assertSee('data-kpi="upcoming-appointments"', false);
        $this->assertMatchesRegularExpression(
            '/data-kpi="today-appointments".*?data-kpi-value="1"/s',
            $response->getContent()
        );
        $this->assertMatchesRegularExpression(
            '/data-kpi="upcoming-appointments".*?data-kpi-value="1"/s',
            $response->getContent()
        );
    }

    public function test_completed_session_and_active_case_kpis_use_canonical_scoped_records(): void
    {
        $viewer = $this->userWithPermissions(['view hr', 'view therapy']);
        $linkedUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $therapist = $this->therapist($linkedUser);
        $this->therapist($otherUser);
        $patient = $this->patient('KPI Patient');
        $sessionType = SessionType::create([
            'name' => 'KPI Session',
            'duration_minutes' => 30,
            'price' => 100,
        ]);
        $attendedAppointment = $this->appointment($patient, $linkedUser, $sessionType, '2026-08-23 14:00:00');
        PatientCheckin::create([
            'patient_id' => $patient->id,
            'appointment_id' => $attendedAppointment->id,
            'checkin_at' => '2026-08-23 13:55:00',
            'checked_by' => $viewer->id,
            'method' => 'manual',
        ]);
        $activeProgram = $this->program($patient, $linkedUser, TherapyProgram::STATUS_ACTIVE, 'Own Active Case');
        $this->therapySession($activeProgram, '2026-08-20', 'مكتملة', 1);
        $this->therapySession($activeProgram, '2026-08-21', 'مجدولة', 2);
        $this->program($patient, $linkedUser, TherapyProgram::STATUS_COMPLETED, 'Own Closed Case');
        $otherProgram = $this->program($patient, $otherUser, TherapyProgram::STATUS_ACTIVE, 'Other Active Case');
        $this->therapySession($otherProgram, '2026-08-22', 'مكتملة', 1);

        $response = $this->actingAs($viewer)->get(route('therapists.show', $therapist));

        $response->assertOk();
        $this->assertMatchesRegularExpression(
            '/data-kpi="completed-sessions".*?data-kpi-value="1"/s',
            $response->getContent()
        );
        $this->assertMatchesRegularExpression(
            '/data-kpi="active-cases".*?data-kpi-value="1"/s',
            $response->getContent()
        );
    }

    public function test_monthly_entitlement_kpi_sums_existing_earnings_for_month_and_therapist_only(): void
    {
        $viewer = $this->userWithPermissions(['view hr', 'manage payroll']);
        $linkedUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $therapist = $this->therapist($linkedUser);
        $otherTherapist = $this->therapist($otherUser);
        $patient = $this->patient('Earning KPI Patient');
        $program = $this->program($patient, $linkedUser, TherapyProgram::STATUS_ACTIVE, 'Earning Program');
        $currentSession = $this->therapySession($program, '2026-08-20', 'مكتملة', 1);
        $pastSession = $this->therapySession($program, '2026-07-20', 'مكتملة', 2);
        $otherProgram = $this->program($patient, $otherUser, TherapyProgram::STATUS_ACTIVE, 'Other Earning Program');
        $otherSession = $this->therapySession($otherProgram, '2026-08-20', 'مكتملة', 1);

        TherapistEarning::create(['therapist_id' => $therapist->id, 'therapy_session_id' => $currentSession->id, 'amount' => '80.00', 'type' => 'session_fee']);
        TherapistEarning::create(['therapist_id' => $therapist->id, 'therapy_session_id' => $pastSession->id, 'amount' => '40.00', 'type' => 'session_fee']);
        TherapistEarning::create(['therapist_id' => $otherTherapist->id, 'therapy_session_id' => $otherSession->id, 'amount' => '900.00', 'type' => 'session_fee']);

        $this->actingAs($viewer)
            ->get(route('therapists.show', $therapist))
            ->assertOk()
            ->assertSee('data-kpi="monthly-earnings"', false)
            ->assertSee('data-kpi-value="80.00"', false)
            ->assertSeeText('80.00 ج.م')
            ->assertDontSeeText('900.00 ج.م');
    }

    public function test_services_schedule_and_compensation_are_visible_and_compensation_is_read_only(): void
    {
        $viewer = $this->userWithPermissions(['view hr']);
        $therapist = $this->therapist(User::factory()->create(), [
            'salary_type' => 'monthly',
            'monthly_salary' => '5000.00',
        ]);
        [$specialty, $service] = $this->specialtyAndService();
        $therapist->specialties()->attach($specialty);
        $therapist->services()->attach($service);
        TherapistServiceRate::start($therapist, $service, '100.00', '2026-08-20');
        TherapistServiceRate::start($therapist, $service, '120.00', '2026-08-25');
        $therapist->workPeriods()->create([
            'weekday' => 6,
            'starts_at' => '09:00',
            'ends_at' => '13:00',
        ]);

        $this->actingAs($viewer)
            ->get(route('therapists.show', $therapist))
            ->assertOk()
            ->assertSeeText($specialty->name)
            ->assertSeeText($service->name)
            ->assertSeeText('100.00 ج.م')
            ->assertSee('data-current-rate-value="100.00"', false)
            ->assertSeeText('جدول العمل الأسبوعي')
            ->assertSee('09:00', false)
            ->assertSeeText('5,000.00 ج.م')
            ->assertSeeText('القيم الحالية محفوظة للعرض فقط')
            ->assertDontSee('name="monthly_salary"', false);
    }

    public function test_services_editor_stays_collapsed_and_opens_on_demand_for_authorized_manager(): void
    {
        $manager = $this->userWithPermissions(['view hr', 'manage therapist services']);
        $therapist = $this->therapist(User::factory()->create());
        [$specialty, $service] = $this->specialtyAndService();
        $therapist->specialties()->attach($specialty);
        $therapist->services()->attach($service);
        TherapistServiceRate::start($therapist, $service, '100.00', '2026-08-20');

        $this->actingAs($manager)
            ->get(route('therapists.show', ['therapist' => $therapist, 'tab' => 'services']))
            ->assertOk()
            ->assertSee('x-data="{ editing: false }"', false)
            ->assertSee('x-show="editing"', false)
            ->assertSeeText('إدارة الخدمات والاستحقاقات')
            ->assertSee(route('therapists.services.update', $therapist), false);
    }

    public function test_schedule_uses_compact_rest_rows_and_working_12_hour_time_controls(): void
    {
        $manager = $this->userWithPermissions(['view hr', 'manage therapists']);
        $therapist = $this->therapist(User::factory()->create());
        $therapist->workPeriods()->create([
            'weekday' => 6,
            'starts_at' => '08:10',
            'ends_at' => '14:35',
        ]);

        $this->actingAs($manager)
            ->get(route('therapists.show', ['therapist' => $therapist, 'tab' => 'schedule']))
            ->assertOk()
            ->assertSee('data-rest-day', false)
            ->assertSee('data-schedule-editor', false)
            ->assertSee('data-time12-control="start"', false)
            ->assertSee('data-time12-control="end"', false)
            ->assertSee('data-meridiem-toggle', false)
            ->assertSee('data-time-hour-input', false)
            ->assertSee('data-time-minute-input', false)
            ->assertSee('data-time-hour-up', false)
            ->assertSee('data-time-hour-down', false)
            ->assertSee('data-time-minute-up', false)
            ->assertSee('data-time-minute-down', false)
            ->assertSee('data-time-step="5"', false)
            ->assertSeeText('AM')
            ->assertSeeText('PM')
            ->assertSee('08:10', false)
            ->assertSee('14:35', false)
            ->assertDontSee('type="time"', false)
            ->assertSee(route('therapists.work-schedule.update', $therapist), false);
    }

    public function test_duplicate_effective_date_returns_specific_field_message_without_rewriting_history(): void
    {
        $manager = $this->userWithPermissions(['view hr', 'manage therapist services']);
        $therapist = $this->therapist(User::factory()->create());
        [$specialty, $service] = $this->specialtyAndService();
        $therapist->specialties()->attach($specialty);
        $therapist->services()->attach($service);
        TherapistServiceRate::start($therapist, $service, '100.00', '2026-08-20');
        $message = 'يوجد استحقاق مسجل لهذه الخدمة يبدأ من 20/08/2026 بقيمة 100.00 ج.م.';

        $this->actingAs($manager)
            ->from(route('therapists.show', ['therapist' => $therapist, 'tab' => 'services']))
            ->put(route('therapists.services.update', $therapist), [
                'specialty_ids' => [$specialty->id],
                'service_ids' => [$service->id],
                'rates' => [
                    $service->id => [
                        'amount' => '80.00',
                        'effective_from' => '2026-08-20',
                    ],
                ],
            ])
            ->assertRedirect(route('therapists.show', ['therapist' => $therapist, 'tab' => 'services']))
            ->assertSessionHasErrors("rates.{$service->id}.effective_from");

        $this->actingAs($manager)
            ->get(route('therapists.show', ['therapist' => $therapist, 'tab' => 'services']))
            ->assertOk()
            ->assertSeeText($message);

        $this->assertDatabaseCount('therapist_service_rates', 1);
        $this->assertDatabaseHas('therapist_service_rates', [
            'therapist_id' => $therapist->id,
            'service_id' => $service->id,
            'amount' => '100.00',
            'effective_from' => '2026-08-20 00:00:00',
        ]);
    }

    public function test_viewer_does_not_see_permission_sensitive_mutation_controls(): void
    {
        $viewer = $this->userWithPermissions(['view hr']);
        $therapistUser = User::factory()->create();
        $therapist = $this->therapist($therapistUser);
        $patient = $this->patient('Restricted Appointment Patient');
        $sessionType = SessionType::create([
            'name' => 'Restricted Session',
            'duration_minutes' => 30,
            'price' => 100,
        ]);
        $this->appointment($patient, $therapistUser, $sessionType, '2026-08-23 14:00:00');
        $program = $this->program($patient, $therapistUser, TherapyProgram::STATUS_ACTIVE, 'Restricted KPI Program');
        $session = $this->therapySession($program, '2026-08-20', 'مكتملة', 1);
        TherapistEarning::create([
            'therapist_id' => $therapist->id,
            'therapy_session_id' => $session->id,
            'amount' => '777.00',
            'type' => 'session_fee',
        ]);

        $response = $this->actingAs($viewer)->get(route('therapists.show', $therapist));

        $response->assertOk()
            ->assertSeeText('لا تملك صلاحية عرض المواعيد.')
            ->assertDontSeeText('Restricted Appointment Patient')
            ->assertSee('data-kpi-value="unavailable"', false)
            ->assertDontSeeText('777.00 ج.م')
            ->assertDontSee(route('therapists.edit', $therapist), false)
            ->assertDontSee(route('therapists.services.update', $therapist), false)
            ->assertDontSee(route('therapists.work-schedule.update', $therapist), false);
        foreach (['completed-sessions', 'active-cases', 'monthly-earnings'] as $kpi) {
            $this->assertMatchesRegularExpression(
                '/data-kpi="'.preg_quote($kpi, '/').'".*?data-kpi-value="unavailable"/s',
                $response->getContent()
            );
        }
    }

    private function therapist(?User $user, array $attributes = []): Therapist
    {
        return Therapist::create(array_merge([
            'user_id' => $user?->id,
            'name' => 'Workspace Specialist '.uniqid(),
            'salary_type' => 'monthly',
            'monthly_salary' => '0.00',
            'daily_salary' => '0.00',
            'commission_rate' => '0.00',
            'is_active' => true,
        ], $attributes));
    }

    private function specialtyAndService(): array
    {
        $specialty = Specialty::create([
            'name' => 'Workspace Specialty '.uniqid(),
            'is_active' => true,
        ]);
        $service = Service::create([
            'specialty_id' => $specialty->id,
            'name' => 'Workspace Service '.uniqid(),
            'default_duration_minutes' => 30,
            'customer_price' => '200.00',
            'is_active' => true,
        ]);

        return [$specialty, $service];
    }

    private function patient(string $name): Patient
    {
        $guardian = Guardian::create([
            'name' => $name.' Guardian',
            'phone' => '01012345678',
        ]);

        return Patient::create([
            'guardian_id' => $guardian->id,
            'name' => $name,
            'birth_date' => '2020-01-01',
            'gender' => 'male',
            'barcode' => 'PAT-'.uniqid(),
            'qr_code' => 'QR-'.uniqid(),
            'is_active' => true,
        ]);
    }

    private function appointment(
        Patient $patient,
        User $therapistUser,
        SessionType $sessionType,
        string $scheduledAt
    ): Appointment {
        return Appointment::create([
            'patient_id' => $patient->id,
            'therapist_id' => $therapistUser->id,
            'session_type_id' => $sessionType->id,
            'scheduled_at' => $scheduledAt,
            'end_at' => Carbon::parse($scheduledAt)->addMinutes(30),
            'status' => 'مجدول',
        ]);
    }

    private function program(Patient $patient, User $therapistUser, string $status, string $name): TherapyProgram
    {
        return TherapyProgram::create([
            'patient_id' => $patient->id,
            'therapist_id' => $therapistUser->id,
            'name' => $name,
            'status' => $status,
            'start_date' => '2026-01-01',
            'session_price' => '200.00',
        ]);
    }

    private function therapySession(
        TherapyProgram $program,
        string $sessionDate,
        string $status,
        int $sessionNumber
    ): TherapySession {
        return TherapySession::create([
            'therapy_program_id' => $program->id,
            'session_date' => $sessionDate,
            'session_number' => $sessionNumber,
            'duration_minutes' => 30,
            'status' => $status,
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
}
