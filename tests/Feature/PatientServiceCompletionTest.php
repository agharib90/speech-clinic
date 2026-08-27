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
use App\Models\PatientServicePlanItem;
use App\Models\PatientServicePlanPayment;
use App\Models\Service;
use App\Models\SessionType;
use App\Models\Specialty;
use App\Models\Therapist;
use App\Models\TherapistEarning;
use App\Models\TherapistServiceRate;
use App\Models\TherapySession;
use App\Models\User;
use App\Services\PatientServiceCompletionService;
use App\Services\PatientServicePlanAllocator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PatientServiceCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-08-23 20:00:00', config('app.timezone')));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_completion_requires_dedicated_clinical_permission_and_ready_action_is_visible(): void
    {
        $authorized = $this->userWithPermissions(['view patients', 'view appointments', 'edit therapy']);
        $receptionist = $this->userWithPermissions(['manage checkins', 'edit appointments']);
        $fixture = $this->fixture();
        $this->fund($fixture['plan'], '200.00');
        $this->attend($fixture['appointment'], $authorized);

        $this->actingAs($authorized)
            ->get(route('patients.workspace', $fixture['patient']))
            ->assertOk()
            ->assertSeeText('جاهز لإتمام الخدمة')
            ->assertSee(route('appointments.complete-service', $fixture['appointment']), false);

        $this->actingAs($receptionist)
            ->post(route('appointments.complete-service', $fixture['appointment']))
            ->assertForbidden();

        $this->assertSame('مجدول', $fixture['appointment']->fresh()->status);
        $this->assertDatabaseCount('therapy_sessions', 0);
    }

    public function test_therapist_cannot_complete_another_specialists_appointment(): void
    {
        $therapistUser = $this->userWithRole('أخصائي تخاطب', ['edit therapy']);
        $fixture = $this->fixture();
        $this->fund($fixture['plan'], '200.00');
        $this->attend($fixture['appointment'], $therapistUser);

        $this->actingAs($therapistUser)
            ->post(route('appointments.complete-service', $fixture['appointment']))
            ->assertForbidden();

        $this->assertSame(0, $fixture['item']->fresh()->consumed_quantity);
        $this->assertDatabaseCount('therapy_sessions', 0);
    }

    public function test_assigned_specialist_can_complete_their_own_v2_service(): void
    {
        $fixture = $this->fixture();
        $specialist = $fixture['therapist']->user;
        $permission = Permission::firstOrCreate(['name' => 'edit therapy', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'أخصائي تخاطب', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $specialist->assignRole($role);
        $this->fund($fixture['plan'], '200.00');
        $this->attend($fixture['appointment'], $specialist);
        Carbon::setTestNow(Carbon::parse('2026-08-23 09:30:00', config('app.timezone')));

        $this->actingAs($specialist)
            ->post(route('appointments.complete-service', $fixture['appointment']))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $session = TherapySession::where('appointment_id', $fixture['appointment']->id)->firstOrFail();

        $this->assertSame('مكتمل', $fixture['appointment']->fresh()->status);
        $this->assertSame(1, $fixture['item']->fresh()->consumed_quantity);
        $this->assertDatabaseHas('therapist_earnings', [
            'therapy_session_id' => $session->id,
            'therapist_id' => $fixture['therapist']->id,
        ]);
    }

    public function test_completion_is_blocked_before_during_and_one_minute_before_appointment_end(): void
    {
        $actor = $this->userWithPermissions(['view patients', 'view appointments', 'edit therapy']);
        $fixture = $this->fixture(scheduledAt: '2026-08-23 16:30:00');
        $this->fund($fixture['plan'], '200.00');
        $this->attend($fixture['appointment'], $actor);

        foreach (['16:00:00', '16:45:00', '16:59:00'] as $time) {
            Carbon::setTestNow(Carbon::parse("2026-08-23 {$time}", config('app.timezone')));
            $appointment = $fixture['appointment']->fresh([
                'checkin',
                'patientServicePlanItem.plan',
                'patientServicePlanItem.service',
            ]);
            $state = app(PatientServiceCompletionService::class)->state($appointment, $actor);

            $this->assertSame('in_progress', $state['code']);
            $this->assertFalse($state['can_complete']);

            $this->actingAs($actor)
                ->post(route('appointments.complete-service', $appointment))
                ->assertSessionHasErrors('completion');
        }

        Carbon::setTestNow(Carbon::parse('2026-08-23 16:00:00', config('app.timezone')));
        $this->actingAs($actor)
            ->get(route('patients.workspace', $fixture['patient']))
            ->assertOk()
            ->assertSeeText('يمكن إتمام الخدمة بعد انتهاء الموعد')
            ->assertDontSee(route('appointments.complete-service', $fixture['appointment']), false);

        $this->assertSame('مجدول', $fixture['appointment']->fresh()->status);
        $this->assertSame(0, $fixture['item']->fresh()->consumed_quantity);
        $this->assertDatabaseCount('therapy_sessions', 0);
        $this->assertDatabaseCount('therapist_earnings', 0);
    }

    public function test_completion_is_allowed_at_end_after_end_and_without_late_cutoff(): void
    {
        $actor = $this->userWithPermissions(['edit therapy']);

        foreach (['17:00:00', '17:01:00', '19:00:00'] as $index => $time) {
            $fixture = $this->fixture(
                serviceName: 'Timed Service '.($index + 1),
                scheduledAt: '2026-08-23 16:30:00'
            );
            $this->fund($fixture['plan'], '200.00');
            $this->attend($fixture['appointment'], $actor);
            Carbon::setTestNow(Carbon::parse("2026-08-23 {$time}", config('app.timezone')));

            $this->actingAs($actor)
                ->post(route('appointments.complete-service', $fixture['appointment']))
                ->assertRedirect()
                ->assertSessionHasNoErrors();

            $this->assertSame('مكتمل', $fixture['appointment']->fresh()->status);
            $this->assertSame(1, $fixture['item']->fresh()->consumed_quantity);
            $this->assertDatabaseHas('therapy_sessions', ['appointment_id' => $fixture['appointment']->id]);
        }

        $this->assertDatabaseCount('therapy_sessions', 3);
        $this->assertDatabaseCount('therapist_earnings', 3);
    }

    public function test_completion_requires_attendance_for_the_exact_appointment(): void
    {
        $actor = $this->userWithPermissions(['edit therapy']);
        $fixture = $this->fixture();
        $this->fund($fixture['plan'], '200.00');

        $this->actingAs($actor)
            ->post(route('appointments.complete-service', $fixture['appointment']))
            ->assertSessionHasErrors('completion');

        $otherAppointment = $this->appointment(
            $fixture['patient'],
            $fixture['therapist']->user,
            $fixture['item'],
            '2026-08-23 11:00:00'
        );
        $this->attend($otherAppointment, $actor);

        $this->actingAs($actor)
            ->post(route('appointments.complete-service', $fixture['appointment']))
            ->assertSessionHasErrors('completion');

        $this->assertDatabaseMissing('therapy_sessions', [
            'appointment_id' => $fixture['appointment']->id,
        ]);
        $this->assertSame(0, $fixture['item']->fresh()->consumed_quantity);
    }

    public function test_deposit_only_is_rejected_then_combined_full_funding_allows_completion(): void
    {
        $actor = $this->userWithPermissions(['edit therapy']);
        $fixture = $this->fixture();
        $this->attend($fixture['appointment'], $actor);

        $this->fund($fixture['plan'], '100.00');
        $this->assertSame(0, $fixture['item']->fresh()->authorized_quantity);

        $this->actingAs($actor)
            ->post(route('appointments.complete-service', $fixture['appointment']))
            ->assertSessionHasErrors('completion');

        $this->assertSame(0, $fixture['item']->fresh()->consumed_quantity);
        $this->fund($fixture['plan'], '100.00');
        $this->assertSame(1, $fixture['item']->fresh()->authorized_quantity);

        $this->actingAs($actor)
            ->post(route('appointments.complete-service', $fixture['appointment']))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $fixture['item']->fresh()->consumed_quantity);
    }

    public function test_successful_completion_creates_one_session_and_rate_snapshot_without_finance_movement(): void
    {
        $actor = $this->userWithPermissions(['edit therapy']);
        $fixture = $this->fixture(customerPrice: '200.00', rate: '80.00');
        $this->fund($fixture['plan'], '200.00');
        $this->attend($fixture['appointment'], $actor);
        $fixture['service']->update(['customer_price' => '350.00']);
        $financeCounts = $this->financeCounts();

        $this->actingAs($actor)
            ->post(route('appointments.complete-service', $fixture['appointment']))
            ->assertRedirect()
            ->assertSessionHas('success');

        $session = TherapySession::where('appointment_id', $fixture['appointment']->id)->firstOrFail();
        $earning = TherapistEarning::where('therapy_session_id', $session->id)->firstOrFail();

        $this->assertSame('مكتمل', $fixture['appointment']->fresh()->status);
        $this->assertSame(1, $fixture['item']->fresh()->consumed_quantity);
        $this->assertSame('200.00', $fixture['item']->fresh()->final_unit_price);
        $this->assertSame($fixture['appointment']->id, $session->appointment_id);
        $this->assertSame($fixture['therapist']->id, $earning->therapist_id);
        $this->assertSame('80.00', $earning->amount);
        $this->assertSame($financeCounts, $this->financeCounts());
    }

    public function test_earning_snapshot_does_not_change_when_a_future_rate_is_added(): void
    {
        $actor = $this->userWithPermissions(['edit therapy']);
        $fixture = $this->fixture(rate: '80.00');
        $this->fund($fixture['plan'], '200.00');
        $this->attend($fixture['appointment'], $actor);

        $this->actingAs($actor)
            ->post(route('appointments.complete-service', $fixture['appointment']))
            ->assertSessionHasNoErrors();

        $earning = TherapistEarning::firstOrFail();
        TherapistServiceRate::start(
            $fixture['therapist'],
            $fixture['service'],
            '120.00',
            '2026-09-01',
            $actor->id
        );

        $this->assertSame('80.00', $earning->fresh()->amount);
        $this->assertSame(
            '120.00',
            TherapistServiceRate::resolveFor($fixture['therapist'], $fixture['service'], '2026-09-01')->amount
        );
    }

    public function test_repeated_completion_is_idempotent(): void
    {
        $actor = $this->userWithPermissions(['edit therapy']);
        $fixture = $this->fixture();
        $this->fund($fixture['plan'], '400.00');
        $this->attend($fixture['appointment'], $actor);

        $this->actingAs($actor)->post(route('appointments.complete-service', $fixture['appointment']))
            ->assertSessionHasNoErrors();
        $this->actingAs($actor)->post(route('appointments.complete-service', $fixture['appointment']))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $fixture['item']->fresh()->consumed_quantity);
        $this->assertSame(1, TherapySession::where('appointment_id', $fixture['appointment']->id)->count());
        $this->assertDatabaseCount('therapist_earnings', 1);
    }

    public function test_mismatched_patient_relationship_cannot_consume_plan_item(): void
    {
        $actor = $this->userWithPermissions(['edit therapy']);
        $fixture = $this->fixture();
        $otherPatient = $this->patient('Other Completion Patient');
        $forgedAppointment = $this->appointment(
            $otherPatient,
            $fixture['therapist']->user,
            $fixture['item'],
            '2026-08-23 12:00:00'
        );
        $this->fund($fixture['plan'], '200.00');
        $this->attend($forgedAppointment, $actor);

        $this->actingAs($actor)
            ->post(route('appointments.complete-service', $forgedAppointment))
            ->assertSessionHasErrors('completion');

        $this->assertSame(0, $fixture['item']->fresh()->consumed_quantity);
        $this->assertDatabaseCount('therapy_sessions', 0);
    }

    public function test_multiple_same_day_services_complete_independently(): void
    {
        $actor = $this->userWithPermissions(['edit therapy']);
        $first = $this->fixture(serviceName: 'Service A', rate: '80.00');
        $secondService = Service::create([
            'specialty_id' => $first['specialty']->id,
            'name' => 'Service B',
            'default_duration_minutes' => 45,
            'customer_price' => '200.00',
            'is_active' => true,
        ]);
        $secondUser = User::factory()->create();
        $secondTherapist = Therapist::create([
            'user_id' => $secondUser->id,
            'name' => 'Specialist B',
            'salary_type' => 'monthly',
            'is_active' => true,
        ]);
        $secondTherapist->specialties()->attach($first['specialty']);
        $secondTherapist->services()->attach($secondService);
        TherapistServiceRate::start($secondTherapist, $secondService, '110.00', '2026-01-01');
        $secondItem = PatientServicePlanItem::create([
            'patient_service_plan_id' => $first['plan']->id,
            'service_id' => $secondService->id,
            'position' => 2,
            'planned_quantity' => 1,
            'customer_unit_price' => '200.00',
            'discount_amount' => '0.00',
            'final_unit_price' => '200.00',
        ]);
        $secondAppointment = $this->appointment(
            $first['patient'],
            $secondUser,
            $secondItem,
            '2026-08-23 11:00:00',
            45
        );
        $this->fund($first['plan'], '600.00');
        $this->attend($first['appointment'], $actor);
        $this->attend($secondAppointment, $actor);

        $this->actingAs($actor)
            ->post(route('appointments.complete-service', $first['appointment']))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $first['item']->fresh()->consumed_quantity);
        $this->assertSame(1, $secondItem->fresh()->authorized_quantity);
        $this->assertSame(0, $secondItem->fresh()->consumed_quantity);
        $this->assertSame('مجدول', $secondAppointment->fresh()->status);
        $this->assertDatabaseHas('therapist_earnings', ['therapist_id' => $first['therapist']->id]);
        $this->assertDatabaseMissing('therapist_earnings', ['therapist_id' => $secondTherapist->id]);
    }

    public function test_cancelled_exhausted_and_legacy_appointments_are_rejected_by_v2_path(): void
    {
        $actor = $this->userWithPermissions(['edit therapy']);
        $cancelled = $this->fixture();
        $this->fund($cancelled['plan'], '200.00');
        $this->attend($cancelled['appointment'], $actor);
        $cancelled['appointment']->update(['status' => 'ملغى']);

        $this->actingAs($actor)
            ->post(route('appointments.complete-service', $cancelled['appointment']))
            ->assertSessionHasErrors('completion');

        $exhausted = $this->fixture(serviceName: 'Exhausted Service');
        $exhausted['item']->update([
            'planned_quantity' => 1,
            'authorized_quantity' => 1,
            'consumed_quantity' => 1,
        ]);
        $this->attend($exhausted['appointment'], $actor);

        $this->actingAs($actor)
            ->post(route('appointments.complete-service', $exhausted['appointment']))
            ->assertSessionHasErrors('completion');

        $legacyType = SessionType::create([
            'name' => 'Legacy Type',
            'duration_minutes' => 30,
            'price' => 100,
        ]);
        $legacy = Appointment::create([
            'patient_id' => $cancelled['patient']->id,
            'therapist_id' => $cancelled['therapist']->user_id,
            'session_type_id' => $legacyType->id,
            'scheduled_at' => '2026-08-23 14:00:00',
            'end_at' => '2026-08-23 14:30:00',
            'status' => 'مجدول',
        ]);

        $this->actingAs($actor)
            ->post(route('appointments.complete-service', $legacy))
            ->assertSessionHasErrors('completion');

        $this->assertDatabaseCount('therapy_sessions', 0);
        $this->assertDatabaseCount('therapist_earnings', 0);
    }

    private function fixture(
        string $serviceName = 'Speech Service',
        string $customerPrice = '200.00',
        string $rate = '80.00',
        string $scheduledAt = '2026-08-23 09:00:00'
    ): array {
        $patient = $this->patient($serviceName.' Patient');
        $specialty = Specialty::create([
            'name' => $serviceName.' Specialty '.uniqid(),
            'is_active' => true,
        ]);
        $service = Service::create([
            'specialty_id' => $specialty->id,
            'name' => $serviceName,
            'default_duration_minutes' => 30,
            'customer_price' => $customerPrice,
            'is_active' => true,
        ]);
        $therapistUser = User::factory()->create();
        $therapist = Therapist::create([
            'user_id' => $therapistUser->id,
            'name' => $serviceName.' Specialist',
            'salary_type' => 'monthly',
            'is_active' => true,
        ]);
        $therapist->specialties()->attach($specialty);
        $therapist->services()->attach($service);
        TherapistServiceRate::start($therapist, $service, $rate, '2026-01-01');
        $plan = PatientServicePlan::create([
            'patient_id' => $patient->id,
            'status' => PatientServicePlan::STATUS_ACTIVE,
        ]);
        $item = PatientServicePlanItem::create([
            'patient_service_plan_id' => $plan->id,
            'service_id' => $service->id,
            'position' => 1,
            'planned_quantity' => 2,
            'customer_unit_price' => $customerPrice,
            'discount_amount' => '0.00',
            'final_unit_price' => $customerPrice,
        ]);
        $appointment = $this->appointment($patient, $therapistUser, $item, $scheduledAt);

        return compact(
            'patient',
            'specialty',
            'service',
            'therapist',
            'plan',
            'item',
            'appointment'
        );
    }

    private function appointment(
        Patient $patient,
        User $therapistUser,
        PatientServicePlanItem $item,
        string $scheduledAt,
        int $duration = 30
    ): Appointment {
        return Appointment::create([
            'patient_id' => $patient->id,
            'therapist_id' => $therapistUser->id,
            'patient_service_plan_item_id' => $item->id,
            'scheduled_at' => $scheduledAt,
            'end_at' => Carbon::parse($scheduledAt)->addMinutes($duration),
            'status' => 'مجدول',
            'financially_confirmed_at' => '2026-08-20 12:00:00',
            'confirmation_deposit_percentage_snapshot' => 50,
            'confirmation_deposit_amount_snapshot' => '100.00',
        ]);
    }

    private function fund(PatientServicePlan $plan, string $amount): void
    {
        $invoice = Invoice::firstOrCreate(
            ['patient_id' => $plan->patient_id, 'invoice_number' => 'INV-'.uniqid()],
            [
                'issue_date' => '2026-08-20',
                'status' => 'غير مدفوعة',
                'total' => '1000.00',
            ]
        );
        $payment = InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'payment_date' => '2026-08-20',
            'method' => 'كاش',
        ]);

        app(PatientServicePlanAllocator::class)->allocate($plan, $payment);
    }

    private function attend(Appointment $appointment, User $actor): PatientCheckin
    {
        return PatientCheckin::create([
            'patient_id' => $appointment->patient_id,
            'appointment_id' => $appointment->id,
            'checkin_at' => $appointment->scheduled_at,
            'checked_by' => $actor->id,
            'method' => 'manual',
        ]);
    }

    private function financeCounts(): array
    {
        return [
            'invoices' => Invoice::count(),
            'payments' => InvoicePayment::count(),
            'plan_payments' => PatientServicePlanPayment::count(),
            'allocations' => PatientServicePlanAllocation::count(),
        ];
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
        $user = $this->userWithPermissions($permissions);
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $role->givePermissionTo($permissions);
        $user->assignRole($role);

        return $user;
    }
}
