<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Patient;
use App\Models\PatientServicePlan;
use App\Models\Service;
use App\Models\SessionType;
use App\Models\Specialty;
use App\Models\Therapist;
use App\Models\User;
use App\Services\PatientServicePlanAllocator;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LegacyAppointmentExceptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_permission_is_assigned_only_to_system_manager_by_default(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->assertTrue(Role::findByName('مدير النظام')->hasPermissionTo('create legacy appointments'));
        $this->assertFalse(Role::findByName('موظف استقبال')->hasPermissionTo('create legacy appointments'));
        $this->assertFalse(Role::findByName('مسؤول مالي')->hasPermissionTo('create legacy appointments'));
        $this->assertFalse(Role::findByName('أخصائي تخاطب')->hasPermissionTo('create legacy appointments'));
    }

    public function test_user_without_special_permission_cannot_create_or_see_legacy_booking(): void
    {
        $user = $this->userWithPermissions(['view appointments', 'create appointments']);
        [$patient, $therapistUser, $sessionType] = $this->legacyFixture();

        $this->actingAs($user)->get(route('appointments.index'))
            ->assertOk()
            ->assertDontSeeText('حجز استثنائي بالطريقة القديمة');

        $this->actingAs($user)->post(route('appointments.store'), $this->legacyPayload(
            $patient,
            $therapistUser,
            $sessionType,
            'حالة استثنائية معتمدة'
        ))->assertForbidden();
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_authorized_admin_sees_form_and_legacy_reason_is_required(): void
    {
        $admin = $this->userWithPermissions([
            'view appointments',
            'create appointments',
            'create legacy appointments',
        ]);
        [$patient, $therapistUser, $sessionType] = $this->legacyFixture();

        $this->actingAs($admin)->get(route('appointments.index'))
            ->assertOk()
            ->assertSeeText('حجز استثنائي بالطريقة القديمة')
            ->assertSee('name="legacy_booking_reason"', false);

        $this->actingAs($admin)->post(route('appointments.store'), $this->legacyPayload(
            $patient,
            $therapistUser,
            $sessionType,
            null
        ))->assertSessionHasErrors('legacy_booking_reason');
    }

    public function test_authorized_legacy_booking_stores_reason_null_plan_item_and_session_duration(): void
    {
        $admin = $this->userWithPermissions(['create appointments', 'create legacy appointments']);
        [$patient, $therapistUser, $sessionType] = $this->legacyFixture(duration: 45);
        $scheduledAt = now()->addDays(10)->setTime(9, 0);

        $this->actingAs($admin)->post(route('appointments.store'), $this->legacyPayload(
            $patient,
            $therapistUser,
            $sessionType,
            'ترحيل بيانات سابقة',
            $scheduledAt
        ))->assertRedirect();

        $appointment = Appointment::firstOrFail();
        $this->assertSame('ترحيل بيانات سابقة', $appointment->legacy_booking_reason);
        $this->assertNull($appointment->patient_service_plan_item_id);
        $this->assertSame(45.0, $appointment->scheduled_at->diffInMinutes($appointment->end_at));
    }

    public function test_legacy_conflict_detection_remains_active(): void
    {
        $admin = $this->userWithPermissions(['create appointments', 'create legacy appointments']);
        [$patient, $therapistUser, $sessionType] = $this->legacyFixture();
        $scheduledAt = now()->addDays(12)->setTime(10, 0);
        $payload = $this->legacyPayload($patient, $therapistUser, $sessionType, 'سبب إداري', $scheduledAt);

        $this->actingAs($admin)->post(route('appointments.store'), $payload)->assertRedirect();
        $this->actingAs($admin)->post(route('appointments.store'), $payload)
            ->assertSessionHasErrors('therapist_id');
        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_new_legacy_exception_renders_calm_badge(): void
    {
        $admin = $this->userWithPermissions([
            'view appointments',
            'create appointments',
            'create legacy appointments',
        ]);
        [$patient, $therapistUser, $sessionType] = $this->legacyFixture();
        $scheduledAt = now()->addDays(14)->setTime(10, 0);
        $this->actingAs($admin)->post(route('appointments.store'), $this->legacyPayload(
            $patient,
            $therapistUser,
            $sessionType,
            'سبب إداري',
            $scheduledAt
        ));

        $this->actingAs($admin)->get(route('appointments.index', ['date' => $scheduledAt->toDateString()]))
            ->assertOk()
            ->assertSeeText('حجز استثنائي');
    }

    public function test_v2_booking_never_stores_legacy_reason(): void
    {
        $user = $this->userWithPermissions(['create appointments']);
        [$item, $therapist] = $this->servicePlanFixture(withTherapist: true, payment: '150.00');

        $this->actingAs($user)->post(route('appointments.store'), [
            'patient_id' => $item->plan->patient_id,
            'patient_service_plan_item_id' => $item->id,
            'therapist_id' => $therapist->user_id,
            'scheduled_at' => now()->addDays(20)->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $this->assertNull(Appointment::firstOrFail()->legacy_booking_reason);
    }

    public function test_booking_state_reports_missing_therapist_after_financial_eligibility(): void
    {
        $user = $this->userWithPermissions(['view appointments']);
        [$item] = $this->servicePlanFixture(withTherapist: false, payment: '150.00');

        $this->actingAs($user)->get(route('appointments.index'))
            ->assertOk()
            ->assertViewHas('servicePlanItems', function ($items) use ($item) {
                $state = $items->firstWhere('id', $item->id);

                return $state['financially_eligible']
                    && ! $state['has_eligible_therapist']
                    && ! $state['can_book']
                    && $state['booking_state'] === 'لا يوجد أخصائي مسند لهذه الخدمة';
            });
    }

    public function test_booking_state_matches_deposit_therapist_and_insufficient_credit_cases(): void
    {
        $user = $this->userWithPermissions(['view appointments']);
        [$eligibleItem] = $this->servicePlanFixture(withTherapist: true, payment: '150.00');
        [$blockedItem] = $this->servicePlanFixture(withTherapist: true, payment: '149.00');

        $this->actingAs($user)->get(route('appointments.index'))
            ->assertOk()
            ->assertViewHas('servicePlanItems', function ($items) use ($eligibleItem, $blockedItem) {
                $eligible = $items->firstWhere('id', $eligibleItem->id);
                $blocked = $items->firstWhere('id', $blockedItem->id);

                return $eligible['can_book']
                    && $eligible['booking_state'] === 'مقدم الحجز متاح'
                    && ! $blocked['can_book']
                    && $blocked['booking_state'] === 'الرصيد غير كافٍ لتأكيد الموعد';
            });
    }

    public function test_invoice_create_route_is_not_captured_by_show_route(): void
    {
        $route = Route::getRoutes()->match(Request::create('/invoices/create', 'GET'));

        $this->assertSame('invoices.create', $route->getName());
    }

    private function legacyFixture(int $duration = 30): array
    {
        $patient = $this->patient();
        $therapistUser = User::factory()->create();
        $sessionType = SessionType::create([
            'name' => 'جلسة قديمة '.uniqid(),
            'duration_minutes' => $duration,
            'price' => 100,
        ]);

        return [$patient, $therapistUser, $sessionType];
    }

    private function legacyPayload(
        Patient $patient,
        User $therapistUser,
        SessionType $sessionType,
        ?string $reason,
        $scheduledAt = null
    ): array {
        return [
            'patient_id' => $patient->id,
            'therapist_id' => $therapistUser->id,
            'session_type_id' => $sessionType->id,
            'scheduled_at' => ($scheduledAt ?? now()->addDays(10))->format('Y-m-d\TH:i'),
            'legacy_booking_reason' => $reason,
        ];
    }

    private function servicePlanFixture(bool $withTherapist, string $payment): array
    {
        $patient = $this->patient();
        $specialty = Specialty::create(['name' => 'تخصص '.uniqid(), 'is_active' => true]);
        $service = Service::create([
            'specialty_id' => $specialty->id,
            'name' => 'خدمة '.uniqid(),
            'default_duration_minutes' => 30,
            'is_active' => true,
        ]);
        $plan = PatientServicePlan::create([
            'patient_id' => $patient->id,
            'status' => PatientServicePlan::STATUS_ACTIVE,
        ]);
        $item = $plan->items()->create([
            'service_id' => $service->id,
            'position' => 1,
            'planned_quantity' => 2,
            'customer_unit_price' => '300.00',
            'discount_amount' => '0.00',
            'final_unit_price' => '300.00',
        ]);
        $therapist = null;

        if ($withTherapist) {
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
        }

        $invoice = Invoice::create([
            'patient_id' => $patient->id,
            'invoice_number' => 'INV-'.uniqid(),
            'issue_date' => now()->toDateString(),
            'status' => 'مدفوعة جزئياً',
            'total' => $payment,
        ]);
        $invoicePayment = InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'amount' => $payment,
            'payment_date' => now()->toDateString(),
            'method' => 'كاش',
        ]);
        app(PatientServicePlanAllocator::class)->allocate($plan, $invoicePayment);

        return [$item, $therapist];
    }

    private function patient(): Patient
    {
        $guardian = Guardian::create([
            'name' => 'ولي أمر '.uniqid(),
            'phone' => '010'.random_int(10000000, 99999999),
        ]);

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

        Role::firstOrCreate(['name' => 'أخصائي تخاطب', 'guard_name' => 'web']);
        $user->givePermissionTo($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }
}
