<?php

namespace Tests\Feature;

use App\Http\Controllers\GuardianController;
use App\Http\Controllers\PatientController;
use App\Models\Guardian;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class NewCaseRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_create_route_opens_the_new_case_wizard_and_is_not_shadowed(): void
    {
        $user = $this->userWithPermissions(['create patients']);
        $route = app('router')->getRoutes()->match(Request::create('/patients/create', 'GET'));

        $this->assertSame(PatientController::class.'@create', $route->getActionName());

        $this->actingAs($user)->get('/patients/create')
            ->assertOk()
            ->assertSeeText('إضافة حالة جديدة')
            ->assertSeeText('ولي الأمر')
            ->assertSeeText('بيانات الطفل')
            ->assertSeeText('مراجعة وحفظ');
    }

    public function test_guardian_search_matches_name_and_phone_without_exposing_unrelated_records(): void
    {
        $user = $this->userWithPermissions(['create patients']);
        $matchingName = Guardian::create(['name' => 'هشام عادل', 'phone' => '01011112222']);
        $matchingPhone = Guardian::create(['name' => 'منى سامي', 'phone' => '01234567890']);
        $unrelated = Guardian::create(['name' => 'سجل غير مرتبط', 'phone' => '01199998888']);

        $this->actingAs($user)->getJson(route('patients.guardian-search', ['query' => 'هشام']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $matchingName->id)
            ->assertJsonMissing(['id' => $unrelated->id]);

        $this->actingAs($user)->getJson(route('patients.guardian-search', ['query' => '345678']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $matchingPhone->id)
            ->assertJsonMissing(['id' => $unrelated->id]);

        $this->actingAs($user)->getJson(route('patients.guardian-search', ['query' => '%_']))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_guardian_search_requires_case_registration_permission(): void
    {
        $unauthorized = User::factory()->create();

        $this->actingAs($unauthorized)
            ->getJson(route('patients.guardian-search', ['query' => '010']))
            ->assertForbidden();
    }

    public function test_existing_guardian_is_linked_without_creating_a_duplicate_and_redirects_to_workspace(): void
    {
        $user = $this->userWithPermissions(['create patients', 'view patients']);
        $guardian = Guardian::create(['name' => 'هشام عادل', 'phone' => '01011112222']);

        $response = $this->actingAs($user)->post(route('patients.store'), $this->patientPayload([
            'guardian_mode' => 'existing',
            'guardian_id' => $guardian->id,
        ]));

        $patient = Patient::sole();

        $response->assertRedirect(route('patients.workspace', $patient));
        $this->assertDatabaseCount('guardians', 1);
        $this->assertDatabaseCount('patients', 1);
        $this->assertSame($guardian->id, $patient->guardian_id);
        $this->assertSame('PAT-'.str_pad($patient->id, 5, '0', STR_PAD_LEFT), $patient->barcode);
        $this->assertSame($patient->barcode, $patient->qr_code);
    }

    public function test_new_guardian_and_patient_are_created_together_and_linked(): void
    {
        $user = $this->userWithPermissions(['create patients', 'view patients']);

        $response = $this->actingAs($user)->post(route('patients.store'), $this->patientPayload([
            'guardian_mode' => 'new',
            'guardian' => [
                'name' => 'ولي أمر جديد',
                'phone' => '01055556666',
                'phone2' => '01155556666',
                'email' => 'guardian@example.com',
                'address' => 'القاهرة',
                'national_id' => '29901011234567',
                'notes' => 'يفضل التواصل مساءً',
            ],
        ]));

        $guardian = Guardian::sole();
        $patient = Patient::sole();

        $response->assertRedirect(route('patients.workspace', $patient));
        $this->assertSame($guardian->id, $patient->guardian_id);
        $this->assertSame('ولي أمر جديد', $guardian->name);
        $this->assertSame('01055556666', $guardian->phone);
        $this->assertSame($patient->barcode, $patient->qr_code);
    }

    public function test_invalid_patient_data_does_not_leave_a_new_guardian_or_patient(): void
    {
        $user = $this->userWithPermissions(['create patients']);

        $this->actingAs($user)->post(route('patients.store'), $this->patientPayload([
            'guardian_mode' => 'new',
            'guardian' => ['name' => 'ولي مؤقت', 'phone' => '01077778888'],
            'birth_date' => 'not-a-date',
        ]))->assertSessionHasErrors('birth_date');

        $this->assertDatabaseCount('guardians', 0);
        $this->assertDatabaseCount('patients', 0);
    }

    public function test_existing_guardian_phone_prevents_accidental_duplicate_creation(): void
    {
        $user = $this->userWithPermissions(['create patients']);
        Guardian::create(['name' => 'ولي موجود', 'phone' => '01099990000']);

        $this->actingAs($user)->post(route('patients.store'), $this->patientPayload([
            'guardian_mode' => 'new',
            'guardian' => ['name' => 'اسم مكرر', 'phone' => '01099990000'],
        ]))->assertSessionHasErrors('guardian.phone');

        $this->assertDatabaseCount('guardians', 1);
        $this->assertDatabaseCount('patients', 0);
    }

    public function test_duplicate_guardian_error_offers_safe_reuse_without_losing_patient_state(): void
    {
        $user = $this->userWithPermissions(['create patients']);
        $guardian = Guardian::create(['name' => 'ولي مسجل بالفعل', 'phone' => '01099990000']);

        $response = $this->from(route('patients.create'))->actingAs($user)->post(
            route('patients.store'),
            $this->patientPayload([
                'name' => 'طفل يحتفظ ببياناته',
                'diagnosis' => 'بيانات سريرية محفوظة',
                'guardian_mode' => 'new',
                'guardian' => ['name' => 'اسم مكرر', 'phone' => $guardian->phone],
            ])
        );

        $response->assertRedirect(route('patients.create'));
        $response->assertSessionHasInput('name', 'طفل يحتفظ ببياناته');
        $response->assertSessionHasInput('diagnosis', 'بيانات سريرية محفوظة');

        $this->actingAs($user)->get(route('patients.create'))
            ->assertOk()
            ->assertSeeText('استخدام ولي الأمر الموجود')
            ->assertViewHas('duplicateGuardian', fn (?Guardian $candidate) => $candidate?->is($guardian));

        $this->actingAs($user)->post(route('patients.store'), $this->patientPayload([
            'name' => 'طفل يحتفظ ببياناته',
            'guardian_mode' => 'existing',
            'guardian_id' => $guardian->id,
        ]))->assertRedirect();

        $this->assertDatabaseCount('guardians', 1);
        $this->assertSame($guardian->id, Patient::sole()->guardian_id);
    }

    public function test_registration_and_standalone_guardian_routes_keep_their_permissions_and_actions(): void
    {
        $unauthorized = User::factory()->create();

        $this->actingAs($unauthorized)->get(route('patients.create'))->assertForbidden();
        $this->actingAs($unauthorized)->post(route('patients.store'), [])->assertForbidden();

        $user = $this->userWithPermissions(['create patients']);
        $guardianCreateRoute = app('router')->getRoutes()->match(Request::create('/guardians/create', 'GET'));
        $this->assertSame(GuardianController::class.'@create', $guardianCreateRoute->getActionName());

        $this->actingAs($user)->get(route('guardians.create'))->assertOk();
        $this->actingAs($user)->post(route('guardians.store'), [
            'name' => 'ولي مستقل',
            'phone' => '01022223333',
        ])->assertRedirect(route('guardians.index'));
        $this->assertDatabaseHas('guardians', ['name' => 'ولي مستقل', 'phone' => '01022223333']);
    }

    private function patientPayload(array $overrides = []): array
    {
        return array_replace([
            'name' => 'طفل جديد',
            'birth_date' => '2020-05-10',
            'gender' => 'male',
            'diagnosis' => 'تأخر لغوي',
            'referral_source' => 'طبيب أطفال',
            'is_active' => '1',
            'notes' => 'ملاحظات الحالة',
        ], $overrides);
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
