<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Guardian;
use App\Models\Patient;
use App\Models\SessionType;
use App\Models\Therapist;
use App\Models\User;
use App\Services\AppointmentAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TherapistAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_save_multiple_separate_periods_and_leave_day_off(): void
    {
        $manager = $this->userWithPermissions(['view hr', 'manage therapists']);
        $therapist = $this->therapist();

        $this->actingAs($manager)->put(route('therapists.work-schedule.update', $therapist), [
            'periods' => [
                6 => [
                    ['starts_at' => '08:00', 'ends_at' => '15:00'],
                    ['starts_at' => '18:00', 'ends_at' => '22:00'],
                ],
                0 => [
                    ['starts_at' => '09:00', 'ends_at' => '17:00'],
                ],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('therapist_work_periods', [
            'therapist_id' => $therapist->id,
            'weekday' => 6,
            'starts_at' => '08:00',
            'ends_at' => '15:00',
        ]);
        $this->assertSame(2, $therapist->workPeriods()->where('weekday', 6)->count());
        $this->assertSame(0, $therapist->workPeriods()->where('weekday', 1)->count());

        $this->actingAs($manager)->get(route('therapists.show', $therapist))
            ->assertOk()
            ->assertSeeText('ملف الأخصائي')
            ->assertSeeText('جدول العمل الأسبوعي');
    }

    public function test_schedule_rejects_invalid_malformed_overlapping_and_duplicate_periods(): void
    {
        $manager = $this->userWithPermissions(['manage therapists']);
        $therapist = $this->therapist();

        $invalidPayloads = [
            [6 => [['starts_at' => '12:00', 'ends_at' => '08:00']]],
            [6 => [
                ['starts_at' => '08:00', 'ends_at' => '12:00'],
                ['starts_at' => '11:00', 'ends_at' => '14:00'],
            ]],
            [6 => [
                ['starts_at' => '08:00', 'ends_at' => '12:00'],
                ['starts_at' => '08:00', 'ends_at' => '12:00'],
            ]],
            [6 => [['starts_at' => 'not-time', 'ends_at' => '12:00']]],
            ['not-a-day' => [['starts_at' => '08:00', 'ends_at' => '12:00']]],
        ];

        foreach ($invalidPayloads as $periods) {
            $this->actingAs($manager)
                ->from(route('therapists.show', $therapist))
                ->put(route('therapists.work-schedule.update', $therapist), compact('periods'))
                ->assertSessionHasErrors();
        }

        $this->assertDatabaseCount('therapist_work_periods', 0);
    }

    public function test_adjacent_periods_are_accepted_and_unauthorized_user_cannot_update_schedule(): void
    {
        $manager = $this->userWithPermissions(['manage therapists']);
        $unauthorized = User::factory()->create();
        $therapist = $this->therapist();
        $payload = ['periods' => [6 => [
            ['starts_at' => '08:00', 'ends_at' => '12:00'],
            ['starts_at' => '12:00', 'ends_at' => '16:00'],
        ]]];

        $this->actingAs($manager)->put(route('therapists.work-schedule.update', $therapist), $payload)
            ->assertSessionHasNoErrors();
        $this->assertSame(2, $therapist->workPeriods()->count());

        $this->actingAs($unauthorized)->put(route('therapists.work-schedule.update', $therapist), [
            'periods' => [],
        ])->assertForbidden();
        $this->assertSame(2, $therapist->workPeriods()->count());
    }

    public function test_availability_endpoint_requires_appointment_creation_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson(route('appointments.availability', [
            'patient_service_plan_item_id' => 1,
            'therapist_id' => 1,
            'date' => now()->addDay()->toDateString(),
        ]))->assertForbidden();
    }

    public function test_availability_subtracts_busy_appointment_into_complete_free_windows(): void
    {
        [$therapist, $date] = $this->scheduledTherapist([['08:00', '15:00']]);
        $this->appointment($therapist, $date, '09:00', '09:30');

        $availability = app(AppointmentAvailabilityService::class)->availability($therapist, $date, 30);

        $this->assertSame([
            ['start' => '08:00', 'end' => '09:00'],
            ['start' => '09:30', 'end' => '15:00'],
        ], collect($availability['free_windows'])->map(fn (array $window) => [
            'start' => $window['start'],
            'end' => $window['end'],
        ])->all());
    }

    public function test_today_availability_keeps_free_window_but_excludes_past_start_times(): void
    {
        config(['app.timezone' => 'Africa/Cairo']);
        $now = CarbonImmutable::parse('2026-08-22 13:07:00', 'Africa/Cairo');
        CarbonImmutable::setTestNow($now);

        try {
            $therapist = $this->therapist();
            $therapist->workPeriods()->create([
                'weekday' => $now->dayOfWeek,
                'starts_at' => '08:00',
                'ends_at' => '15:00',
            ]);

            $availability = app(AppointmentAvailabilityService::class)
                ->availability($therapist, $now->toDateString(), 30);
            $window = $availability['free_windows'][0];

            $this->assertSame('13:07', $window['start']);
            $this->assertSame('15:00', $window['end']);
            $this->assertNotContains('13:00', $window['start_times']);
            $this->assertContains('13:15', $window['start_times']);
            $this->assertSame('13:15', $window['start_times'][0]);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_multiple_work_periods_never_expose_the_gap_between_shifts(): void
    {
        [$therapist, $date] = $this->scheduledTherapist([
            ['08:00', '15:00'],
            ['18:00', '22:00'],
        ]);

        $availability = app(AppointmentAvailabilityService::class)->availability($therapist, $date, 30);
        $windows = collect($availability['free_windows'])->map(fn (array $window) => [
            'start' => $window['start'],
            'end' => $window['end'],
        ])->all();

        $this->assertSame([
            ['start' => '08:00', 'end' => '15:00'],
            ['start' => '18:00', 'end' => '22:00'],
        ], $windows);
        $this->assertNotContains('15:00', collect($availability['free_windows'])->flatMap->start_times->all());
    }

    public function test_valid_starts_respect_fifteen_thirty_forty_five_and_sixty_minute_durations(): void
    {
        [$therapist, $date] = $this->scheduledTherapist([['08:00', '09:30']]);
        $service = app(AppointmentAvailabilityService::class);

        $starts15 = $service->availability($therapist, $date, 15)['free_windows'][0]['start_times'];
        $starts30 = $service->availability($therapist, $date, 30)['free_windows'][0]['start_times'];
        $starts45 = $service->availability($therapist, $date, 45)['free_windows'][0]['start_times'];
        $starts60 = $service->availability($therapist, $date, 60)['free_windows'][0]['start_times'];

        $this->assertContains('09:15', $starts15);
        $this->assertContains('09:00', $starts30);
        $this->assertNotContains('09:15', $starts30);
        $this->assertContains('08:45', $starts45);
        $this->assertNotContains('09:00', $starts45);
        $this->assertContains('08:30', $starts60);
        $this->assertNotContains('08:45', $starts60);
    }

    public function test_busy_interval_blocks_every_overlap_shape_but_allows_adjacent_starts(): void
    {
        [$therapist, $date] = $this->scheduledTherapist([['08:00', '15:00']]);
        $this->appointment($therapist, $date, '10:00', '11:00');
        $service = app(AppointmentAvailabilityService::class);
        $starts60 = collect($service->availability($therapist, $date, 60)['free_windows'])->flatMap->start_times;
        $starts120 = collect($service->availability($therapist, $date, 120)['free_windows'])->flatMap->start_times;
        $starts15 = collect($service->availability($therapist, $date, 15)['free_windows'])->flatMap->start_times;

        $this->assertNotContains('09:15', $starts60);
        $this->assertNotContains('10:30', $starts60);
        $this->assertNotContains('09:00', $starts120);
        $this->assertNotContains('10:15', $starts15);
        $this->assertContains('09:00', $starts60);
        $this->assertContains('11:00', $starts60);
    }

    public function test_cancelled_and_cross_boundary_appointments_are_handled_safely(): void
    {
        [$therapist, $date] = $this->scheduledTherapist([['08:00', '15:00']]);
        $this->appointment($therapist, $date, '09:00', '09:30', 'ملغى');
        $this->appointment($therapist, $date, '14:45', '15:30');

        $availability = app(AppointmentAvailabilityService::class)->availability($therapist, $date, 30);
        $starts = collect($availability['free_windows'])->flatMap->start_times;

        $this->assertContains('09:00', $starts);
        $this->assertContains('14:15', $starts);
        $this->assertNotContains('14:30', $starts);
    }

    private function scheduledTherapist(array $periods): array
    {
        $therapist = $this->therapist();
        $date = CarbonImmutable::parse('next saturday')->startOfDay();

        foreach ($periods as [$start, $end]) {
            $therapist->workPeriods()->create([
                'weekday' => $date->dayOfWeek,
                'starts_at' => $start,
                'ends_at' => $end,
            ]);
        }

        return [$therapist, $date];
    }

    private function appointment(Therapist $therapist, CarbonImmutable $date, string $start, string $end, string $status = 'مجدول'): Appointment
    {
        $patient = $this->patient();
        $sessionType = SessionType::firstOrCreate(
            ['name' => 'جلسة إشغال'],
            ['duration_minutes' => 30, 'price' => 100]
        );

        return Appointment::create([
            'patient_id' => $patient->id,
            'therapist_id' => $therapist->user_id,
            'session_type_id' => $sessionType->id,
            'scheduled_at' => $date->toDateString().' '.$start,
            'end_at' => $date->toDateString().' '.$end,
            'status' => $status,
        ]);
    }

    private function therapist(): Therapist
    {
        $user = User::factory()->create();

        return Therapist::create([
            'user_id' => $user->id,
            'name' => 'أخصائي '.uniqid(),
            'salary_type' => 'monthly',
            'is_active' => true,
        ]);
    }

    private function patient(): Patient
    {
        $guardian = Guardian::create(['name' => 'ولي أمر '.uniqid(), 'phone' => '01000000000']);

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
