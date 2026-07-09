<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Patient;
use App\Models\PayrollRecord;
use App\Models\Setting;
use App\Models\Therapist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SettingsManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_is_protected_and_available_to_manager_or_admin(): void
    {
        $plainUser = User::factory()->create();
        $settingsUser = $this->userWithPermissions(['manage settings']);
        $admin = $this->userWithRole('مدير النظام', []);

        $this->actingAs($plainUser)
            ->get(route('settings.edit'))
            ->assertForbidden();

        $this->actingAs($settingsUser)
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertSeeText('بيانات العيادة')
            ->assertDontSee('name="twilio_auth_token"', false)
            ->assertDontSee('name="auth_token"', false);

        $this->actingAs($admin)
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertSeeText('إدارة البيانات والنسخ الاحتياطي');
    }

    public function test_unauthorized_user_cannot_update_or_upload_logo(): void
    {
        Storage::fake('public');

        $plainUser = User::factory()->create();

        $this->actingAs($plainUser)
            ->put(route('settings.update'), [
                'section' => 'clinic',
                'clinic_name' => 'Blocked Clinic',
                'logo' => UploadedFile::fake()->image('blocked.png', 300, 120),
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('settings', ['clinic_name' => 'Blocked Clinic']);
        Storage::disk('public')->assertMissing('settings/logos/blocked.png');
    }

    public function test_logo_upload_validation_and_safe_storage_name(): void
    {
        Storage::fake('public');

        $settingsUser = $this->userWithPermissions(['manage settings']);

        $this->actingAs($settingsUser)
            ->put(route('settings.update'), [
                'section' => 'clinic',
                'clinic_name' => 'Speech Clinic',
                'logo' => UploadedFile::fake()->create('not-image.pdf', 1, 'application/pdf'),
            ])
            ->assertSessionHasErrors('logo');

        $this->actingAs($settingsUser)
            ->put(route('settings.update'), [
                'section' => 'clinic',
                'clinic_name' => 'Speech Clinic',
                'logo' => UploadedFile::fake()->image('clinic-logo.png', 300, 120),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $settings = Setting::firstOrFail();

        $this->assertSame('clinic-logo.png', $settings->logo_original_name);
        $this->assertStringStartsWith('settings/logos/', $settings->logo_path);
        $this->assertStringNotContainsString('clinic-logo', $settings->logo_path);
        Storage::disk('public')->assertExists($settings->logo_path);
    }

    public function test_default_currency_values_are_egp_and_legacy_currency_symbol_is_egp(): void
    {
        $defaults = Setting::defaults();

        $this->assertSame('EGP', $defaults['currency_code']);
        $this->assertSame('ج.م', $defaults['currency_symbol']);
        $this->assertSame('ج.م', $defaults['currency']);

        $settings = Setting::firstOrCreate(['id' => 1], $defaults);

        $this->assertSame('EGP', $settings->currency_code);
        $this->assertSame('ج.م', $settings->currency_symbol);
        $this->assertSame('ج.م', $settings->currency);
    }

    public function test_legacy_currency_alignment_migration_only_updates_exact_old_symbol(): void
    {
        Setting::query()->delete();

        $legacy = Setting::create(array_merge(Setting::defaults(), [
            'clinic_name' => 'Legacy Currency Clinic',
            'currency' => 'ر.س',
        ]));
        $custom = Setting::create(array_merge(Setting::defaults(), [
            'clinic_name' => 'Custom Currency Clinic',
            'currency' => 'USD',
            'currency_symbol' => '$',
        ]));
        $manualSaudi = Setting::create(array_merge(Setting::defaults(), [
            'clinic_name' => 'Manual Saudi Currency Clinic',
            'currency' => 'ر.س',
            'currency_symbol' => 'ر.س',
        ]));

        $migration = require database_path('migrations/2026_07_07_000002_align_legacy_currency_setting_with_egp.php');
        $migration->up();

        $this->assertSame('ج.م', $legacy->fresh()->currency);
        $this->assertSame('USD', $custom->fresh()->currency);
        $this->assertSame('ر.س', $manualSaudi->fresh()->currency);
    }

    public function test_currency_settings_do_not_change_existing_invoice_amounts(): void
    {
        $settingsUser = $this->userWithPermissions(['manage settings']);
        $patient = $this->createPatient('Finance Settings Patient');
        $therapistUser = User::factory()->create();
        $therapist = Therapist::create([
            'user_id' => $therapistUser->id,
            'name' => 'Payroll Therapist',
            'salary_type' => 'monthly',
            'monthly_salary' => 3000,
            'daily_salary' => 0,
            'commission_rate' => 0,
            'is_active' => true,
        ]);

        $invoice = Invoice::create([
            'patient_id' => $patient->id,
            'invoice_number' => 'INV-SET-001',
            'issue_date' => '2026-07-07',
            'status' => 'غير مدفوعة',
            'total' => 1250.75,
        ]);
        $payment = InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'amount' => 250.25,
            'payment_date' => '2026-07-07',
            'method' => 'cash',
        ]);
        $payrollRecord = PayrollRecord::create([
            'therapist_id' => $therapist->id,
            'type' => 'إضافة',
            'amount' => 300.50,
            'description' => 'Bonus',
            'month' => 7,
            'year' => 2026,
        ]);

        $this->actingAs($settingsUser)
            ->put(route('settings.update'), [
                'section' => 'financial',
                'currency_code' => 'EGP',
                'currency_symbol' => 'ج.م',
                'default_therapist_commission_rate' => 35,
            ])
            ->assertRedirect();

        $settings = Setting::firstOrFail();

        $this->assertSame('EGP', $settings->currency_code);
        $this->assertSame('ج.م', $settings->currency_symbol);
        $this->assertSame('ج.م', $settings->currency);
        $this->assertEquals(1250.75, $invoice->fresh()->total);
        $this->assertEquals(250.25, $payment->fresh()->amount);
        $this->assertEquals(300.50, $payrollRecord->fresh()->amount);
    }

    public function test_default_commission_applies_to_new_therapist_user_only(): void
    {
        $admin = $this->userWithRole('مدير النظام', []);
        Role::firstOrCreate(['name' => 'أخصائي تخاطب', 'guard_name' => 'web']);

        Setting::firstOrCreate(['id' => 1], Setting::defaults())
            ->update(['default_therapist_commission_rate' => 42.5]);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'New Therapist',
                'email' => 'new-therapist@example.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'أخصائي تخاطب',
                'salary_type' => 'commission',
            ])
            ->assertRedirect(route('users.index'));

        $createdUser = User::where('email', 'new-therapist@example.test')->firstOrFail();
        $therapist = Therapist::where('user_id', $createdUser->id)->firstOrFail();

        $this->assertEquals(42.5, $therapist->commission_rate);
    }

    public function test_notification_templates_reject_unknown_variables_and_do_not_store_secrets(): void
    {
        $settingsUser = $this->userWithPermissions(['manage settings']);

        $this->actingAs($settingsUser)
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertDontSee('name="twilio_auth_token"', false)
            ->assertDontSee('name="api_secret"', false);

        $this->actingAs($settingsUser)
            ->put(route('settings.update'), [
                'section' => 'notifications',
                'appointment_reminders_enabled' => '1',
                'appointment_reminder_hours' => 24,
                'invoice_notifications_enabled' => '0',
                'notification_sender_number' => '+201000000000',
                'notification_sender_name' => 'Clinic',
                'appointment_reminder_template' => 'Hello {unknown_variable}',
                'invoice_notification_template' => 'فاتورة {invoice_number} من {clinic_name}',
            ])
            ->assertSessionHasErrors('appointment_reminder_template');

        $this->actingAs($settingsUser)
            ->put(route('settings.update'), [
                'section' => 'notifications',
                'appointment_reminders_enabled' => '1',
                'appointment_reminder_hours' => 24,
                'invoice_notifications_enabled' => '0',
                'notification_sender_number' => '+201000000000',
                'notification_sender_name' => 'Clinic',
                'appointment_reminder_template' => 'موعد {patient_name} في {appointment_date} الساعة {appointment_time}',
                'invoice_notification_template' => 'فاتورة {invoice_number} من {clinic_name}',
            ])
            ->assertRedirect();

        $this->assertSame('Clinic', Setting::firstOrFail()->notification_sender_name);
    }

    public function test_working_hours_accept_valid_structure_and_reject_invalid_time_range(): void
    {
        $settingsUser = $this->userWithPermissions(['manage settings']);
        $workingHours = Setting::defaultWorkingHours();
        $workingHours['saturday']['opens_at'] = '18:00';
        $workingHours['saturday']['closes_at'] = '09:00';

        $this->actingAs($settingsUser)
            ->put(route('settings.update'), [
                'section' => 'working_hours',
                'working_hours' => $workingHours,
            ])
            ->assertSessionHasErrors('working_hours.saturday.closes_at');

        $workingHours['saturday']['opens_at'] = '09:00';
        $workingHours['saturday']['closes_at'] = '18:00';
        $workingHours['friday']['is_open'] = false;
        $workingHours['friday']['opens_at'] = null;
        $workingHours['friday']['closes_at'] = null;

        $this->actingAs($settingsUser)
            ->put(route('settings.update'), [
                'section' => 'working_hours',
                'working_hours' => $workingHours,
            ])
            ->assertRedirect();

        $storedHours = Setting::firstOrFail()->working_hours;

        $this->assertTrue($storedHours['saturday']['is_open']);
        $this->assertSame('09:00', $storedHours['saturday']['opens_at']);
        $this->assertFalse($storedHours['friday']['is_open']);
        $this->assertNull($storedHours['friday']['opens_at']);
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
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $role->syncPermissions($permissions);

        $user = User::factory()->create();
        $user->assignRole($role);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    private function createPatient(string $name): Patient
    {
        $guardian = Guardian::create([
            'name' => "{$name} Guardian",
            'phone' => '01000000000',
        ]);

        return Patient::create([
            'guardian_id' => $guardian->id,
            'name' => $name,
            'birth_date' => '2020-01-01',
            'gender' => 'male',
            'barcode' => 'PAT-SET-' . uniqid(),
            'qr_code' => 'PAT-SET',
            'is_active' => true,
        ]);
    }
}
