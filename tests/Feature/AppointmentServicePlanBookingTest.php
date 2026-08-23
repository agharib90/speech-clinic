<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PackageUsage;
use App\Models\Patient;
use App\Models\PatientServicePlan;
use App\Models\PatientServicePlanItem;
use App\Models\Service;
use App\Models\SessionPackage;
use App\Models\SessionType;
use App\Models\Setting;
use App\Models\Specialty;
use App\Models\Therapist;
use App\Models\TherapistEarning;
use App\Models\TherapistWorkPeriod;
use App\Models\TherapySession;
use App\Models\User;
use App\Services\AppointmentServicePlanBookingService;
use App\Services\PatientServicePlanAllocator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AppointmentServicePlanBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmation_deposit_setting_defaults_to_fifty_and_accepts_only_one_to_one_hundred(): void
    {
        $manager = $this->userWithPermissions(['manage settings']);
        $this->assertSame(50, app(AppointmentServicePlanBookingService::class)->depositPercentage());
        $this->assertSame(50, Setting::defaults()['appointment_confirmation_deposit_percentage']);

        $this->actingAs($manager)->put(route('settings.update'), $this->financialSettingsPayload(65))
            ->assertSessionHasNoErrors();
        $this->assertSame(65, Setting::firstOrFail()->appointment_confirmation_deposit_percentage);

        $this->actingAs($manager)->put(route('settings.update'), $this->financialSettingsPayload(0))
            ->assertSessionHasErrors('appointment_confirmation_deposit_percentage');
        $this->actingAs($manager)->put(route('settings.update'), $this->financialSettingsPayload(101))
            ->assertSessionHasErrors('appointment_confirmation_deposit_percentage');
    }

    public function test_exact_half_deposit_confirms_booking_but_one_cent_less_is_rejected(): void
    {
        $manager = $this->userWithPermissions(['create appointments']);
        [$item, $therapist] = $this->bookingFixture('300.00');
        $this->fund($item->plan, '149.00');

        $this->actingAs($manager)->post(route('appointments.store'), $this->bookingPayload($item, $therapist, 1))
            ->assertSessionHasErrors('patient_service_plan_item_id');
        $this->assertDatabaseCount('appointments', 0);

        $this->fund($item->plan, '1.00');
        $this->actingAs($manager)->post(route('appointments.store'), $this->bookingPayload($item, $therapist, 1))
            ->assertRedirect();
        $this->assertDatabaseHas('appointments', [
            'patient_service_plan_item_id' => $item->id,
            'confirmation_deposit_percentage_snapshot' => 50,
            'confirmation_deposit_amount_snapshot' => '150.00',
            'status' => 'مجدول',
        ]);
        $this->assertNotNull(Appointment::firstOrFail()->financially_confirmed_at);
    }

    public function test_discounted_final_price_drives_snapshot_and_later_setting_changes_do_not_recalculate_it(): void
    {
        $manager = $this->userWithPermissions(['create appointments']);
        Setting::create(array_merge(Setting::defaults(), ['appointment_confirmation_deposit_percentage' => 50]));
        [$item, $therapist] = $this->bookingFixture('240.00', customerPrice: '300.00', discount: '60.00');
        $this->fund($item->plan, '120.00');

        $this->actingAs($manager)->post(route('appointments.store'), $this->bookingPayload($item, $therapist, 1))
            ->assertRedirect();
        $appointment = Appointment::firstOrFail();
        $this->assertSame('120.00', $appointment->confirmation_deposit_amount_snapshot);

        Setting::firstOrFail()->update(['appointment_confirmation_deposit_percentage' => 75]);
        $this->assertSame(50, $appointment->fresh()->confirmation_deposit_percentage_snapshot);
        $this->assertSame('120.00', $appointment->fresh()->confirmation_deposit_amount_snapshot);
    }

    public function test_one_partial_credit_cannot_confirm_two_appointments_and_funding_transition_is_not_double_counted(): void
    {
        $manager = $this->userWithPermissions(['create appointments']);
        [$item, $therapist] = $this->bookingFixture('300.00', plannedQuantity: 3);
        $this->fund($item->plan, '150.00');

        $this->actingAs($manager)->post(route('appointments.store'), $this->bookingPayload($item, $therapist, 1))
            ->assertRedirect();
        $this->actingAs($manager)->post(route('appointments.store'), $this->bookingPayload($item, $therapist, 2))
            ->assertSessionHasErrors('patient_service_plan_item_id');

        $this->fund($item->plan, '150.00');
        $this->assertSame(1, $item->fresh()->authorized_quantity);
        $this->assertSame('0.00', $item->plan->availableCredit());
        $this->actingAs($manager)->post(route('appointments.store'), $this->bookingPayload($item, $therapist, 2))
            ->assertSessionHasErrors('patient_service_plan_item_id');

        $this->fund($item->plan, '150.00');
        $this->actingAs($manager)->post(route('appointments.store'), $this->bookingPayload($item, $therapist, 2))
            ->assertRedirect();
        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_two_fully_authorized_units_allow_exactly_two_open_bookings(): void
    {
        $manager = $this->userWithPermissions(['create appointments']);
        [$item, $therapist] = $this->bookingFixture('300.00', plannedQuantity: 2);
        $this->fund($item->plan, '600.00');
        $this->assertSame(2, $item->fresh()->authorized_quantity);

        foreach ([1, 2] as $day) {
            $this->actingAs($manager)->post(route('appointments.store'), $this->bookingPayload($item, $therapist, $day))
                ->assertRedirect();
        }

        $this->actingAs($manager)->post(route('appointments.store'), $this->bookingPayload($item, $therapist, 3))
            ->assertSessionHasErrors('patient_service_plan_item_id');
        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_partial_credit_only_books_the_next_unfunded_item_in_plan_order(): void
    {
        $manager = $this->userWithPermissions(['create appointments']);
        [$first, $therapist] = $this->bookingFixture('300.00');
        $secondService = $this->service('خدمة لاحقة', 30);
        $therapist->services()->attach($secondService);
        $second = $first->plan->items()->create([
            'service_id' => $secondService->id,
            'position' => 2,
            'planned_quantity' => 1,
            'customer_unit_price' => '100.00',
            'discount_amount' => '0.00',
            'final_unit_price' => '100.00',
        ]);
        $this->fund($first->plan, '150.00');

        $this->actingAs($manager)->post(route('appointments.store'), $this->bookingPayload($second, $therapist, 1))
            ->assertSessionHasErrors('patient_service_plan_item_id');
        $this->actingAs($manager)->post(route('appointments.store'), $this->bookingPayload($first, $therapist, 1))
            ->assertRedirect();
    }

    public function test_plan_item_must_belong_to_patient_and_plan_must_be_active(): void
    {
        $manager = $this->userWithPermissions(['create appointments']);
        [$item, $therapist] = $this->bookingFixture('100.00');
        $this->fund($item->plan, '100.00');
        $otherPatient = $this->patient();

        $payload = $this->bookingPayload($item, $therapist, 1);
        $payload['patient_id'] = $otherPatient->id;
        $this->actingAs($manager)->post(route('appointments.store'), $payload)
            ->assertSessionHasErrors('patient_service_plan_item_id');

        $item->plan->update(['status' => PatientServicePlan::STATUS_DRAFT]);
        $this->actingAs($manager)->post(route('appointments.store'), $this->bookingPayload($item, $therapist, 1))
            ->assertSessionHasErrors('patient_service_plan_item_id');
    }

    public function test_therapist_must_be_active_assigned_to_service_and_appointment_stores_user_id(): void
    {
        $manager = $this->userWithPermissions(['create appointments']);
        [$item, $therapist] = $this->bookingFixture('100.00');
        $this->fund($item->plan, '100.00');
        $unassignedUser = User::factory()->create();

        $payload = $this->bookingPayload($item, $therapist, 1);
        $payload['therapist_id'] = $unassignedUser->id;
        $this->actingAs($manager)->post(route('appointments.store'), $payload)
            ->assertSessionHasErrors('therapist_id');

        $therapist->update(['is_active' => false]);
        $this->actingAs($manager)->post(route('appointments.store'), $this->bookingPayload($item, $therapist, 1))
            ->assertSessionHasErrors('therapist_id');

        $therapist->update(['is_active' => true]);
        $item->service->update(['is_active' => false]);
        $this->actingAs($manager)->post(route('appointments.store'), $this->bookingPayload($item, $therapist, 1))
            ->assertSessionHasErrors('patient_service_plan_item_id');

        $item->service->update(['is_active' => true]);
        $this->actingAs($manager)->post(route('appointments.store'), $this->bookingPayload($item, $therapist, 1))
            ->assertRedirect();
        $appointment = Appointment::firstOrFail();
        $this->assertSame($therapist->user_id, $appointment->therapist_id);
        $this->assertNotSame($therapist->id, $appointment->therapist_id);
    }

    public function test_service_duration_is_required_drives_end_time_and_conflict_detection_remains_active(): void
    {
        $manager = $this->userWithPermissions(['create appointments']);
        [$item, $therapist] = $this->bookingFixture('100.00', duration: null, plannedQuantity: 2);
        $this->fund($item->plan, '200.00');

        $this->actingAs($manager)->post(route('appointments.store'), $this->bookingPayload($item, $therapist, 1))
            ->assertSessionHasErrors('patient_service_plan_item_id');

        $item->service->update(['default_duration_minutes' => 45]);
        $payload = $this->bookingPayload($item, $therapist, 1);
        $this->actingAs($manager)->post(route('appointments.store'), $payload)->assertRedirect();
        $appointment = Appointment::firstOrFail();
        $this->assertSame(45.0, $appointment->scheduled_at->diffInMinutes($appointment->end_at));

        $overlap = $payload;
        $overlap['scheduled_at'] = $appointment->scheduled_at->copy()->addMinutes(15)->format('Y-m-d\TH:i');
        $this->actingAs($manager)->post(route('appointments.store'), $overlap)
            ->assertSessionHasErrors('therapist_id');
    }

    public function test_v2_booking_rejects_times_outside_schedule_between_shifts_and_crossing_shift_end(): void
    {
        $manager = $this->userWithPermissions(['create appointments']);
        [$item, $therapist] = $this->bookingFixture('100.00', plannedQuantity: 2);
        $this->fund($item->plan, '200.00');
        $date = now()->addDays(30)->startOfDay();

        $therapist->workPeriods()->where('weekday', $date->dayOfWeek)->delete();
        $therapist->workPeriods()->createMany([
            ['weekday' => $date->dayOfWeek, 'starts_at' => '08:00', 'ends_at' => '15:00'],
            ['weekday' => $date->dayOfWeek, 'starts_at' => '18:00', 'ends_at' => '22:00'],
        ]);

        foreach (['07:45', '16:00', '14:45'] as $time) {
            $payload = $this->bookingPayload($item, $therapist, 10);
            $payload['scheduled_at'] = $date->format('Y-m-d').'T'.$time;

            $this->actingAs($manager)->post(route('appointments.store'), $payload)
                ->assertSessionHasErrors('scheduled_at');
        }

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_booking_rechecks_stale_conflicts_allows_adjacent_time_and_ignores_cancelled_appointments(): void
    {
        $manager = $this->userWithPermissions(['create appointments']);
        [$item, $therapist] = $this->bookingFixture('100.00', plannedQuantity: 3);
        $this->fund($item->plan, '300.00');
        $date = now()->addDays(35)->startOfDay();

        $this->actingAs($manager)->getJson(route('appointments.availability', [
            'patient_service_plan_item_id' => $item->id,
            'therapist_id' => $therapist->user_id,
            'date' => $date->toDateString(),
        ]))->assertOk();

        Appointment::create([
            'patient_id' => $item->plan->patient_id,
            'therapist_id' => $therapist->user_id,
            'scheduled_at' => $date->copy()->setTime(9, 0),
            'end_at' => $date->copy()->setTime(9, 30),
            'status' => 'مجدول',
        ]);
        $stalePayload = $this->bookingPayload($item, $therapist, 15);
        $stalePayload['scheduled_at'] = $date->format('Y-m-d').'T09:00';
        $this->actingAs($manager)->post(route('appointments.store'), $stalePayload)
            ->assertSessionHasErrors('therapist_id');

        $adjacentPayload = $stalePayload;
        $adjacentPayload['scheduled_at'] = $date->format('Y-m-d').'T09:30';
        $this->actingAs($manager)->post(route('appointments.store'), $adjacentPayload)
            ->assertRedirect();

        Appointment::create([
            'patient_id' => $item->plan->patient_id,
            'therapist_id' => $therapist->user_id,
            'scheduled_at' => $date->copy()->setTime(11, 0),
            'end_at' => $date->copy()->setTime(11, 30),
            'status' => 'ملغى',
        ]);
        $cancelledPayload = $stalePayload;
        $cancelledPayload['scheduled_at'] = $date->format('Y-m-d').'T11:00';
        $this->actingAs($manager)->post(route('appointments.store'), $cancelledPayload)
            ->assertRedirect();
    }

    public function test_availability_endpoint_rejects_item_after_booking_eligibility_becomes_stale(): void
    {
        $manager = $this->userWithPermissions(['create appointments']);
        [$item, $therapist] = $this->bookingFixture('300.00');
        $this->fund($item->plan, '150.00');
        $date = now()->addDays(40)->setTime(10, 0);
        $endpoint = route('appointments.availability', [
            'patient_service_plan_item_id' => $item->id,
            'therapist_id' => $therapist->user_id,
            'date' => $date->toDateString(),
        ]);

        $this->actingAs($manager)->getJson($endpoint)->assertOk();

        $payload = $this->bookingPayload($item, $therapist, 20);
        $payload['scheduled_at'] = $date->format('Y-m-d\TH:i');
        $this->actingAs($manager)->post(route('appointments.store'), $payload)->assertRedirect();

        $this->actingAs($manager)->getJson($endpoint)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('patient_service_plan_item_id');
    }

    public function test_v2_booking_still_rejects_forged_past_datetime(): void
    {
        $manager = $this->userWithPermissions(['create appointments']);
        [$item, $therapist] = $this->bookingFixture('100.00');
        $this->fund($item->plan, '100.00');
        $payload = $this->bookingPayload($item, $therapist, 1);
        $payload['scheduled_at'] = now()->subMinute()->format('Y-m-d\TH:i');

        $this->actingAs($manager)->post(route('appointments.store'), $payload)
            ->assertSessionHasErrors('scheduled_at');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_legacy_booking_rendering_and_completion_continue_to_work(): void
    {
        $this->travelTo('2026-08-03 09:00:00');

        try {
            $manager = $this->userWithPermissions([
                'create appointments',
                'create legacy appointments',
                'view appointments',
                'edit appointments',
            ]);
            $patient = $this->patient();
            $therapistUser = User::factory()->create();
            $legacyTherapist = Therapist::create([
                'user_id' => $therapistUser->id,
                'name' => 'أخصائي حجز قديم',
                'salary_type' => 'monthly',
                'is_active' => true,
            ]);
            $scheduledAt = now()->addDays(10)->setTime(10, 0);
            $legacyTherapist->workPeriods()->create([
                'weekday' => $scheduledAt->dayOfWeek,
                'starts_at' => '08:00',
                'ends_at' => '17:00',
            ]);
            $sessionType = SessionType::create(['name' => 'جلسة قديمة', 'duration_minutes' => 30, 'price' => 100]);

            $this->actingAs($manager)->post(route('appointments.store'), [
                'patient_id' => $patient->id,
                'therapist_id' => $therapistUser->id,
                'session_type_id' => $sessionType->id,
                'scheduled_at' => $scheduledAt->format('Y-m-d\TH:i'),
                'legacy_booking_reason' => 'إدخال موعد قديم',
            ])->assertRedirect();
            $appointment = Appointment::firstOrFail();

            $this->actingAs($manager)->get(route('appointments.index', ['date' => $appointment->scheduled_at->format('Y-m-d')]))
                ->assertOk()
                ->assertSeeText('جلسة قديمة');
            $this->actingAs($manager)->post(route('appointments.update-status', $appointment), ['status' => 'مكتمل'])
                ->assertRedirect();
            $this->assertDatabaseHas('therapy_sessions', ['appointment_id' => $appointment->id]);
        } finally {
            $this->travelBack();
        }
    }

    public function test_v2_appointment_cannot_use_legacy_completion_and_creates_no_side_effects(): void
    {
        $manager = $this->userWithPermissions(['create appointments', 'edit appointments']);
        [$item, $therapist] = $this->bookingFixture('100.00');
        $this->fund($item->plan, '100.00');
        $this->actingAs($manager)->post(route('appointments.store'), $this->bookingPayload($item, $therapist, 1));
        $appointment = Appointment::firstOrFail();
        SessionPackage::create([
            'patient_id' => $item->plan->patient_id,
            'name' => 'باقة قديمة',
            'total_sessions' => 5,
            'used_sessions' => 0,
            'price_paid' => 500,
            'status' => 'نشط',
        ]);

        $this->actingAs($manager)->post(route('appointments.update-status', $appointment), ['status' => 'مكتمل'])
            ->assertSessionHasErrors('status');
        $this->assertSame('مجدول', $appointment->fresh()->status);
        $this->assertSame(0, TherapySession::count());
        $this->assertSame(0, TherapistEarning::count());
        $this->assertSame(0, PackageUsage::count());
        $this->assertSame(0, SessionPackage::firstOrFail()->used_sessions);
    }

    public function test_v2_and_legacy_names_both_render_in_appointment_list(): void
    {
        $manager = $this->userWithPermissions(['create appointments', 'view appointments']);
        [$item, $therapist] = $this->bookingFixture('100.00');
        $this->fund($item->plan, '100.00');
        $payload = $this->bookingPayload($item, $therapist, 5);
        $this->actingAs($manager)->post(route('appointments.store'), $payload);
        $v2Appointment = Appointment::firstOrFail();

        $legacyType = SessionType::create(['name' => 'نوع جلسة تراثي', 'duration_minutes' => 30, 'price' => 100]);
        Appointment::create([
            'patient_id' => $item->plan->patient_id,
            'therapist_id' => $therapist->user_id,
            'session_type_id' => $legacyType->id,
            'scheduled_at' => $v2Appointment->scheduled_at->copy()->addHours(3),
            'end_at' => $v2Appointment->scheduled_at->copy()->addHours(3)->addMinutes(30),
            'status' => 'مجدول',
        ]);

        $this->actingAs($manager)->get(route('appointments.index', ['date' => $v2Appointment->scheduled_at->format('Y-m-d')]))
            ->assertOk()
            ->assertSeeText($item->service->name)
            ->assertSeeText('نوع جلسة تراثي');
    }

    public function test_phase_two_plan_order_allocation_example_remains_unchanged(): void
    {
        $patient = $this->patient();
        $plan = PatientServicePlan::create(['patient_id' => $patient->id, 'status' => PatientServicePlan::STATUS_ACTIVE]);
        foreach ([['300.00', 1], ['200.00', 1], ['100.00', 5]] as $position => [$price, $quantity]) {
            $service = $this->service('خدمة '.($position + 1), 30);
            $plan->items()->create([
                'service_id' => $service->id,
                'position' => $position + 1,
                'planned_quantity' => $quantity,
                'customer_unit_price' => $price,
                'discount_amount' => '0.00',
                'final_unit_price' => $price,
            ]);
        }

        $this->fund($plan, '750.00');
        $this->assertSame([1, 1, 2], $plan->items()->pluck('authorized_quantity')->all());
        $this->assertSame('50.00', $plan->availableCredit());
    }

    private function bookingFixture(
        string $finalPrice,
        ?int $duration = 30,
        int $plannedQuantity = 1,
        ?string $customerPrice = null,
        string $discount = '0.00'
    ): array {
        $patient = $this->patient();
        $service = $this->service('خدمة حجز', $duration);
        $plan = PatientServicePlan::create(['patient_id' => $patient->id, 'status' => PatientServicePlan::STATUS_ACTIVE]);
        $item = $plan->items()->create([
            'service_id' => $service->id,
            'position' => 1,
            'planned_quantity' => $plannedQuantity,
            'customer_unit_price' => $customerPrice ?? $finalPrice,
            'discount_amount' => $discount,
            'final_unit_price' => $finalPrice,
        ]);
        $therapistUser = User::factory()->create();
        $therapist = Therapist::create([
            'user_id' => $therapistUser->id,
            'name' => 'أخصائي '.uniqid(),
            'salary_type' => 'monthly',
            'monthly_salary' => 5000,
            'daily_salary' => 0,
            'commission_rate' => 0,
            'is_active' => true,
        ]);
        $therapist->services()->attach($service);
        foreach (array_keys(TherapistWorkPeriod::WEEKDAYS) as $weekday) {
            $therapist->workPeriods()->create([
                'weekday' => $weekday,
                'starts_at' => '08:00',
                'ends_at' => '22:00',
            ]);
        }

        return [$item, $therapist];
    }

    private function patient(): Patient
    {
        $guardian = Guardian::create(['name' => 'ولي أمر '.uniqid(), 'phone' => '010'.random_int(10000000, 99999999)]);

        return Patient::create([
            'guardian_id' => $guardian->id,
            'name' => 'طفل '.uniqid(),
            'birth_date' => '2020-01-01',
            'gender' => 'male',
            'barcode' => 'PAT-'.uniqid(),
            'qr_code' => 'PAT-'.uniqid(),
            'is_active' => true,
        ]);
    }

    private function service(string $name, ?int $duration): Service
    {
        $specialty = Specialty::create(['name' => 'تخصص '.uniqid(), 'is_active' => true]);

        return Service::create([
            'specialty_id' => $specialty->id,
            'name' => $name.' '.uniqid(),
            'default_duration_minutes' => $duration,
            'is_active' => true,
        ]);
    }

    private function fund(PatientServicePlan $plan, string $amount): void
    {
        $invoice = Invoice::create([
            'patient_id' => $plan->patient_id,
            'invoice_number' => 'INV-'.uniqid(),
            'issue_date' => now()->toDateString(),
            'status' => 'مدفوعة جزئياً',
            'total' => $amount,
        ]);
        $payment = InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'payment_date' => now()->toDateString(),
            'method' => 'كاش',
        ]);

        app(PatientServicePlanAllocator::class)->allocate($plan, $payment);
    }

    private function bookingPayload(PatientServicePlanItem $item, Therapist $therapist, int $dayOffset): array
    {
        return [
            'patient_id' => $item->plan->patient_id,
            'patient_service_plan_item_id' => $item->id,
            'therapist_id' => $therapist->user_id,
            'scheduled_at' => now()->addDays(20 + $dayOffset)->setTime(10, 0)->format('Y-m-d\TH:i'),
            'notes' => 'موعد مؤكد ماليًا',
        ];
    }

    private function financialSettingsPayload(int $percentage): array
    {
        return [
            'section' => 'financial',
            'currency_code' => 'EGP',
            'currency_symbol' => 'ج.م',
            'default_therapist_commission_rate' => 0,
            'appointment_confirmation_deposit_percentage' => $percentage,
        ];
    }

    private function userWithPermissions(array $permissions): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => 'أخصائي تخاطب', 'guard_name' => 'web']);

        $user->givePermissionTo($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }
}
