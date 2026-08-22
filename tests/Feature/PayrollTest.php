<?php

namespace Tests\Feature;

use App\Models\Therapist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PayrollTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_add_a_payroll_record(): void
    {
        $user = $this->userWithPayrollPermission();
        $therapist = $this->createTherapist();

        $this->actingAs($user)
            ->from(route('payroll.index'))
            ->post(route('payroll.addRecord'), [
                'therapist_id' => $therapist->id,
                'type' => 'إضافة',
                'amount' => 275.50,
                'description' => 'بدل إضافي',
                'month' => 8,
                'year' => 2026,
            ])
            ->assertRedirect(route('payroll.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('payroll_records', [
            'therapist_id' => $therapist->id,
            'type' => 'إضافة',
            'amount' => 275.50,
            'description' => 'بدل إضافي',
            'month' => 8,
            'year' => 2026,
        ]);
    }

    public function test_payroll_record_validation_rejects_an_invalid_type(): void
    {
        $user = $this->userWithPayrollPermission();
        $therapist = $this->createTherapist();

        $this->actingAs($user)
            ->post(route('payroll.addRecord'), [
                'therapist_id' => $therapist->id,
                'type' => 'غير صالح',
                'amount' => 275.50,
                'description' => 'بدل إضافي',
                'month' => 8,
                'year' => 2026,
            ])
            ->assertSessionHasErrors('type');

        $this->assertDatabaseCount('payroll_records', 0);
    }

    private function userWithPayrollPermission(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name' => 'manage payroll',
            'guard_name' => 'web',
        ]);
        $user = User::factory()->create();
        $user->givePermissionTo($permission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    private function createTherapist(): Therapist
    {
        return Therapist::create([
            'name' => 'أخصائي اختبار الرواتب',
            'salary_type' => 'monthly',
            'daily_salary' => 0,
            'monthly_salary' => 5000,
            'commission_rate' => 0,
            'is_active' => true,
        ]);
    }
}
