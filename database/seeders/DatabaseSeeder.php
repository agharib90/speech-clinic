<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Guardian;
use App\Models\Patient;
use App\Models\SessionType;
use App\Models\Therapist;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ١. تشغيل Seeder الخاص بالأدوار والصلاحيات أولاً
        $this->call(RolePermissionSeeder::class);

        // ٢. إنشاء المستخدمين
        $admin = User::create([
            'name' => 'مدير النظام',
            'email' => 'admin@clinic.com',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole('مدير النظام'); // ربط الدور

        $receptionist = User::create([
            'name' => 'سارة الاستقبال',
            'email' => 'reception@clinic.com',
            'password' => Hash::make('password'),
        ]);
        $receptionist->assignRole('موظف استقبال'); // ربط الدور

        $therapistUser = User::create([
            'name' => 'د. أحمد الأخصائي',
            'email' => 'therapist@clinic.com',
            'password' => Hash::make('password'),
        ]);
        $therapistUser->assignRole('أخصائي تخاطب'); // ربط الدور

        $accountant = User::create([
            'name' => 'محمد المالي',
            'email' => 'finance@clinic.com',
            'password' => Hash::make('password'),
        ]);
        $accountant->assignRole('مسؤول مالي'); // ربط الدور

        // ٢. إنشاء أخصائيين (ربط المستخدم بجدول الأخصائيين)
        Therapist::create([
            'user_id' => $therapistUser->id,
            'name' => 'د. أحمد الأخصائي',
            'specialization' => 'اضطرابات النطق',
            'phone' => '0501234567',
            'salary_type' => 'commission',
            'commission_rate' => 40.00, // 40% من الجلسة
            'is_active' => true,
        ]);

        // ٣. إنشاء أنواع الجلسات
        SessionType::create(['name' => 'جلسة نطق', 'duration_minutes' => 30, 'price' => 150.00, 'color' => '#3B82F6']);
        SessionType::create(['name' => 'جلسة تخاطب', 'duration_minutes' => 45, 'price' => 200.00, 'color' => '#10B981']);
        SessionType::create(['name' => 'جلسة لغة', 'duration_minutes' => 30, 'price' => 150.00, 'color' => '#F59E0B']);
        SessionType::create(['name' => 'جلسة تقييم', 'duration_minutes' => 60, 'price' => 300.00, 'color' => '#EF4444']);

        // ٤. إنشاء أولياء أمور
        $guardian1 = Guardian::create([
            'name' => 'خالد المحمدي',
            'phone' => '0551234567',
            'phone2' => '0557654321',
            'email' => 'khaled@email.com',
            'address' => 'الرياض - حي النزهة',
            'national_id' => '1234567890',
        ]);

        $guardian2 = Guardian::create([
            'name' => 'فاطمة العلي',
            'phone' => '0559876543',
            'address' => 'الرياض - حي الملقا',
        ]);

        // ٥. إنشاء المرضى (الأطفال)
        Patient::create([
            'guardian_id' => $guardian1->id,
            'name' => 'عبدالله خالد',
            'birth_date' => '2021-05-10',
            'gender' => 'male',
            'diagnosis' => 'تأخر لغوي',
            'barcode' => 'PAT-10001',
            'qr_code' => 'PAT-10001',
            'referral_source' => 'طبيب أطفال',
            'is_active' => true,
        ]);

        Patient::create([
            'guardian_id' => $guardian1->id,
            'name' => 'سارة خالد',
            'birth_date' => '2019-11-22',
            'gender' => 'female',
            'diagnosis' => 'اضطراب طيف التوحد',
            'barcode' => 'PAT-10002',
            'qr_code' => 'PAT-10002',
            'is_active' => true,
        ]);

        Patient::create([
            'guardian_id' => $guardian2->id,
            'name' => 'محمد عمر',
            'birth_date' => '2022-02-14',
            'gender' => 'male',
            'diagnosis' => 'لثغة',
            'barcode' => 'PAT-10003',
            'qr_code' => 'PAT-10003',
            'is_active' => true,
        ]);
    }
}
