<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Patient;
use App\Models\PatientServicePlan;
use App\Models\PatientServicePlanAllocation;
use App\Models\PayrollRecord;
use App\Models\Service;
use App\Models\SessionPackage;
use App\Models\Specialty;
use App\Models\Therapist;
use App\Models\TherapistEarning;
use App\Models\TherapistServiceRate;
use App\Models\User;
use App\Services\PatientServicePlanAllocator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PatientServicePlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_create_a_plan_with_multiple_ordered_services(): void
    {
        $user = $this->userWithPermissions(['manage patient service plans']);
        $patient = $this->createPatient();
        $evaluation = $this->createService('تقييم أولي', '300.00');
        $sessions = $this->createService('جلسات تخاطب', '100.00');

        $response = $this->actingAs($user)->post(route('patients.service-plans.store', $patient), [
            'starts_at' => '2026-08-20',
            'notes' => 'خطة أولية',
            'items' => [
                ['service_id' => $sessions->id, 'position' => 2, 'planned_quantity' => 10, 'customer_unit_price' => '100.00'],
                ['service_id' => $evaluation->id, 'position' => 1, 'planned_quantity' => 1, 'customer_unit_price' => '300.00'],
            ],
        ]);

        $plan = PatientServicePlan::firstOrFail();
        $response->assertRedirect(route('patient-service-plans.show', $plan));
        $this->assertSame(PatientServicePlan::STATUS_DRAFT, $plan->status);
        $this->assertSame([$evaluation->id, $sessions->id], $plan->items()->pluck('service_id')->all());
        $this->assertSame([1, 2], $plan->items()->pluck('position')->all());
        $this->assertSame('1300.00', $plan->totalAmount());
    }

    public function test_duplicate_or_invalid_item_order_is_rejected(): void
    {
        $user = $this->userWithPermissions(['manage patient service plans']);
        $patient = $this->createPatient();
        $first = $this->createService('خدمة أولى');
        $second = $this->createService('خدمة ثانية');

        $this->actingAs($user)->post(route('patients.service-plans.store', $patient), [
            'items' => [
                ['service_id' => $first->id, 'position' => 1, 'planned_quantity' => 1, 'customer_unit_price' => 100],
                ['service_id' => $second->id, 'position' => 1, 'planned_quantity' => 1, 'customer_unit_price' => 100],
            ],
        ])->assertSessionHasErrors('items.1.position');

        $this->assertDatabaseCount('patient_service_plans', 0);
    }

    public function test_authorized_user_can_open_and_activate_a_draft_plan(): void
    {
        $user = $this->userWithPermissions(['manage patient service plans']);
        $patient = $this->createPatient();
        $service = $this->createService('خدمة تفعيل');
        $plan = PatientServicePlan::create([
            'patient_id' => $patient->id,
            'status' => PatientServicePlan::STATUS_DRAFT,
        ]);
        $plan->items()->create([
            'service_id' => $service->id,
            'position' => 1,
            'planned_quantity' => 2,
            'customer_unit_price' => '100.00',
            'discount_amount' => '0.00',
            'final_unit_price' => '100.00',
        ]);

        $this->actingAs($user)
            ->get(route('patient-service-plans.show', $plan))
            ->assertOk()
            ->assertSeeText('خدمة تفعيل')
            ->assertSeeText('تفعيل الخطة');

        $this->actingAs($user)
            ->post(route('patient-service-plans.activate', $plan))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(PatientServicePlan::STATUS_ACTIVE, $plan->fresh()->status);
    }

    public function test_customer_price_and_discount_are_independent_from_therapist_rate(): void
    {
        $user = $this->userWithPermissions(['manage patient service plans', 'manage patient discounts']);
        $patient = $this->createPatient();
        $service = $this->createService('جلسة تخاطب', '150.00');
        $therapist = $this->createTherapist();
        $therapist->specialties()->attach($service->specialty_id);
        $therapist->services()->attach($service);
        $rate = TherapistServiceRate::start($therapist, $service, '80.00', '2026-01-01');

        $this->actingAs($user)->post(route('patients.service-plans.store', $patient), [
            'items' => [[
                'service_id' => $service->id,
                'position' => 1,
                'planned_quantity' => 10,
                'customer_unit_price' => '999.00',
                'discount_amount' => '20.00',
            ]],
        ])->assertRedirect();

        $item = PatientServicePlan::firstOrFail()->items()->firstOrFail();
        $this->assertSame('150.00', $item->customer_unit_price);
        $this->assertSame('20.00', $item->discount_amount);
        $this->assertSame('130.00', $item->final_unit_price);
        $this->assertSame('80.00', $rate->fresh()->amount);
    }

    public function test_user_without_discount_permission_cannot_apply_discount(): void
    {
        $user = $this->userWithPermissions(['manage patient service plans']);
        $patient = $this->createPatient();
        $service = $this->createService('خدمة بخصم', '150.00');

        $this->actingAs($user)->post(route('patients.service-plans.store', $patient), [
            'items' => [[
                'service_id' => $service->id,
                'position' => 1,
                'planned_quantity' => 1,
                'customer_unit_price' => 150,
                'discount_amount' => 20,
            ]],
        ])->assertForbidden();

        $this->assertDatabaseCount('patient_service_plans', 0);
    }

    public function test_invalid_discount_cannot_make_final_price_zero_or_negative(): void
    {
        $user = $this->userWithPermissions(['manage patient service plans', 'manage patient discounts']);
        $patient = $this->createPatient();
        $service = $this->createService('خدمة خصم غير صالح', '100.00');

        $this->actingAs($user)->post(route('patients.service-plans.store', $patient), [
            'items' => [[
                'service_id' => $service->id,
                'position' => 1,
                'planned_quantity' => 1,
                'customer_unit_price' => 100,
                'discount_amount' => 100,
            ]],
        ])->assertSessionHasErrors('items.0.discount_amount');
    }

    public function test_service_price_is_snapshotted_and_new_plan_uses_latest_price(): void
    {
        $user = $this->userWithPermissions(['manage patient service plans']);
        $service = $this->createService('خدمة بسعر متغير', '100.00');
        $firstPatient = $this->createPatient();

        $this->actingAs($user)->post(route('patients.service-plans.store', $firstPatient), [
            'items' => [[
                'service_id' => $service->id,
                'position' => 1,
                'planned_quantity' => 1,
                'customer_unit_price' => '999.00',
            ]],
        ])->assertRedirect();

        $firstItem = $firstPatient->servicePlans()->firstOrFail()->items()->firstOrFail();
        $this->assertSame('100.00', $firstItem->customer_unit_price);

        $service->update(['customer_price' => '120.00']);
        $this->assertSame('100.00', $firstItem->fresh()->customer_unit_price);

        $secondPatient = $this->createPatient();
        $this->actingAs($user)->post(route('patients.service-plans.store', $secondPatient), [
            'items' => [['service_id' => $service->id, 'position' => 1, 'planned_quantity' => 1]],
        ])->assertRedirect();

        $this->assertSame('120.00', $secondPatient->servicePlans()->firstOrFail()->items()->firstOrFail()->customer_unit_price);
    }

    public function test_service_without_official_customer_price_cannot_be_assigned(): void
    {
        $user = $this->userWithPermissions(['manage patient service plans']);
        $patient = $this->createPatient();
        $service = $this->createService('خدمة بلا سعر', null);

        $this->actingAs($user)->post(route('patients.service-plans.store', $patient), [
            'items' => [[
                'service_id' => $service->id,
                'position' => 1,
                'planned_quantity' => 1,
                'customer_unit_price' => '500.00',
            ]],
        ])->assertSessionHasErrors('items.0.service_id');

        $this->assertDatabaseCount('patient_service_plans', 0);
    }

    public function test_partial_payment_authorizes_only_full_units_and_keeps_credit(): void
    {
        [$plan] = $this->createActivePlan([['price' => '100.00', 'quantity' => 10]]);
        $payment = $this->createInvoicePayment($plan->patient, '350.00');

        app(PatientServicePlanAllocator::class)->allocate($plan, $payment);

        $item = $plan->items()->firstOrFail()->fresh();
        $this->assertSame(3, $item->authorized_quantity);
        $this->assertSame(7, $item->unpaidQuantity());
        $this->assertSame('50.00', $plan->availableCredit());
    }

    public function test_existing_credit_combines_with_a_later_payment(): void
    {
        [$plan] = $this->createActivePlan([['price' => '100.00', 'quantity' => 10]]);
        $allocator = app(PatientServicePlanAllocator::class);

        $allocator->allocate($plan, $this->createInvoicePayment($plan->patient, '50.00'));
        $this->assertSame(0, $plan->items()->firstOrFail()->authorized_quantity);
        $this->assertSame('50.00', $plan->availableCredit());

        $allocator->allocate($plan, $this->createInvoicePayment($plan->patient, '70.00'));
        $this->assertSame(1, $plan->items()->firstOrFail()->authorized_quantity);
        $this->assertSame('20.00', $plan->availableCredit());
    }

    public function test_mixed_service_payment_allocates_strictly_in_plan_order(): void
    {
        [$plan, $items] = $this->createActivePlan([
            ['name' => 'تقييم', 'price' => '300.00', 'quantity' => 1],
            ['name' => 'اختبار لغة', 'price' => '200.00', 'quantity' => 1],
            ['name' => 'جلسات', 'price' => '100.00', 'quantity' => 10],
        ]);

        app(PatientServicePlanAllocator::class)->allocate(
            $plan,
            $this->createInvoicePayment($plan->patient, '750.00')
        );

        $this->assertSame([1, 1, 2], collect($items)->map(fn ($item) => $item->fresh()->authorized_quantity)->all());
        $this->assertSame('50.00', $plan->availableCredit());
        $this->assertSame(['300.00', '200.00', '200.00'], PatientServicePlanAllocation::orderBy('id')->pluck('allocated_amount')->all());
    }

    public function test_allocator_does_not_skip_an_unfunded_earlier_item_for_a_cheaper_later_item(): void
    {
        [$plan, $items] = $this->createActivePlan([
            ['price' => '300.00', 'quantity' => 1],
            ['price' => '100.00', 'quantity' => 2],
        ]);

        app(PatientServicePlanAllocator::class)->allocate($plan, $this->createInvoicePayment($plan->patient, '200.00'));

        $this->assertSame([0, 0], collect($items)->map(fn ($item) => $item->fresh()->authorized_quantity)->all());
        $this->assertSame('200.00', $plan->availableCredit());
    }

    public function test_allocation_never_exceeds_planned_quantity_and_surplus_remains_credit(): void
    {
        [$plan] = $this->createActivePlan([['price' => '100.00', 'quantity' => 2]]);

        app(PatientServicePlanAllocator::class)->allocate($plan, $this->createInvoicePayment($plan->patient, '500.00'));

        $this->assertSame(2, $plan->items()->firstOrFail()->authorized_quantity);
        $this->assertSame('300.00', $plan->availableCredit());
    }

    public function test_same_invoice_payment_cannot_be_allocated_twice(): void
    {
        [$plan] = $this->createActivePlan([['price' => '100.00', 'quantity' => 2]]);
        $payment = $this->createInvoicePayment($plan->patient, '100.00');
        $allocator = app(PatientServicePlanAllocator::class);
        $allocator->allocate($plan, $payment);

        try {
            $allocator->allocate($plan, $payment);
            $this->fail('Duplicate payment allocation was not rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('invoice_payment_id', $exception->errors());
        }

        $this->assertDatabaseCount('patient_service_plan_payments', 1);
        $this->assertSame(1, $plan->items()->firstOrFail()->authorized_quantity);
    }

    public function test_non_active_plans_cannot_receive_payments(): void
    {
        foreach ([PatientServicePlan::STATUS_DRAFT, PatientServicePlan::STATUS_COMPLETED, PatientServicePlan::STATUS_CANCELLED] as $status) {
            [$plan] = $this->createActivePlan([['price' => '100.00', 'quantity' => 1]]);
            $plan->update(['status' => $status]);

            try {
                app(PatientServicePlanAllocator::class)->allocate(
                    $plan,
                    $this->createInvoicePayment($plan->patient, '100.00')
                );
                $this->fail("A {$status} plan accepted a payment.");
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('invoice_payment_id', $exception->errors());
            }
        }

        $this->assertDatabaseCount('patient_service_plan_payments', 0);
    }

    public function test_payment_from_another_patient_is_rejected(): void
    {
        [$plan] = $this->createActivePlan([['price' => '100.00', 'quantity' => 1]]);
        $otherPayment = $this->createInvoicePayment($this->createPatient(), '100.00');

        $this->expectException(ValidationException::class);
        app(PatientServicePlanAllocator::class)->allocate($plan, $otherPayment);
    }

    public function test_historical_customer_price_snapshot_survives_service_and_rate_changes(): void
    {
        [$plan, $items] = $this->createActivePlan([['price' => '150.00', 'quantity' => 2]]);
        $service = $items[0]->service;
        $service->update(['name' => 'اسم خدمة محدث']);

        $this->assertSame('150.00', $items[0]->fresh()->customer_unit_price);
        $this->assertSame('150.00', $items[0]->fresh()->final_unit_price);
        $this->assertSame('300.00', $plan->totalAmount());
    }

    public function test_plan_and_allocation_do_not_create_earnings_or_change_payroll(): void
    {
        [$plan] = $this->createActivePlan([['price' => '100.00', 'quantity' => 2]]);
        $therapist = $this->createTherapist();
        $record = PayrollRecord::create([
            'therapist_id' => $therapist->id,
            'type' => 'إضافة',
            'amount' => 250,
            'description' => 'سجل قائم',
            'month' => 8,
            'year' => 2026,
        ]);

        app(PatientServicePlanAllocator::class)->allocate($plan, $this->createInvoicePayment($plan->patient, '100.00'));

        $this->assertDatabaseCount((new TherapistEarning)->getTable(), 0);
        $this->assertSame('250.00', number_format((float) $record->fresh()->amount, 2, '.', ''));
    }

    public function test_legacy_session_packages_remain_accessible(): void
    {
        $patient = $this->createPatient();
        $package = SessionPackage::create([
            'patient_id' => $patient->id,
            'name' => 'باقة قديمة',
            'total_sessions' => 8,
            'used_sessions' => 2,
            'price_paid' => 800,
            'status' => 'نشط',
        ]);

        $this->createActivePlan([['price' => '100.00', 'quantity' => 2]], $patient);

        $this->assertSame('باقة قديمة', SessionPackage::findOrFail($package->id)->name);
        $this->assertSame(2, SessionPackage::findOrFail($package->id)->used_sessions);
    }

    public function test_unauthorized_user_cannot_manage_service_plans(): void
    {
        $user = User::factory()->create();
        $patient = $this->createPatient();
        $service = $this->createService('خدمة محمية');

        $this->actingAs($user)->get(route('patients.service-plans.index', $patient))->assertForbidden();
        $this->actingAs($user)->post(route('patients.service-plans.store', $patient), [
            'items' => [[
                'service_id' => $service->id,
                'position' => 1,
                'planned_quantity' => 1,
                'customer_unit_price' => 100,
            ]],
        ])->assertForbidden();
        $this->assertDatabaseCount('patient_service_plans', 0);
    }

    public function test_consumption_cannot_exceed_authorized_quantity(): void
    {
        [$plan] = $this->createActivePlan([['price' => '100.00', 'quantity' => 2]]);
        app(PatientServicePlanAllocator::class)->allocate($plan, $this->createInvoicePayment($plan->patient, '100.00'));
        $item = $plan->items()->firstOrFail();

        $this->assertSame(1, $item->consume()->consumed_quantity);

        $this->expectException(ValidationException::class);
        $item->consume();
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

    private function createPatient(): Patient
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

    private function createService(string $name, ?string $customerPrice = '100.00'): Service
    {
        $specialty = Specialty::create([
            'name' => 'تخصص '.uniqid(),
            'is_active' => true,
        ]);

        return Service::create([
            'specialty_id' => $specialty->id,
            'name' => $name.' '.uniqid(),
            'customer_price' => $customerPrice,
            'is_active' => true,
        ]);
    }

    private function createTherapist(): Therapist
    {
        return Therapist::create([
            'name' => 'أخصائي '.uniqid(),
            'specialization' => 'تخاطب',
            'salary_type' => 'monthly',
            'monthly_salary' => 5000,
            'daily_salary' => 0,
            'commission_rate' => 0,
            'is_active' => true,
        ]);
    }

    private function createActivePlan(array $itemDefinitions, ?Patient $patient = null): array
    {
        $patient ??= $this->createPatient();
        $plan = PatientServicePlan::create([
            'patient_id' => $patient->id,
            'status' => PatientServicePlan::STATUS_ACTIVE,
        ]);
        $items = [];

        foreach ($itemDefinitions as $index => $definition) {
            $service = $this->createService($definition['name'] ?? 'خدمة');
            $items[] = $plan->items()->create([
                'service_id' => $service->id,
                'position' => $index + 1,
                'planned_quantity' => $definition['quantity'],
                'customer_unit_price' => $definition['price'],
                'discount_amount' => '0.00',
                'final_unit_price' => $definition['price'],
            ]);
        }

        return [$plan, $items];
    }

    private function createInvoicePayment(Patient $patient, string $amount): InvoicePayment
    {
        $invoice = Invoice::create([
            'patient_id' => $patient->id,
            'invoice_number' => 'INV-'.uniqid(),
            'issue_date' => '2026-08-19',
            'status' => 'مدفوعة جزئياً',
            'total' => '10000.00',
        ]);

        return InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'payment_date' => '2026-08-19',
            'method' => 'كاش',
        ]);
    }
}
