<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Patient;
use App\Models\PatientServicePlan;
use App\Models\PatientServicePlanAllocation;
use App\Models\PatientServicePlanPayment;
use App\Models\PayrollRecord;
use App\Models\Service;
use App\Models\SessionPackage;
use App\Models\Specialty;
use App\Models\Therapist;
use App\Models\TherapistEarning;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PatientServicePlanLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_open_edit_page_with_existing_values_for_pristine_draft(): void
    {
        $user = $this->userWithPermissions(['manage patient service plans']);
        [$plan, $items] = $this->createPlan(PatientServicePlan::STATUS_DRAFT, [
            ['name' => 'تقييم مبدئي', 'quantity' => 2, 'price' => '300.00'],
        ]);

        $this->actingAs($user)
            ->get(route('patient-service-plans.edit', $plan))
            ->assertOk()
            ->assertSeeText('تعديل خطة الخدمات')
            ->assertSee('data-initial-service-ids="'.$items[0]->service_id.'"', false)
            ->assertSee('customer_unit_price', false)
            ->assertSee('300.00')
            ->assertSee('سعر محفوظ وقت إضافة الخدمة', false)
            ->assertSee('حفظ التعديلات');
    }

    public function test_multiple_existing_items_retain_their_service_ids_on_edit(): void
    {
        $user = $this->userWithPermissions(['manage patient service plans']);
        [$plan, $items] = $this->createPlan(PatientServicePlan::STATUS_DRAFT, [
            ['name' => 'خدمة أولى', 'quantity' => 1, 'price' => '100.00'],
            ['name' => 'خدمة ثانية', 'quantity' => 2, 'price' => '200.00'],
        ]);

        $this->actingAs($user)
            ->get(route('patient-service-plans.edit', $plan))
            ->assertOk()
            ->assertSee(
                'data-initial-service-ids="'.$items[0]->service_id.','.$items[1]->service_id.'"',
                false
            );
    }

    public function test_validation_old_input_restores_submitted_service_selection_on_edit(): void
    {
        $user = $this->userWithPermissions(['manage patient service plans']);
        [$plan, $items] = $this->createPlan(PatientServicePlan::STATUS_DRAFT, [
            ['name' => 'الخدمة الأصلية', 'quantity' => 1, 'price' => '100.00'],
        ]);
        $replacement = $this->createService('الخدمة المختارة');
        $editUrl = route('patient-service-plans.edit', $plan);

        $this->actingAs($user)
            ->from($editUrl)
            ->put(route('patient-service-plans.update', $plan), [
                'items' => [[
                    'id' => $items[0]->id,
                    'service_id' => $replacement->id,
                    'position' => 1,
                    'planned_quantity' => 0,
                    'customer_unit_price' => '100.00',
                    'discount_amount' => '0.00',
                ]],
            ])
            ->assertRedirect($editUrl)
            ->assertSessionHasErrors('items.0.planned_quantity')
            ->assertSessionHasInput('items.0.service_id', $replacement->id);

        $this->get($editUrl)
            ->assertOk()
            ->assertSee('data-initial-service-ids="'.$replacement->id.'"', false);
    }

    public function test_create_page_still_renders_available_service_choices(): void
    {
        $user = $this->userWithPermissions(['manage patient service plans']);
        [$plan] = $this->createPlan(PatientServicePlan::STATUS_DRAFT, [
            ['name' => 'خدمة متاحة', 'quantity' => 1, 'price' => '100.00'],
        ]);
        $service = $plan->items()->firstOrFail()->service;

        $this->actingAs($user)
            ->get(route('patients.service-plans.create', $plan->patient))
            ->assertOk()
            ->assertSee('data-initial-service-ids=""', false)
            ->assertViewHas('services', fn ($services) => $services->contains('id', $service->id));
    }

    public function test_application_layout_initializes_persistent_theme_before_alpine(): void
    {
        $user = $this->userWithPermissions(['manage patient service plans']);
        [$plan] = $this->createPlan(PatientServicePlan::STATUS_DRAFT, [
            ['quantity' => 1, 'price' => '100.00'],
        ]);

        $this->actingAs($user)
            ->get(route('patient-service-plans.edit', $plan))
            ->assertOk()
            ->assertSee("const key = 'speech-clinic-theme'", false)
            ->assertSee('localStorage.getItem(key)', false)
            ->assertSee('prefers-color-scheme: dark', false)
            ->assertSee('window.SpeechClinicTheme.toggle()', false)
            ->assertDontSee('cdn.jsdelivr.net/npm/alpinejs', false);
    }

    public function test_guest_layout_also_initializes_persistent_theme(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee("const key = 'speech-clinic-theme'", false)
            ->assertSee('prefers-color-scheme: dark', false);
    }

    public function test_pristine_draft_can_be_fully_edited_with_added_removed_reordered_items(): void
    {
        $user = $this->userWithPermissions(['manage patient service plans', 'manage patient discounts']);
        [$plan, $items] = $this->createPlan(PatientServicePlan::STATUS_DRAFT, [
            ['name' => 'خدمة ستُحذف', 'quantity' => 1, 'price' => '100.00'],
            ['name' => 'جلسة تخاطب', 'quantity' => 1, 'price' => '120.00'],
        ]);
        $newService = $this->createService('اختبار لغة');

        $this->actingAs($user)->put(route('patient-service-plans.update', $plan), [
            'starts_at' => '2026-09-01',
            'ends_at' => '2026-12-31',
            'notes' => 'خطة مصححة',
            'items' => [
                $this->itemPayload($items[1], position: 2, quantity: 10, price: '150.00', discount: '20.00'),
                ['service_id' => $newService->id, 'position' => 1, 'planned_quantity' => 1, 'customer_unit_price' => '200.00', 'discount_amount' => '0.00'],
            ],
        ])->assertRedirect(route('patient-service-plans.show', $plan));

        $plan->refresh();
        $this->assertSame('2026-09-01', $plan->starts_at->format('Y-m-d'));
        $this->assertSame('خطة مصححة', $plan->notes);
        $this->assertSame([$newService->id, $items[1]->service_id], $plan->items()->pluck('service_id')->all());
        $this->assertSame([1, 2], $plan->items()->pluck('position')->all());
        $this->assertDatabaseMissing('patient_service_plan_items', ['service_id' => $items[0]->service_id]);
        $speechItem = $plan->items()->where('service_id', $items[1]->service_id)->firstOrFail();
        $this->assertSame(10, $speechItem->planned_quantity);
        $this->assertSame('120.00', $speechItem->customer_unit_price);
        $this->assertSame('20.00', $speechItem->discount_amount);
        $this->assertSame('100.00', $speechItem->final_unit_price);
    }

    public function test_pristine_active_plan_can_be_fully_edited(): void
    {
        $user = $this->userWithPermissions(['manage patient service plans']);
        [$plan, $items] = $this->createPlan(PatientServicePlan::STATUS_ACTIVE, [
            ['quantity' => 1, 'price' => '100.00'],
        ]);

        $this->actingAs($user)->put(route('patient-service-plans.update', $plan), [
            'notes' => 'نشطة لكنها نظيفة',
            'items' => [$this->itemPayload($items[0], quantity: 4, price: '125.00')],
        ])->assertRedirect();

        $updated = $plan->items()->firstOrFail();
        $this->assertSame(PatientServicePlan::STATUS_ACTIVE, $plan->fresh()->status);
        $this->assertSame(4, $updated->planned_quantity);
        $this->assertSame('100.00', $updated->final_unit_price);
    }

    public function test_positions_are_normalized_deterministically_during_edit(): void
    {
        $user = $this->userWithPermissions(['manage patient service plans']);
        [$plan, $items] = $this->createPlan(PatientServicePlan::STATUS_DRAFT, [
            ['name' => 'الأولى', 'quantity' => 1, 'price' => '100.00'],
            ['name' => 'الثانية', 'quantity' => 1, 'price' => '100.00'],
            ['name' => 'الثالثة', 'quantity' => 1, 'price' => '100.00'],
        ]);

        $this->actingAs($user)->put(route('patient-service-plans.update', $plan), [
            'items' => [
                $this->itemPayload($items[2], position: 30),
                $this->itemPayload($items[0], position: 10),
                $this->itemPayload($items[1], position: 20),
            ],
        ])->assertRedirect();

        $this->assertSame(
            [$items[0]->service_id, $items[1]->service_id, $items[2]->service_id],
            $plan->items()->pluck('service_id')->all()
        );
        $this->assertSame([1, 2, 3], $plan->items()->pluck('position')->all());
    }

    public function test_user_without_discount_permission_cannot_manipulate_existing_discount(): void
    {
        $user = $this->userWithPermissions(['manage patient service plans']);
        [$plan, $items] = $this->createPlan(PatientServicePlan::STATUS_DRAFT, [
            ['quantity' => 2, 'price' => '150.00', 'discount' => '20.00'],
        ]);

        $this->actingAs($user)->put(route('patient-service-plans.update', $plan), [
            'items' => [$this->itemPayload($items[0], discount: '10.00')],
        ])->assertSessionHasErrors('items.0.discount_amount');

        $this->assertSame('20.00', $items[0]->fresh()->discount_amount);
        $this->assertSame('130.00', $items[0]->fresh()->final_unit_price);
    }

    public function test_user_without_discount_permission_can_edit_other_fields_while_discount_is_preserved(): void
    {
        $user = $this->userWithPermissions(['manage patient service plans']);
        [$plan, $items] = $this->createPlan(PatientServicePlan::STATUS_DRAFT, [
            ['quantity' => 2, 'price' => '150.00', 'discount' => '20.00'],
        ]);
        $payload = $this->itemPayload($items[0], quantity: 3);
        unset($payload['discount_amount']);

        $this->actingAs($user)->put(route('patient-service-plans.update', $plan), [
            'items' => [$payload],
        ])->assertRedirect();

        $updated = $plan->items()->firstOrFail();
        $this->assertSame(3, $updated->planned_quantity);
        $this->assertSame('20.00', $updated->discount_amount);
    }

    public function test_invalid_edit_leaves_original_plan_and_items_unchanged(): void
    {
        $user = $this->userWithPermissions(['manage patient service plans']);
        [$plan, $items] = $this->createPlan(PatientServicePlan::STATUS_DRAFT, [
            ['quantity' => 2, 'price' => '150.00'],
        ]);

        $this->actingAs($user)->put(route('patient-service-plans.update', $plan), [
            'notes' => 'يجب ألا تُحفظ',
            'items' => [$this->itemPayload($items[0], quantity: 0, price: '0.00')],
        ])->assertSessionHasErrors();

        $this->assertNull($plan->fresh()->notes);
        $this->assertSame(2, $items[0]->fresh()->planned_quantity);
        $this->assertSame('150.00', $items[0]->fresh()->customer_unit_price);
    }

    public function test_payment_history_rejects_financial_changes_but_allows_notes(): void
    {
        $user = $this->userWithPermissions(['manage patient service plans', 'manage patient discounts']);
        [$plan, $items] = $this->createPlan(PatientServicePlan::STATUS_ACTIVE, [
            ['quantity' => 2, 'price' => '100.00'],
        ]);
        $this->attachPlanPayment($plan, '50.00');

        $this->actingAs($user)->put(route('patient-service-plans.update', $plan), [
            'items' => [$this->itemPayload($items[0], quantity: 10, price: '5.00')],
        ])->assertSessionHasErrors('plan');

        $this->assertSame(2, $items[0]->fresh()->planned_quantity);
        $this->assertSame('100.00', $items[0]->fresh()->customer_unit_price);

        $this->actingAs($user)->put(route('patient-service-plans.update', $plan), [
            'notes' => 'ملاحظة بعد الدفع',
        ])->assertRedirect();
        $this->assertSame('ملاحظة بعد الدفع', $plan->fresh()->notes);
    }

    public function test_allocation_authorization_and_consumption_each_protect_financial_structure(): void
    {
        $user = $this->userWithPermissions(['manage patient service plans']);

        foreach (['allocation', 'authorized', 'consumed'] as $historyType) {
            [$plan, $items] = $this->createPlan(PatientServicePlan::STATUS_ACTIVE, [
                ['quantity' => 3, 'price' => '100.00'],
            ]);

            if ($historyType === 'allocation') {
                $planPayment = $this->attachPlanPayment($plan, '100.00');
                PatientServicePlanAllocation::create([
                    'patient_service_plan_payment_id' => $planPayment->id,
                    'patient_service_plan_item_id' => $items[0]->id,
                    'allocated_amount' => '100.00',
                    'authorized_quantity' => 1,
                ]);
            } elseif ($historyType === 'authorized') {
                $items[0]->update(['authorized_quantity' => 1]);
            } else {
                $items[0]->update(['authorized_quantity' => 1, 'consumed_quantity' => 1]);
            }

            $this->actingAs($user)->put(route('patient-service-plans.update', $plan), [
                'starts_at' => '2027-01-01',
                'items' => [$this->itemPayload($items[0], quantity: 1)],
            ])->assertSessionHasErrors('plan');

            $this->assertSame(3, $items[0]->fresh()->planned_quantity);
        }
    }

    public function test_pristine_draft_and_its_items_can_be_deleted(): void
    {
        $user = $this->userWithPermissions(['manage patient service plans']);
        [$plan, $items] = $this->createPlan(PatientServicePlan::STATUS_DRAFT, [
            ['quantity' => 1, 'price' => '100.00'],
            ['quantity' => 2, 'price' => '120.00'],
        ]);
        $patient = $plan->patient;

        $this->actingAs($user)
            ->delete(route('patient-service-plans.destroy', $plan))
            ->assertRedirect(route('patients.service-plans.index', $patient))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('patient_service_plans', ['id' => $plan->id]);
        foreach ($items as $item) {
            $this->assertDatabaseMissing('patient_service_plan_items', ['id' => $item->id]);
        }
    }

    public function test_non_draft_statuses_cannot_be_deleted(): void
    {
        $user = $this->userWithPermissions(['manage patient service plans']);

        foreach ([PatientServicePlan::STATUS_ACTIVE, PatientServicePlan::STATUS_COMPLETED, PatientServicePlan::STATUS_CANCELLED] as $status) {
            [$plan] = $this->createPlan($status, [['quantity' => 1, 'price' => '100.00']]);
            $this->actingAs($user)->delete(route('patient-service-plans.destroy', $plan))->assertSessionHasErrors('plan');
            $this->assertDatabaseHas('patient_service_plans', ['id' => $plan->id, 'status' => $status]);
        }
    }

    public function test_draft_with_any_protected_history_cannot_be_deleted(): void
    {
        $user = $this->userWithPermissions(['manage patient service plans']);

        foreach (['payment', 'allocation', 'authorized', 'consumed'] as $historyType) {
            [$plan, $items] = $this->createPlan(PatientServicePlan::STATUS_DRAFT, [['quantity' => 2, 'price' => '100.00']]);

            if (in_array($historyType, ['payment', 'allocation'], true)) {
                $planPayment = $this->attachPlanPayment($plan, '100.00');

                if ($historyType === 'allocation') {
                    PatientServicePlanAllocation::create([
                        'patient_service_plan_payment_id' => $planPayment->id,
                        'patient_service_plan_item_id' => $items[0]->id,
                        'allocated_amount' => '100.00',
                        'authorized_quantity' => 1,
                    ]);
                }
            } elseif ($historyType === 'authorized') {
                $items[0]->update(['authorized_quantity' => 1]);
            } else {
                $items[0]->update(['authorized_quantity' => 1, 'consumed_quantity' => 1]);
            }

            $this->actingAs($user)->delete(route('patient-service-plans.destroy', $plan))->assertSessionHasErrors('plan');
            $this->assertDatabaseHas('patient_service_plans', ['id' => $plan->id]);
        }
    }

    public function test_unauthorized_user_cannot_edit_update_or_delete_plan(): void
    {
        $user = User::factory()->create();
        [$plan] = $this->createPlan(PatientServicePlan::STATUS_DRAFT, [['quantity' => 1, 'price' => '100.00']]);

        $this->actingAs($user)->get(route('patient-service-plans.edit', $plan))->assertForbidden();
        $this->actingAs($user)->put(route('patient-service-plans.update', $plan), ['notes' => 'غير مسموح'])->assertForbidden();
        $this->actingAs($user)->delete(route('patient-service-plans.destroy', $plan))->assertForbidden();
        $this->assertDatabaseHas('patient_service_plans', ['id' => $plan->id]);
    }

    public function test_show_page_exposes_contextual_actions_and_arabic_status_labels(): void
    {
        $user = $this->userWithPermissions(['manage patient service plans']);
        [$draft] = $this->createPlan(PatientServicePlan::STATUS_DRAFT, [['quantity' => 1, 'price' => '100.00']]);

        $this->actingAs($user)->get(route('patient-service-plans.show', $draft))
            ->assertOk()
            ->assertSeeText('مسودة')
            ->assertSeeText('تعديل الخطة')
            ->assertSeeText('حذف المسودة');

        $draft->update(['status' => PatientServicePlan::STATUS_ACTIVE]);
        $this->actingAs($user)->get(route('patient-service-plans.show', $draft))
            ->assertOk()
            ->assertSeeText('نشطة')
            ->assertDontSeeText('حذف المسودة');

        foreach ([
            PatientServicePlan::STATUS_COMPLETED => 'مكتملة',
            PatientServicePlan::STATUS_CANCELLED => 'ملغاة',
        ] as $status => $label) {
            $draft->update(['status' => $status]);
            $this->actingAs($user)->get(route('patient-service-plans.show', $draft))
                ->assertOk()
                ->assertSeeText($label)
                ->assertDontSeeText('حذف المسودة');
        }
    }

    public function test_user_without_management_permission_does_not_see_patient_plan_action(): void
    {
        $user = $this->userWithPermissions(['view patients']);
        [$plan] = $this->createPlan(PatientServicePlan::STATUS_DRAFT, [['quantity' => 1, 'price' => '100.00']]);

        $this->actingAs($user)
            ->get(route('patients.show', $plan->patient))
            ->assertOk()
            ->assertDontSeeText('فتح خطط الخدمات');
    }

    public function test_lifecycle_changes_do_not_touch_earnings_payroll_or_legacy_packages(): void
    {
        $user = $this->userWithPermissions(['manage patient service plans']);
        [$plan, $items] = $this->createPlan(PatientServicePlan::STATUS_DRAFT, [['quantity' => 1, 'price' => '100.00']]);
        $therapist = Therapist::create([
            'name' => 'أخصائي '.uniqid(), 'specialization' => 'تخاطب', 'salary_type' => 'monthly',
            'monthly_salary' => 5000, 'daily_salary' => 0, 'commission_rate' => 0, 'is_active' => true,
        ]);
        $payroll = PayrollRecord::create([
            'therapist_id' => $therapist->id, 'type' => 'إضافة', 'amount' => 250,
            'description' => 'قائم', 'month' => 8, 'year' => 2026,
        ]);
        $package = SessionPackage::create([
            'patient_id' => $plan->patient_id, 'name' => 'باقة قديمة', 'total_sessions' => 8,
            'used_sessions' => 2, 'price_paid' => 800, 'status' => 'نشط',
        ]);

        $this->actingAs($user)->put(route('patient-service-plans.update', $plan), [
            'items' => [$this->itemPayload($items[0], quantity: 3)],
        ])->assertRedirect();
        $this->actingAs($user)->delete(route('patient-service-plans.destroy', $plan))->assertRedirect();

        $this->assertDatabaseCount((new TherapistEarning)->getTable(), 0);
        $this->assertEquals(250, $payroll->fresh()->amount);
        $this->assertSame(2, $package->fresh()->used_sessions);
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

    private function createPlan(string $status, array $definitions): array
    {
        $guardian = Guardian::create(['name' => 'ولي أمر '.uniqid(), 'phone' => '010'.random_int(10000000, 99999999)]);
        $patient = Patient::create([
            'guardian_id' => $guardian->id, 'name' => 'طفل '.uniqid(), 'birth_date' => '2020-01-01',
            'gender' => 'male', 'barcode' => 'PAT-'.uniqid(), 'qr_code' => 'PAT-'.uniqid(), 'is_active' => true,
        ]);
        $plan = PatientServicePlan::create(['patient_id' => $patient->id, 'status' => $status]);
        $items = [];

        foreach ($definitions as $index => $definition) {
            $service = $this->createService($definition['name'] ?? 'خدمة', $definition['price']);
            $price = $definition['price'];
            $discount = $definition['discount'] ?? '0.00';
            $items[] = $plan->items()->create([
                'service_id' => $service->id,
                'position' => $index + 1,
                'planned_quantity' => $definition['quantity'],
                'customer_unit_price' => $price,
                'discount_amount' => $discount,
                'final_unit_price' => number_format((float) $price - (float) $discount, 2, '.', ''),
            ]);
        }

        return [$plan, $items];
    }

    private function createService(string $name, string $customerPrice = '100.00'): Service
    {
        $specialty = Specialty::create(['name' => 'تخصص '.uniqid(), 'is_active' => true]);

        return Service::create(['specialty_id' => $specialty->id, 'name' => $name.' '.uniqid(), 'customer_price' => $customerPrice, 'is_active' => true]);
    }

    private function itemPayload($item, int $position = 1, ?int $quantity = null, ?string $price = null, ?string $discount = null): array
    {
        return [
            'id' => $item->id,
            'service_id' => $item->service_id,
            'position' => $position,
            'planned_quantity' => $quantity ?? $item->planned_quantity,
            'customer_unit_price' => $price ?? $item->customer_unit_price,
            'discount_amount' => $discount ?? $item->discount_amount,
        ];
    }

    private function attachPlanPayment(PatientServicePlan $plan, string $amount): PatientServicePlanPayment
    {
        $invoice = Invoice::create([
            'patient_id' => $plan->patient_id, 'invoice_number' => 'INV-'.uniqid(),
            'issue_date' => '2026-08-19', 'status' => 'مدفوعة جزئياً', 'total' => '1000.00',
        ]);
        $payment = InvoicePayment::create([
            'invoice_id' => $invoice->id, 'amount' => $amount,
            'payment_date' => '2026-08-19', 'method' => 'كاش',
        ]);

        return PatientServicePlanPayment::create([
            'patient_service_plan_id' => $plan->id,
            'invoice_payment_id' => $payment->id,
            'amount_snapshot' => $amount,
        ]);
    }
}
