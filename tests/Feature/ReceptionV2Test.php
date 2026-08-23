<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Guardian;
use App\Models\Patient;
use App\Models\PatientCheckin;
use App\Models\PatientServicePlan;
use App\Models\PatientServicePlanItem;
use App\Models\Service;
use App\Models\SessionType;
use App\Models\Specialty;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ReceptionV2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setNow('2026-08-23 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_reception_routes_require_manage_checkins_permission(): void
    {
        $plainUser = User::factory()->create();
        $appointment = $this->appointment($this->patient('Unauthorized Patient'), '10:30');

        $this->actingAs($this->receptionist())
            ->get(route('reception.index'))
            ->assertOk();

        $this->actingAs($plainUser)
            ->get(route('reception.index', ['search' => 'Unauthorized']))
            ->assertForbidden();

        $this->actingAs($plainUser)
            ->post(route('reception.scan'), ['barcode' => $appointment->patient->barcode])
            ->assertForbidden();

        $this->actingAs($plainUser)
            ->post(route('reception.attendance.confirm', $appointment), [
                'patient_id' => $appointment->patient_id,
                'method' => 'manual',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('patient_checkins', 0);
    }

    public function test_barcode_and_qr_resolution_select_patient_without_creating_attendance(): void
    {
        $receptionist = $this->receptionist();
        $patient = $this->patient('Barcode Patient');

        foreach ([$patient->barcode, $patient->qr_code] as $code) {
            $response = $this->actingAs($receptionist)
                ->post(route('reception.scan'), ['barcode' => $code]);

            $response->assertRedirect(route('reception.index', [
                'patient' => $patient->id,
                'source' => 'barcode',
            ]));
            $this->assertDatabaseCount('patient_checkins', 0);

            $this->actingAs($receptionist)
                ->get($response->headers->get('Location'))
                ->assertOk()
                ->assertSeeText($patient->name);
        }
    }

    public function test_scanner_identification_does_not_checkout_an_active_checkin(): void
    {
        $receptionist = $this->receptionist();
        $patient = $this->patient('Active Checkin Patient');
        $checkin = PatientCheckin::create([
            'patient_id' => $patient->id,
            'checkin_at' => now()->subMinutes(20),
            'checked_by' => $receptionist->id,
            'method' => 'manual',
        ]);

        $this->actingAs($receptionist)
            ->post(route('reception.scan'), ['barcode' => $patient->barcode])
            ->assertRedirect();

        $this->assertNull($checkin->fresh()->checkout_at);
        $this->assertDatabaseCount('patient_checkins', 1);
    }

    public function test_invalid_barcode_returns_safe_error_without_creating_attendance(): void
    {
        $this->actingAs($this->receptionist())
            ->post(route('reception.scan'), ['barcode' => 'UNKNOWN-CODE'])
            ->assertRedirect(route('reception.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('patient_checkins', 0);
    }

    public function test_reception_shows_all_same_day_appointments_and_manual_search_results(): void
    {
        $receptionist = $this->receptionist();
        $patient = $this->patient('Multiple Services Patient');
        $first = $this->appointment($patient, '09:00', 'Speech Service');
        $second = $this->appointment($patient, '11:30', 'Assessment Service');
        $otherDay = $this->appointment($patient, '14:00', 'Tomorrow Service', 'مجدول', '2026-08-24');

        $this->actingAs($receptionist)
            ->get(route('reception.index', ['search' => 'Multiple Services']))
            ->assertOk()
            ->assertSeeText($patient->name)
            ->assertSeeText($patient->guardian->phone);

        $this->actingAs($receptionist)
            ->get(route('reception.index', ['patient' => $patient->id, 'source' => 'manual']))
            ->assertOk()
            ->assertSeeText('Speech Service')
            ->assertSeeText('Assessment Service')
            ->assertSee('09:00')
            ->assertSee('11:30')
            ->assertSeeInOrder(['09:00', '11:30'])
            ->assertDontSeeText('Tomorrow Service')
            ->assertSee(route('reception.attendance.confirm', $first), false)
            ->assertDontSee(route('reception.attendance.confirm', $second), false)
            ->assertDontSee(route('reception.attendance.confirm', $otherDay), false);
    }

    public function test_appointment_is_visible_but_forged_confirmation_is_rejected_one_minute_too_early(): void
    {
        $this->setNow('2026-08-23 15:59:00');
        $receptionist = $this->receptionist();
        $patient = $this->patient('Early Arrival Patient');
        $appointment = $this->appointment($patient, '16:30');

        $this->actingAs($receptionist)
            ->get(route('reception.index', ['patient' => $patient->id, 'source' => 'manual']))
            ->assertOk()
            ->assertSee('04:30 PM')
            ->assertSeeText('يتاح تأكيد الحضور من 04:00 PM')
            ->assertDontSee(route('reception.attendance.confirm', $appointment), false);

        $this->actingAs($receptionist)
            ->post(route('reception.attendance.confirm', $appointment), [
                'patient_id' => $patient->id,
                'method' => 'manual',
            ])
            ->assertSessionHas('error', 'يتاح تأكيد الحضور من 04:00 PM.');

        $this->assertDatabaseMissing('patient_checkins', [
            'appointment_id' => $appointment->id,
        ]);
    }

    public function test_exact_boundary_uses_application_timezone_and_allows_attendance(): void
    {
        $this->setNow('2026-08-23 16:00:00');
        $receptionist = $this->receptionist();
        $patient = $this->patient('Boundary Patient');
        $appointment = $this->appointment($patient, '16:30');

        $this->assertSame(config('app.timezone'), now()->timezoneName);

        $this->actingAs($receptionist)
            ->post(route('reception.attendance.confirm', $appointment), [
                'patient_id' => $patient->id,
                'method' => 'manual',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('patient_checkins', [
            'appointment_id' => $appointment->id,
            'checkin_at' => '2026-08-23 16:00:00',
        ]);
    }

    public function test_one_minute_after_boundary_allows_attendance(): void
    {
        $this->setNow('2026-08-23 16:01:00');
        $receptionist = $this->receptionist();
        $patient = $this->patient('After Boundary Patient');
        $appointment = $this->appointment($patient, '16:30');

        $this->actingAs($receptionist)
            ->post(route('reception.attendance.confirm', $appointment), [
                'patient_id' => $patient->id,
                'method' => 'manual',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('patient_checkins', [
            'appointment_id' => $appointment->id,
        ]);
    }

    public function test_attendance_after_scheduled_time_has_no_late_cutoff(): void
    {
        $this->setNow('2026-08-23 09:30:00');
        $receptionist = $this->receptionist();
        $patient = $this->patient('Late Attendance Patient');
        $appointment = $this->appointment($patient, '08:00');

        $this->actingAs($receptionist)
            ->post(route('reception.attendance.confirm', $appointment), [
                'patient_id' => $patient->id,
                'method' => 'manual',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('patient_checkins', [
            'appointment_id' => $appointment->id,
        ]);
    }

    public function test_past_appointment_can_be_confirmed_while_later_appointment_remains_too_early(): void
    {
        $this->setNow('2026-08-23 09:30:00');
        $receptionist = $this->receptionist();
        $patient = $this->patient('Mixed Window Patient');
        $pastAppointment = $this->appointment($patient, '08:00', 'Morning Service');
        $futureAppointment = $this->appointment($patient, '16:30', 'Afternoon Service');

        $this->actingAs($receptionist)
            ->get(route('reception.index', ['patient' => $patient->id, 'source' => 'manual']))
            ->assertOk()
            ->assertSeeText('Morning Service')
            ->assertSeeText('Afternoon Service')
            ->assertSee(route('reception.attendance.confirm', $pastAppointment), false)
            ->assertDontSee(route('reception.attendance.confirm', $futureAppointment), false);

        $this->actingAs($receptionist)
            ->post(route('reception.attendance.confirm', $pastAppointment), [
                'patient_id' => $patient->id,
                'method' => 'manual',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('patient_checkins', [
            'appointment_id' => $pastAppointment->id,
        ]);
        $this->assertDatabaseMissing('patient_checkins', [
            'appointment_id' => $futureAppointment->id,
        ]);
    }

    public function test_explicit_confirmation_creates_attendance_for_the_selected_appointment_only(): void
    {
        $receptionist = $this->receptionist();
        $patient = $this->patient('Independent Appointments Patient');
        $first = $this->appointment($patient, '09:00');
        $second = $this->appointment($patient, '11:30');

        $this->actingAs($receptionist)
            ->post(route('reception.attendance.confirm', $first), [
                'patient_id' => $patient->id,
                'method' => 'barcode',
            ])
            ->assertRedirect(route('reception.index', [
                'patient' => $patient->id,
                'source' => 'barcode',
            ]))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('patient_checkins', [
            'patient_id' => $patient->id,
            'appointment_id' => $first->id,
            'checked_by' => $receptionist->id,
            'method' => 'barcode',
        ]);
        $this->assertDatabaseMissing('patient_checkins', [
            'appointment_id' => $second->id,
        ]);
    }

    public function test_confirming_the_same_appointment_twice_does_not_duplicate_attendance(): void
    {
        $receptionist = $this->receptionist();
        $appointment = $this->appointment($this->patient('Duplicate Patient'), '10:30');
        $payload = ['patient_id' => $appointment->patient_id, 'method' => 'manual'];

        $this->actingAs($receptionist)
            ->post(route('reception.attendance.confirm', $appointment), $payload)
            ->assertSessionHas('success');

        $this->actingAs($receptionist)
            ->post(route('reception.attendance.confirm', $appointment), $payload)
            ->assertSessionHas('error');

        $this->assertSame(1, PatientCheckin::where('appointment_id', $appointment->id)->count());
    }

    public function test_cancelled_wrong_day_and_wrong_patient_appointments_cannot_be_confirmed(): void
    {
        $receptionist = $this->receptionist();
        $patient = $this->patient('Rejected Attendance Patient');
        $otherPatient = $this->patient('Other Attendance Patient');
        $cancelled = $this->appointment($patient, '09:00', 'Cancelled', 'ملغى');
        $completed = $this->appointment($patient, '10:00', 'Completed', 'مكتمل');
        $wrongDay = $this->appointment($patient, '09:00', 'Wrong Day', 'مجدول', '2026-08-24');
        $wrongPatient = $this->appointment($otherPatient, '12:00');

        foreach ([$cancelled, $completed, $wrongDay, $wrongPatient] as $appointment) {
            $this->actingAs($receptionist)
                ->post(route('reception.attendance.confirm', $appointment), [
                    'patient_id' => $patient->id,
                    'method' => 'manual',
                ])
                ->assertSessionHas('error');
        }

        $this->assertDatabaseCount('patient_checkins', 0);
    }

    public function test_attendance_confirmation_does_not_change_service_or_financial_state(): void
    {
        $receptionist = $this->receptionist();
        $patient = $this->patient('Financial Boundary Patient');
        $specialty = Specialty::create(['name' => 'Speech', 'is_active' => true]);
        $service = Service::create([
            'specialty_id' => $specialty->id,
            'name' => 'Funded Speech Session',
            'default_duration_minutes' => 30,
            'customer_price' => 200,
            'is_active' => true,
        ]);
        $plan = PatientServicePlan::create([
            'patient_id' => $patient->id,
            'status' => PatientServicePlan::STATUS_ACTIVE,
            'created_by' => $receptionist->id,
        ]);
        $item = PatientServicePlanItem::create([
            'patient_service_plan_id' => $plan->id,
            'service_id' => $service->id,
            'position' => 1,
            'planned_quantity' => 5,
            'customer_unit_price' => 200,
            'discount_amount' => 0,
            'final_unit_price' => 200,
            'authorized_quantity' => 3,
            'consumed_quantity' => 1,
        ]);
        $appointment = $this->appointment($patient, '10:30');
        $appointment->update([
            'patient_service_plan_item_id' => $item->id,
            'financially_confirmed_at' => '2026-08-20 12:00:00',
            'confirmation_deposit_percentage_snapshot' => 50,
            'confirmation_deposit_amount_snapshot' => 100,
        ]);

        $before = [
            'consumed' => $item->consumed_quantity,
            'authorized' => $item->authorized_quantity,
            'financially_confirmed_at' => $appointment->fresh()->financially_confirmed_at?->toDateTimeString(),
            'allocations' => 0,
            'earnings' => 0,
            'invoices' => 0,
            'payments' => 0,
        ];

        $this->actingAs($receptionist)
            ->post(route('reception.attendance.confirm', $appointment), [
                'patient_id' => $patient->id,
                'method' => 'manual',
            ])
            ->assertSessionHas('success');

        $this->assertSame($before['consumed'], $item->fresh()->consumed_quantity);
        $this->assertSame($before['authorized'], $item->fresh()->authorized_quantity);
        $this->assertSame(
            $before['financially_confirmed_at'],
            $appointment->fresh()->financially_confirmed_at?->toDateTimeString()
        );
        $this->assertDatabaseCount('patient_service_plan_allocations', $before['allocations']);
        $this->assertDatabaseCount('therapist_earnings', $before['earnings']);
        $this->assertDatabaseCount('invoices', $before['invoices']);
        $this->assertDatabaseCount('invoice_payments', $before['payments']);
        $this->assertDatabaseCount('therapy_sessions', 0);
    }

    private function receptionist(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name' => 'manage checkins',
            'guard_name' => 'web',
        ]);
        $user = User::factory()->create();
        $user->givePermissionTo($permission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    private function setNow(string $dateTime): void
    {
        Carbon::setTestNow(Carbon::parse($dateTime, config('app.timezone')));
    }

    private function patient(string $name): Patient
    {
        $guardian = Guardian::create([
            'name' => "{$name} Guardian",
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
        string $time,
        string $serviceName = 'Speech Session',
        string $status = 'مجدول',
        string $date = '2026-08-23'
    ): Appointment {
        $therapist = User::factory()->create();
        $sessionType = SessionType::create([
            'name' => $serviceName.' '.uniqid(),
            'duration_minutes' => 30,
            'price' => 150,
        ]);

        return Appointment::create([
            'patient_id' => $patient->id,
            'therapist_id' => $therapist->id,
            'session_type_id' => $sessionType->id,
            'scheduled_at' => "{$date} {$time}:00",
            'end_at' => Carbon::parse("{$date} {$time}:00")->addMinutes(30),
            'status' => $status,
        ]);
    }
}
