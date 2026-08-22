<?php

namespace Tests\Feature;

use App\Models\PayrollRecord;
use App\Models\Service;
use App\Models\Specialty;
use App\Models\Therapist;
use App\Models\TherapistEarning;
use App\Models\TherapistServiceRate;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SpecialtyServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_manager_can_create_specialty(): void
    {
        $user = $this->userWithPermissions(['manage specialties']);

        $this->actingAs($user)
            ->get(route('specialties.index'))
            ->assertOk()
            ->assertSeeText('التخصصات');

        $this->actingAs($user)
            ->post(route('specialties.store'), [
                'name' => 'تخاطب',
                'description' => 'اضطرابات النطق واللغة',
                'is_active' => 1,
            ])
            ->assertRedirect(route('specialties.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('specialties', [
            'name' => 'تخاطب',
            'description' => 'اضطرابات النطق واللغة',
            'is_active' => true,
        ]);
    }

    public function test_unauthorized_user_cannot_manage_specialties(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('specialties.index'))->assertForbidden();
        $this->actingAs($user)->post(route('specialties.store'), [
            'name' => 'غير مسموح',
            'is_active' => 1,
        ])->assertForbidden();

        $this->assertDatabaseMissing('specialties', ['name' => 'غير مسموح']);
    }

    public function test_authorized_manager_can_create_service_under_specialty(): void
    {
        $user = $this->userWithPermissions(['manage services']);
        $specialty = $this->createSpecialty('تخاطب');

        $this->actingAs($user)
            ->get(route('services.create'))
            ->assertOk()
            ->assertSeeText('إضافة خدمة جديدة');

        $this->actingAs($user)
            ->post(route('services.store'), [
                'specialty_id' => $specialty->id,
                'name' => 'جلسة تخاطب',
                'description' => 'جلسة فردية',
                'default_duration_minutes' => 45,
                'customer_price' => '250.00',
                'is_active' => 1,
            ])
            ->assertRedirect(route('services.index'));

        $this->assertDatabaseHas('services', [
            'specialty_id' => $specialty->id,
            'name' => 'جلسة تخاطب',
            'default_duration_minutes' => 45,
            'customer_price' => 250,
        ]);
    }

    public function test_service_edit_includes_inactive_current_specialty_but_excludes_other_inactive_specialties(): void
    {
        $user = $this->userWithPermissions(['manage services']);
        $currentSpecialty = $this->createSpecialty('تخصص حالي');
        $unrelatedSpecialty = $this->createSpecialty('تخصص غير مرتبط');
        $activeSpecialty = $this->createSpecialty('تخصص نشط');
        $service = $this->createService($currentSpecialty, 'خدمة قائمة');

        $currentSpecialty->update(['is_active' => false]);
        $unrelatedSpecialty->update(['is_active' => false]);

        $this->actingAs($user)
            ->get(route('services.edit', $service))
            ->assertOk()
            ->assertSeeText($currentSpecialty->name)
            ->assertSeeText($activeSpecialty->name)
            ->assertDontSeeText($unrelatedSpecialty->name);
    }

    public function test_service_activation_rejects_missing_or_nonpositive_customer_price(): void
    {
        $user = $this->userWithPermissions(['manage services']);
        $specialty = $this->createSpecialty('تخصص تفعيل دون سعر');

        foreach ([null, '0.00'] as $index => $customerPrice) {
            $service = Service::create([
                'specialty_id' => $specialty->id,
                'name' => 'خدمة دون سعر '.($index + 1),
                'customer_price' => $customerPrice,
                'is_active' => false,
            ]);

            $this->actingAs($user)
                ->from(route('services.index'))
                ->patch(route('services.toggle-active', $service))
                ->assertRedirect(route('services.index'))
                ->assertSessionHasErrors('customer_price');

            $this->assertFalse($service->fresh()->is_active);
        }
    }

    public function test_service_with_positive_customer_price_can_be_activated(): void
    {
        $user = $this->userWithPermissions(['manage services']);
        $specialty = $this->createSpecialty('تخصص تفعيل بسعر');
        $service = Service::create([
            'specialty_id' => $specialty->id,
            'name' => 'خدمة بسعر معتمد',
            'customer_price' => '250.00',
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->patch(route('services.toggle-active', $service))
            ->assertSessionHas('success');

        $this->assertTrue($service->fresh()->is_active);
    }

    public function test_active_service_can_still_be_disabled_without_customer_price(): void
    {
        $user = $this->userWithPermissions(['manage services']);
        $specialty = $this->createSpecialty('تخصص تعطيل');
        $service = Service::create([
            'specialty_id' => $specialty->id,
            'name' => 'خدمة تاريخية بلا سعر',
            'customer_price' => null,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->patch(route('services.toggle-active', $service))
            ->assertSessionHas('success');

        $this->assertFalse($service->fresh()->is_active);
    }

    public function test_duplicate_service_inside_same_specialty_is_rejected(): void
    {
        $user = $this->userWithPermissions(['manage services']);
        $specialty = $this->createSpecialty('تخاطب');
        $this->createService($specialty, 'تقييم مبدئي');

        $this->actingAs($user)
            ->post(route('services.store'), [
                'specialty_id' => $specialty->id,
                'name' => 'تقييم مبدئي',
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('services', 1);
    }

    public function test_therapist_can_have_multiple_specialties_and_services_without_changing_legacy_specialization(): void
    {
        $user = $this->userWithPermissions(['manage therapist services']);
        $therapist = $this->createTherapist('اضطرابات النطق القديمة');
        $speech = $this->createSpecialty('تخاطب');
        $behavior = $this->createSpecialty('تعديل سلوك');
        $speechService = $this->createService($speech, 'جلسة تخاطب');
        $behaviorService = $this->createService($behavior, 'جلسة تعديل سلوك');

        $this->actingAs($user)
            ->get(route('therapists.services.edit', $therapist))
            ->assertOk()
            ->assertSeeText('سعر استحقاق الأخصائي');

        $this->actingAs($user)
            ->put(route('therapists.services.update', $therapist), [
                'specialty_ids' => [$speech->id, $behavior->id],
                'service_ids' => [$speechService->id, $behaviorService->id],
                'rates' => [
                    $speechService->id => ['amount' => 80, 'effective_from' => '2026-01-01'],
                    $behaviorService->id => ['amount' => 100, 'effective_from' => '2026-01-01'],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEqualsCanonicalizing([$speech->id, $behavior->id], $therapist->specialties()->pluck('specialties.id')->all());
        $this->assertEqualsCanonicalizing([$speechService->id, $behaviorService->id], $therapist->services()->pluck('services.id')->all());
        $this->assertSame('اضطرابات النطق القديمة', $therapist->fresh()->specialization);
    }

    public function test_therapist_cannot_be_assigned_service_outside_assigned_specialties(): void
    {
        $user = $this->userWithPermissions(['manage therapist services']);
        $therapist = $this->createTherapist();
        $speech = $this->createSpecialty('تخاطب');
        $behavior = $this->createSpecialty('تعديل سلوك');
        $unrelatedService = $this->createService($behavior, 'جلسة تعديل سلوك');

        $this->actingAs($user)
            ->put(route('therapists.services.update', $therapist), [
                'specialty_ids' => [$speech->id],
                'service_ids' => [$unrelatedService->id],
                'rates' => [
                    $unrelatedService->id => ['amount' => 100, 'effective_from' => '2026-01-01'],
                ],
            ])
            ->assertSessionHasErrors('service_ids');

        $this->assertDatabaseMissing('service_therapist', [
            'therapist_id' => $therapist->id,
            'service_id' => $unrelatedService->id,
        ]);
    }

    public function test_same_service_can_have_different_rates_for_two_therapists(): void
    {
        [$specialty, $service] = $this->specialtyAndService('تخاطب', 'جلسة تخاطب');
        $first = $this->createTherapist();
        $second = $this->createTherapist();
        $this->assignService($first, $specialty, $service);
        $this->assignService($second, $specialty, $service);

        TherapistServiceRate::start($first, $service, 80, '2026-01-01');
        TherapistServiceRate::start($second, $service, 100, '2026-01-01');

        $this->assertSame('80.00', TherapistServiceRate::resolveFor($first, $service, '2026-02-01')->amount);
        $this->assertSame('100.00', TherapistServiceRate::resolveFor($second, $service, '2026-02-01')->amount);
    }

    public function test_same_therapist_can_have_different_rates_for_multiple_services(): void
    {
        $specialty = $this->createSpecialty('تخاطب');
        $session = $this->createService($specialty, 'جلسة تخاطب');
        $assessment = $this->createService($specialty, 'تقييم مبدئي');
        $therapist = $this->createTherapist();
        $therapist->specialties()->attach($specialty);
        $therapist->services()->attach([$session->id, $assessment->id]);

        TherapistServiceRate::start($therapist, $session, 80, '2026-01-01');
        TherapistServiceRate::start($therapist, $assessment, 150, '2026-01-01');

        $this->assertSame('80.00', TherapistServiceRate::resolveFor($therapist, $session, '2026-03-01')->amount);
        $this->assertSame('150.00', TherapistServiceRate::resolveFor($therapist, $assessment, '2026-03-01')->amount);
    }

    public function test_changing_rate_creates_history_and_resolver_returns_past_and_current_rates(): void
    {
        [$specialty, $service] = $this->specialtyAndService('تخاطب', 'جلسة تخاطب');
        $therapist = $this->createTherapist();
        $this->assignService($therapist, $specialty, $service);

        $oldRate = TherapistServiceRate::start($therapist, $service, 80, '2026-01-01');
        $currentRate = TherapistServiceRate::start($therapist, $service, 100, '2026-06-01');

        $this->assertDatabaseCount('therapist_service_rates', 2);
        $this->assertSame('2026-05-31', $oldRate->fresh()->effective_to->format('Y-m-d'));
        $this->assertNull($currentRate->effective_to);
        $this->assertSame('80.00', TherapistServiceRate::resolveFor($therapist, $service, '2026-03-15')->amount);
        $this->assertSame('100.00', TherapistServiceRate::resolveFor($therapist, $service, '2026-08-15')->amount);
    }

    public function test_duplicate_therapist_specialty_pivot_is_prevented_by_database(): void
    {
        $specialty = $this->createSpecialty('تخاطب');
        $therapist = $this->createTherapist();
        $therapist->specialties()->attach($specialty);

        $this->expectException(QueryException::class);

        DB::table('specialty_therapist')->insert([
            'therapist_id' => $therapist->id,
            'specialty_id' => $specialty->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_overlapping_rate_period_is_rejected(): void
    {
        [$specialty, $service] = $this->specialtyAndService('تخاطب', 'جلسة تخاطب');
        $therapist = $this->createTherapist();
        $this->assignService($therapist, $specialty, $service);
        TherapistServiceRate::create([
            'therapist_id' => $therapist->id,
            'service_id' => $service->id,
            'amount' => 80,
            'effective_from' => '2026-01-01',
            'effective_to' => '2026-06-30',
        ]);

        $this->expectException(ValidationException::class);

        TherapistServiceRate::create([
            'therapist_id' => $therapist->id,
            'service_id' => $service->id,
            'amount' => 100,
            'effective_from' => '2026-06-01',
            'effective_to' => null,
        ]);
    }

    public function test_duplicate_therapist_service_pivot_is_prevented_by_database(): void
    {
        [$specialty, $service] = $this->specialtyAndService('تخاطب', 'جلسة تخاطب');
        $therapist = $this->createTherapist();
        $this->assignService($therapist, $specialty, $service);

        $this->expectException(QueryException::class);

        DB::table('service_therapist')->insert([
            'therapist_id' => $therapist->id,
            'service_id' => $service->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_service_rate_foundation_does_not_change_existing_payroll_data_or_create_earnings(): void
    {
        [$specialty, $service] = $this->specialtyAndService('تخاطب', 'جلسة تخاطب');
        $therapist = $this->createTherapist();
        $this->assignService($therapist, $specialty, $service);
        $payrollRecord = PayrollRecord::create([
            'therapist_id' => $therapist->id,
            'type' => 'إضافة',
            'amount' => 250,
            'description' => 'بدل قائم',
            'month' => 8,
            'year' => 2026,
        ]);

        TherapistServiceRate::start($therapist, $service, 90, '2026-08-01');

        $this->assertEquals(5000, $therapist->fresh()->monthly_salary);
        $this->assertEquals(250, $payrollRecord->fresh()->amount);
        $this->assertDatabaseCount((new TherapistEarning)->getTable(), 0);
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

    private function createSpecialty(string $name): Specialty
    {
        return Specialty::create(['name' => $name, 'is_active' => true]);
    }

    private function createService(Specialty $specialty, string $name): Service
    {
        return Service::create([
            'specialty_id' => $specialty->id,
            'name' => $name,
            'is_active' => true,
        ]);
    }

    private function createTherapist(string $legacySpecialization = 'تخصص قديم'): Therapist
    {
        return Therapist::create([
            'name' => 'أخصائي '.uniqid(),
            'specialization' => $legacySpecialization,
            'salary_type' => 'monthly',
            'monthly_salary' => 5000,
            'daily_salary' => 0,
            'commission_rate' => 0,
            'is_active' => true,
        ]);
    }

    private function specialtyAndService(string $specialtyName, string $serviceName): array
    {
        $specialty = $this->createSpecialty($specialtyName);

        return [$specialty, $this->createService($specialty, $serviceName)];
    }

    private function assignService(Therapist $therapist, Specialty $specialty, Service $service): void
    {
        $therapist->specialties()->attach($specialty);
        $therapist->services()->attach($service);
    }
}
