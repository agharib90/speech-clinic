<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\ArticulationAssessment;
use App\Models\Attendance;
use App\Models\CaseHistory;
use App\Models\ClinicalProgressPoint;
use App\Models\DischargeSummary;
use App\Models\Equipment;
use App\Models\Guardian;
use App\Models\HomeTask;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\PackageUsage;
use App\Models\Patient;
use App\Models\PatientCheckin;
use App\Models\PayrollRecord;
use App\Models\ProgramAttachment;
use App\Models\ProgramEquipment;
use App\Models\ProgramPayment;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\SessionMilestone;
use App\Models\SessionPackage;
use App\Models\SessionType;
use App\Models\Setting;
use App\Models\StutteringAssessment;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Therapist;
use App\Models\TherapistEarning;
use App\Models\TherapyProgram;
use App\Models\TherapySession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedRolesAndUsers();
            $this->seedClinicData();
        });
    }

    private function seedRolesAndUsers(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'view patients', 'create patients', 'edit patients', 'delete patients',
            'manage checkins', 'view checkins',
            'view appointments', 'create appointments', 'edit appointments', 'delete appointments',
            'view therapy', 'create therapy', 'edit therapy', 'delete therapy',
            'manage milestones', 'manage home tasks',
            'view finance', 'manage invoices', 'manage quotations', 'manage packages',
            'view hr', 'manage therapists', 'manage payroll',
            'manage specialties', 'manage services', 'manage therapist services',
            'manage patient service plans', 'manage patient discounts',
            'manage inventory', 'manage suppliers',
            'view reports', 'manage settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $roles = [
            'مدير النظام' => $permissions,
            'مسؤول مالي' => [
                'view patients', 'view appointments',
                'view finance', 'manage invoices', 'manage quotations', 'manage packages',
                'view hr', 'manage therapists', 'manage payroll',
                'manage inventory', 'manage suppliers', 'view reports',
            ],
            'موظف استقبال' => [
                'view patients', 'create patients', 'edit patients',
                'manage checkins', 'view checkins',
                'view appointments', 'create appointments', 'edit appointments',
            ],
            'أخصائي تخاطب' => [
                'view patients',
                'view appointments', 'edit appointments',
                'view therapy', 'create therapy', 'edit therapy',
                'manage milestones', 'manage home tasks',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web'])
                ->syncPermissions($rolePermissions);
        }

        $admin = User::where('email', 'admin@clinic.com')->first();
        if (! $admin) {
            $adminPassword = 'Admin-' . str()->random(10);
            $admin = User::create([
                'name' => 'مدير النظام',
                'email' => 'admin@clinic.com',
                'password' => Hash::make($adminPassword),
                'email_verified_at' => now(),
            ]);

            $this->command?->warn("Demo admin created: admin@clinic.com / {$adminPassword}");
        }

        $admin->syncRoles(['مدير النظام']);

        $demoUsers = [
            ['name' => 'سارة الاستقبال', 'email' => 'reception.demo@clinic.test', 'role' => 'موظف استقبال'],
            ['name' => 'محمد المالي', 'email' => 'finance.demo@clinic.test', 'role' => 'مسؤول مالي'],
            ['name' => 'د. سارة النطق', 'email' => 'sara.therapist@clinic.test', 'role' => 'أخصائي تخاطب'],
            ['name' => 'أ. عمر التخاطب', 'email' => 'omar.therapist@clinic.test', 'role' => 'أخصائي تخاطب'],
        ];

        foreach ($demoUsers as $demoUser) {
            $user = User::updateOrCreate(
                ['email' => $demoUser['email']],
                [
                    'name' => $demoUser['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([$demoUser['role']]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function seedClinicData(): void
    {
        Setting::updateOrCreate(
            ['id' => 1],
            ['clinic_name' => 'مركز البيان للتخاطب', 'currency' => 'ج.م']
        );

        $reception = User::where('email', 'reception.demo@clinic.test')->firstOrFail();
        $saraUser = User::where('email', 'sara.therapist@clinic.test')->firstOrFail();
        $omarUser = User::where('email', 'omar.therapist@clinic.test')->firstOrFail();

        $saraTherapist = Therapist::updateOrCreate(
            ['user_id' => $saraUser->id],
            [
                'name' => $saraUser->name,
                'specialization' => 'اضطرابات النطق واللغة',
                'phone' => '01010001001',
                'email' => $saraUser->email,
                'license_number' => 'SLP-2026-101',
                'hire_date' => now()->subMonths(8)->toDateString(),
                'salary_type' => 'commission',
                'daily_salary' => 0,
                'monthly_salary' => 0,
                'commission_rate' => 40,
                'is_active' => true,
            ]
        );

        $omarTherapist = Therapist::updateOrCreate(
            ['user_id' => $omarUser->id],
            [
                'name' => $omarUser->name,
                'specialization' => 'تأهيل التخاطب وصعوبات التواصل',
                'phone' => '01010001002',
                'email' => $omarUser->email,
                'license_number' => 'SLP-2026-102',
                'hire_date' => now()->subMonths(5)->toDateString(),
                'salary_type' => 'monthly',
                'daily_salary' => 0,
                'monthly_salary' => 12000,
                'commission_rate' => 0,
                'is_active' => true,
            ]
        );

        $sessionTypes = $this->seedSessionTypes();
        $guardians = $this->seedGuardians();
        $patients = $this->seedPatients($guardians);
        $suppliers = $this->seedInventory();
        $equipment = Equipment::all()->keyBy('name');

        $programs = $this->seedPrograms($patients, $saraUser, $omarUser, $sessionTypes);
        $appointments = $this->seedAppointments($patients, $saraUser, $omarUser, $sessionTypes);
        $sessions = $this->seedSessions($programs, $appointments, $sessionTypes);

        $this->seedClinicalTools($patients, $programs, $saraUser, $omarUser);
        $this->seedCheckins($patients, $appointments, $reception);
        $this->seedProgressAndTasks($programs, $sessions);
        $this->seedPackages($patients, $sessions);
        $this->seedFinance($patients, $programs);
        $this->seedHr($saraTherapist, $omarTherapist, $sessions);
        $this->seedProgramAssets($programs, $equipment);
        $this->seedSupplierPayments($suppliers);
    }

    private function seedSessionTypes(): array
    {
        $types = [
            ['name' => 'جلسة تقييم شامل', 'duration_minutes' => 60, 'price' => 350, 'color' => '#EF4444'],
            ['name' => 'جلسة نطق', 'duration_minutes' => 30, 'price' => 180, 'color' => '#3B82F6'],
            ['name' => 'جلسة تخاطب', 'duration_minutes' => 45, 'price' => 240, 'color' => '#10B981'],
            ['name' => 'جلسة إرشاد أسري', 'duration_minutes' => 30, 'price' => 150, 'color' => '#F59E0B'],
        ];

        $created = [];
        foreach ($types as $type) {
            $created[$type['name']] = SessionType::updateOrCreate(['name' => $type['name']], $type);
        }

        return $created;
    }

    private function seedGuardians(): array
    {
        $data = [
            ['name' => 'أحمد محمود', 'phone' => '01020000001', 'phone2' => '01020000011', 'email' => 'guardian.ahmed@example.test', 'address' => 'القاهرة - مدينة نصر', 'national_id' => 'DEMO-NID-001', 'notes' => 'يفضل التواصل مساء.'],
            ['name' => 'منى خالد', 'phone' => '01020000002', 'phone2' => null, 'email' => 'guardian.mona@example.test', 'address' => 'الجيزة - الدقي', 'national_id' => 'DEMO-NID-002', 'notes' => 'متابعة أسبوعية منتظمة.'],
            ['name' => 'هشام عادل', 'phone' => '01020000003', 'phone2' => '01020000033', 'email' => 'guardian.hesham@example.test', 'address' => 'القاهرة - التجمع', 'national_id' => 'DEMO-NID-003', 'notes' => 'طلب تقرير تقدم شهري.'],
            ['name' => 'ريم يوسف', 'phone' => '01020000004', 'phone2' => null, 'email' => 'guardian.reem@example.test', 'address' => 'الإسكندرية - سموحة', 'national_id' => 'DEMO-NID-004', 'notes' => 'حالة تجريبية بعيدة عن المركز.'],
        ];

        $guardians = [];
        foreach ($data as $guardian) {
            $guardians[$guardian['national_id']] = Guardian::updateOrCreate(
                ['national_id' => $guardian['national_id']],
                $guardian
            );
        }

        return $guardians;
    }

    private function seedPatients(array $guardians): array
    {
        $data = [
            ['guardian_key' => 'DEMO-NID-001', 'name' => 'يوسف أحمد', 'birth_date' => '2020-04-12', 'gender' => 'male', 'diagnosis' => 'تأخر لغوي بسيط', 'barcode' => 'PAT-DEMO-001', 'referral_source' => 'طبيب أطفال', 'is_active' => true, 'notes' => 'يحتاج تدريبات مفردات يومية.'],
            ['guardian_key' => 'DEMO-NID-001', 'name' => 'ليلى أحمد', 'birth_date' => '2018-09-20', 'gender' => 'female', 'diagnosis' => 'لثغة في حرف الراء', 'barcode' => 'PAT-DEMO-002', 'referral_source' => 'ترشيح ولي أمر', 'is_active' => true, 'notes' => 'تحسن ملحوظ بعد الجلسات الأولى.'],
            ['guardian_key' => 'DEMO-NID-002', 'name' => 'مالك كريم', 'birth_date' => '2019-01-08', 'gender' => 'male', 'diagnosis' => 'اضطراب نطق أصوات متعددة', 'barcode' => 'PAT-DEMO-003', 'referral_source' => 'مدرسة', 'is_active' => true, 'notes' => 'يحتاج متابعة للانتباه أثناء الجلسة.'],
            ['guardian_key' => 'DEMO-NID-003', 'name' => 'نور هشام', 'birth_date' => '2021-06-15', 'gender' => 'female', 'diagnosis' => 'تأخر تواصل اجتماعي', 'barcode' => 'PAT-DEMO-004', 'referral_source' => 'عيادة نفسية', 'is_active' => true, 'notes' => 'تدريب تواصل بصري وتقليد.'],
            ['guardian_key' => 'DEMO-NID-004', 'name' => 'آدم سامي', 'birth_date' => '2017-12-30', 'gender' => 'male', 'diagnosis' => 'متابعة بعد برنامج سابق', 'barcode' => 'PAT-DEMO-005', 'referral_source' => 'بحث إلكتروني', 'is_active' => false, 'notes' => 'حالة غير نشطة لاختبار الفلاتر.'],
        ];

        $patients = [];
        foreach ($data as $patient) {
            $patients[$patient['barcode']] = Patient::updateOrCreate(
                ['barcode' => $patient['barcode']],
                [
                    'guardian_id' => $guardians[$patient['guardian_key']]->id,
                    'name' => $patient['name'],
                    'birth_date' => $patient['birth_date'],
                    'gender' => $patient['gender'],
                    'diagnosis' => $patient['diagnosis'],
                    'qr_code' => $patient['barcode'],
                    'referral_source' => $patient['referral_source'],
                    'is_active' => $patient['is_active'],
                    'notes' => $patient['notes'],
                ]
            );
        }

        return $patients;
    }

    private function seedInventory(): array
    {
        $supplierData = [
            ['name' => 'شركة النور للمستلزمات', 'phone' => '0220001001', 'email' => 'supplies.alnour@example.test', 'address' => 'القاهرة - العتبة', 'balance' => 1800, 'notes' => 'توريد أدوات علاجية شهرية.'],
            ['name' => 'مكتبة الطفل التعليمية', 'phone' => '0220001002', 'email' => 'kids.books@example.test', 'address' => 'الجيزة - فيصل', 'balance' => 450, 'notes' => 'بطاقات وصور تعليمية.'],
        ];

        $suppliers = [];
        foreach ($supplierData as $supplier) {
            $suppliers[$supplier['name']] = Supplier::updateOrCreate(['email' => $supplier['email']], $supplier);
        }

        $equipmentData = [
            ['name' => 'بطاقات صور كلمات', 'category' => 'أدوات تعليمية', 'quantity' => 6, 'unit' => 'مجموعة', 'price' => 250, 'reorder_level' => 2, 'supplier' => 'مكتبة الطفل التعليمية', 'notes' => 'تستخدم في جلسات المفردات.'],
            ['name' => 'مرآة تدريب نطق', 'category' => 'أدوات علاجية', 'quantity' => 4, 'unit' => 'قطعة', 'price' => 120, 'reorder_level' => 1, 'supplier' => 'شركة النور للمستلزمات', 'notes' => 'تدريب مخارج الحروف.'],
            ['name' => 'مكعبات تواصل', 'category' => 'ألعاب علاجية', 'quantity' => 12, 'unit' => 'قطعة', 'price' => 45, 'reorder_level' => 5, 'supplier' => 'مكتبة الطفل التعليمية', 'notes' => 'للتعزيز والتفاعل.'],
            ['name' => 'سماعة تسجيل جلسات', 'category' => 'أجهزة', 'quantity' => 1, 'unit' => 'قطعة', 'price' => 950, 'reorder_level' => 1, 'supplier' => 'شركة النور للمستلزمات', 'notes' => 'كمية منخفضة لاختبار التنبيهات.'],
        ];

        foreach ($equipmentData as $item) {
            Equipment::updateOrCreate(
                ['name' => $item['name']],
                [
                    'category' => $item['category'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'price' => $item['price'],
                    'reorder_level' => $item['reorder_level'],
                    'supplier_id' => $suppliers[$item['supplier']]->id,
                    'notes' => $item['notes'],
                ]
            );
        }

        return $suppliers;
    }

    private function seedPrograms(array $patients, User $saraUser, User $omarUser, array $sessionTypes): array
    {
        $programData = [
            ['patient' => 'PAT-DEMO-001', 'therapist' => $saraUser, 'name' => 'برنامج تنمية اللغة التعبيرية', 'disorder_type' => 'تأخر لغوي', 'goals' => 'زيادة الحصيلة اللغوية، تكوين جملة من كلمتين، تحسين الطلب اللفظي.', 'status' => 'جاري', 'start_date' => now()->subWeeks(6), 'sessions_per_week' => 2, 'session_price' => $sessionTypes['جلسة تخاطب']->price, 'notes' => 'برنامج نشط مع تقدم تدريجي.'],
            ['patient' => 'PAT-DEMO-002', 'therapist' => $saraUser, 'name' => 'برنامج تصحيح مخارج الحروف', 'disorder_type' => 'لثغة', 'goals' => 'تثبيت صوت الراء في المقاطع والكلمات والجمل.', 'status' => 'جاري', 'start_date' => now()->subWeeks(4), 'sessions_per_week' => 1, 'session_price' => $sessionTypes['جلسة نطق']->price, 'notes' => 'تركيز على واجبات منزلية قصيرة.'],
            ['patient' => 'PAT-DEMO-003', 'therapist' => $omarUser, 'name' => 'برنامج وضوح الكلام', 'disorder_type' => 'اضطراب نطق', 'goals' => 'تمييز سمعي، إنتاج أصوات مستهدفة، تعميم داخل الحوار.', 'status' => 'جاري', 'start_date' => now()->subWeeks(3), 'sessions_per_week' => 2, 'session_price' => $sessionTypes['جلسة نطق']->price, 'notes' => 'يحتاج تعزيز بصري.'],
            ['patient' => 'PAT-DEMO-004', 'therapist' => $omarUser, 'name' => 'برنامج تواصل اجتماعي مبكر', 'disorder_type' => 'تأخر تواصل', 'goals' => 'الانتباه المشترك، التقليد، التواصل البصري، الطلب بالإشارة.', 'status' => 'متوقف', 'start_date' => now()->subMonths(2), 'end_date' => now()->subWeeks(1), 'sessions_per_week' => 1, 'session_price' => $sessionTypes['جلسة تخاطب']->price, 'notes' => 'متوقف مؤقتًا بناء على طلب الأسرة.'],
        ];

        $programs = [];
        foreach ($programData as $program) {
            $programs[$program['patient']] = TherapyProgram::updateOrCreate(
                ['patient_id' => $patients[$program['patient']]->id, 'therapist_id' => $program['therapist']->id],
                [
                    'name' => $program['name'],
                    'disorder_type' => $program['disorder_type'],
                    'goals' => $program['goals'],
                    'status' => $program['status'],
                    'start_date' => $program['start_date']->toDateString(),
                    'end_date' => isset($program['end_date']) ? $program['end_date']->toDateString() : null,
                    'sessions_per_week' => $program['sessions_per_week'],
                    'session_price' => $program['session_price'],
                    'notes' => $program['notes'],
                ]
            );
        }

        return $programs;
    }

    private function seedAppointments(array $patients, User $saraUser, User $omarUser, array $sessionTypes): array
    {
        $appointments = [];
        $rows = [
            ['key' => 'today-1', 'patient' => 'PAT-DEMO-001', 'therapist' => $saraUser, 'type' => 'جلسة تخاطب', 'at' => now()->setTime(10, 0), 'status' => 'مجدول', 'notes' => 'موعد اليوم لاختبار شاشة الجدولة.'],
            ['key' => 'today-2', 'patient' => 'PAT-DEMO-002', 'therapist' => $saraUser, 'type' => 'جلسة نطق', 'at' => now()->setTime(12, 0), 'status' => 'مكتمل', 'notes' => 'موعد مكتمل مرتبط بجلسة.'],
            ['key' => 'tomorrow-1', 'patient' => 'PAT-DEMO-003', 'therapist' => $omarUser, 'type' => 'جلسة نطق', 'at' => now()->addDay()->setTime(11, 30), 'status' => 'مجدول', 'notes' => 'موعد قادم.'],
            ['key' => 'missed', 'patient' => 'PAT-DEMO-004', 'therapist' => $omarUser, 'type' => 'جلسة تخاطب', 'at' => now()->subDays(2)->setTime(13, 0), 'status' => 'غياب', 'notes' => 'حالة غياب.'],
            ['key' => 'cancelled', 'patient' => 'PAT-DEMO-001', 'therapist' => $saraUser, 'type' => 'جلسة إرشاد أسري', 'at' => now()->addDays(2)->setTime(9, 30), 'status' => 'ملغى', 'notes' => 'موعد ملغى لاختبار الحالة.'],
        ];

        foreach ($rows as $row) {
            $start = Carbon::parse($row['at']);
            $type = $sessionTypes[$row['type']];
            $appointments[$row['key']] = Appointment::updateOrCreate(
                ['patient_id' => $patients[$row['patient']]->id, 'scheduled_at' => $start],
                [
                    'therapist_id' => $row['therapist']->id,
                    'session_type_id' => $type->id,
                    'end_at' => $start->copy()->addMinutes($type->duration_minutes),
                    'status' => $row['status'],
                    'notes' => $row['notes'],
                ]
            );
        }

        return $appointments;
    }

    private function seedSessions(array $programs, array $appointments, array $sessionTypes): array
    {
        $sessions = [];
        $sessionRows = [
            ['key' => 'p1-s1', 'program' => 'PAT-DEMO-001', 'appointment' => null, 'date' => now()->subWeeks(3), 'number' => 1, 'type' => 'جلسة تخاطب', 'status' => 'مكتملة', 'notes' => 'تعرف على 10 صور جديدة.', 'goals_achieved' => 'استخدم 6 مفردات بشكل مستقل.', 'next_session_plan' => 'تدريب جمل من كلمتين.', 'internal_notes' => 'الانتباه يحتاج فواصل قصيرة.'],
            ['key' => 'p1-s2', 'program' => 'PAT-DEMO-001', 'appointment' => null, 'date' => now()->subWeeks(2), 'number' => 2, 'type' => 'جلسة تخاطب', 'status' => 'مكتملة', 'notes' => 'تدريب الطلب اللفظي.', 'goals_achieved' => 'طلب 4 عناصر بالكلام.', 'next_session_plan' => 'تعميم الطلب في اللعب.', 'internal_notes' => null],
            ['key' => 'p2-s1', 'program' => 'PAT-DEMO-002', 'appointment' => 'today-2', 'date' => now(), 'number' => 1, 'type' => 'جلسة نطق', 'status' => 'مكتملة', 'notes' => 'تدريب صوت الراء في المقاطع.', 'goals_achieved' => 'إنتاج صحيح بنسبة 55%.', 'next_session_plan' => 'كلمات تبدأ بصوت الراء.', 'internal_notes' => 'تحتاج تعزيز مرئي.'],
            ['key' => 'p3-s1', 'program' => 'PAT-DEMO-003', 'appointment' => null, 'date' => now()->subDays(5), 'number' => 1, 'type' => 'جلسة نطق', 'status' => 'مكتملة', 'notes' => 'تمييز سمعي بين س/ش.', 'goals_achieved' => 'تمييز 70% من البطاقات.', 'next_session_plan' => 'إنتاج الصوت في أول الكلمة.', 'internal_notes' => null],
        ];

        foreach ($sessionRows as $row) {
            $appointmentId = $row['appointment'] ? $appointments[$row['appointment']]->id : null;
            $type = $sessionTypes[$row['type']];
            $sessions[$row['key']] = TherapySession::updateOrCreate(
                [
                    'therapy_program_id' => $programs[$row['program']]->id,
                    'session_number' => $row['number'],
                ],
                [
                    'appointment_id' => $appointmentId,
                    'session_date' => Carbon::parse($row['date'])->toDateString(),
                    'duration_minutes' => $type->duration_minutes,
                    'status' => $row['status'],
                    'notes' => $row['notes'],
                    'goals_achieved' => $row['goals_achieved'],
                    'next_session_plan' => $row['next_session_plan'],
                    'internal_notes' => $row['internal_notes'],
                ]
            );
        }

        return $sessions;
    }

    private function seedCheckins(array $patients, array $appointments, User $reception): void
    {
        PatientCheckin::updateOrCreate(
            ['patient_id' => $patients['PAT-DEMO-001']->id, 'checkin_at' => now()->setTime(9, 45)],
            [
                'appointment_id' => $appointments['today-1']->id,
                'checkout_at' => null,
                'checked_by' => $reception->id,
                'method' => 'barcode',
            ]
        );

        PatientCheckin::updateOrCreate(
            ['patient_id' => $patients['PAT-DEMO-002']->id, 'checkin_at' => now()->setTime(11, 50)],
            [
                'appointment_id' => $appointments['today-2']->id,
                'checkout_at' => now()->setTime(12, 45),
                'checked_by' => $reception->id,
                'method' => 'manual',
            ]
        );
    }

    private function seedProgressAndTasks(array $programs, array $sessions): void
    {
        $milestones = [
            ['program' => 'PAT-DEMO-001', 'skill_area' => 'الحصيلة اللغوية', 'baseline_score' => 25, 'current_score' => 55, 'notes' => 'زيادة واضحة في تسمية الصور.'],
            ['program' => 'PAT-DEMO-001', 'skill_area' => 'تكوين الجملة', 'baseline_score' => 10, 'current_score' => 35, 'notes' => 'بدأ استخدام فعل واسم.'],
            ['program' => 'PAT-DEMO-002', 'skill_area' => 'صوت الراء', 'baseline_score' => 5, 'current_score' => 55, 'notes' => 'تحسن في المقاطع المفتوحة.'],
            ['program' => 'PAT-DEMO-003', 'skill_area' => 'التمييز السمعي', 'baseline_score' => 30, 'current_score' => 70, 'notes' => 'استجابة أفضل مع البطاقات.'],
        ];

        foreach ($milestones as $milestone) {
            SessionMilestone::updateOrCreate(
                ['therapy_program_id' => $programs[$milestone['program']]->id, 'skill_area' => $milestone['skill_area']],
                [
                    'recorded_at' => now()->subDays(rand(1, 12)),
                    'baseline_score' => $milestone['baseline_score'],
                    'current_score' => $milestone['current_score'],
                    'notes' => $milestone['notes'],
                ]
            );
        }

        $tasks = [
            ['session' => 'p1-s2', 'description' => 'تسمية 10 صور من كروت الحيوانات يوميًا.', 'due_date' => now()->addDays(5), 'is_completed' => false, 'parent_feedback' => 'تم التدريب 3 أيام حتى الآن.'],
            ['session' => 'p2-s1', 'description' => 'تكرار مقاطع را/رو/ري أمام المرآة.', 'due_date' => now()->addDays(4), 'is_completed' => false, 'parent_feedback' => null],
            ['session' => 'p3-s1', 'description' => 'لعبة تمييز صوت س وش لمدة 10 دقائق.', 'due_date' => now()->addDays(6), 'is_completed' => true, 'parent_feedback' => 'استجاب بشكل جيد مع الصور الملونة.'],
        ];

        foreach ($tasks as $task) {
            HomeTask::updateOrCreate(
                ['therapy_session_id' => $sessions[$task['session']]->id, 'description' => $task['description']],
                [
                    'due_date' => $task['due_date']->toDateString(),
                    'is_completed' => $task['is_completed'],
                    'parent_feedback' => $task['parent_feedback'],
                ]
            );
        }
    }

    private function seedClinicalTools(array $patients, array $programs, User $saraUser, User $omarUser): void
    {
        $histories = [
            'PAT-DEMO-001' => [
                'taken_by' => $saraUser->id,
                'taken_at' => now()->subWeeks(6)->toDateString(),
                'main_concerns' => 'تأخر في استخدام الجمل وطلب الأشياء غالبا بالإشارة.',
                'prenatal_history' => 'حمل مستقر دون مضاعفات مؤثرة.',
                'birth_history' => 'ولادة طبيعية في موعدها.',
                'developmental_milestones' => 'المشي في العمر المتوقع، بداية الكلمات متأخرة نسبيا.',
                'medical_history' => 'لا توجد أمراض مزمنة مسجلة.',
                'hearing_vision_notes' => 'فحص السمع الأخير داخل الحدود الطبيعية حسب ولي الأمر.',
                'family_history' => 'لا توجد حالات مشابهة واضحة في الأسرة.',
                'language_environment' => 'يتعرض للعربية العامية في المنزل مع وقت شاشة متوسط.',
                'previous_interventions' => 'تدريبات منزلية غير منتظمة قبل الالتحاق بالمركز.',
                'notes' => 'استجابة جيدة للتعزيز البصري واللعب المنظم.',
            ],
            'PAT-DEMO-002' => [
                'taken_by' => $saraUser->id,
                'taken_at' => now()->subWeeks(4)->toDateString(),
                'main_concerns' => 'صعوبة واضحة في صوت الراء داخل الكلمات والجمل.',
                'prenatal_history' => 'لا توجد ملاحظات مؤثرة.',
                'birth_history' => 'ولادة قيصرية دون احتياج حضانة.',
                'developmental_milestones' => 'تطور لغوي عام مناسب مع خطأ نطقي محدد.',
                'medical_history' => 'لا توجد عمليات أو إصابات مسجلة.',
                'hearing_vision_notes' => 'لا توجد شكاوى سمعية أو بصرية.',
                'family_history' => 'وجود لثغة مشابهة لدى أحد الأقارب في الطفولة.',
                'language_environment' => 'لغة عربية في المنزل والمدرسة.',
                'previous_interventions' => 'لا توجد جلسات سابقة منتظمة.',
                'notes' => 'دافعية جيدة أمام المرآة وبطاقات الكلمات.',
            ],
            'PAT-DEMO-003' => [
                'taken_by' => $omarUser->id,
                'taken_at' => now()->subWeeks(3)->toDateString(),
                'main_concerns' => 'أخطاء نطق متعددة تؤثر على وضوح الكلام.',
                'prenatal_history' => 'الحمل مستقر حسب التاريخ المأخوذ.',
                'birth_history' => 'لا توجد مضاعفات ولادة مذكورة.',
                'developmental_milestones' => 'اكتساب الكلمات كان متأخرا بدرجة بسيطة.',
                'medical_history' => 'التهابات أذن متكررة سابقا حسب ولي الأمر.',
                'hearing_vision_notes' => 'يوصى بمتابعة السمع إذا استمرت أخطاء التمييز.',
                'family_history' => 'لا توجد حالات معروفة.',
                'language_environment' => 'يتواصل بالعربية في المنزل ويتعرض لمحتوى أطفال يوميا.',
                'previous_interventions' => 'جلسات قصيرة غير مكتملة في مركز آخر.',
                'notes' => 'يحتاج فواصل انتباه قصيرة أثناء الاختبارات.',
            ],
        ];

        foreach ($histories as $barcode => $history) {
            CaseHistory::updateOrCreate(
                ['patient_id' => $patients[$barcode]->id],
                $history
            );
        }

        $progressRows = [
            ['program' => 'PAT-DEMO-001', 'domain' => 'اللغة التعبيرية', 'days' => 35, 'score' => 25, 'notes' => 'خط أساس عند بدء البرنامج.'],
            ['program' => 'PAT-DEMO-001', 'domain' => 'اللغة التعبيرية', 'days' => 21, 'score' => 42, 'notes' => 'تحسن في الطلب اللفظي.'],
            ['program' => 'PAT-DEMO-001', 'domain' => 'اللغة التعبيرية', 'days' => 7, 'score' => 58, 'notes' => 'بداية استخدام جمل قصيرة.'],
            ['program' => 'PAT-DEMO-002', 'domain' => 'النطق', 'days' => 28, 'score' => 10, 'notes' => 'خط أساس لصوت الراء.'],
            ['program' => 'PAT-DEMO-002', 'domain' => 'النطق', 'days' => 14, 'score' => 38, 'notes' => 'إنتاج أفضل في المقاطع.'],
            ['program' => 'PAT-DEMO-002', 'domain' => 'النطق', 'days' => 2, 'score' => 62, 'notes' => 'تحسن في الكلمات المفردة.'],
            ['program' => 'PAT-DEMO-003', 'domain' => 'الطلاقة', 'days' => 18, 'score' => 40, 'notes' => 'بداية متابعة الطلاقة.'],
            ['program' => 'PAT-DEMO-003', 'domain' => 'الطلاقة', 'days' => 5, 'score' => 55, 'notes' => 'تحسن مع تنظيم معدل الكلام.'],
        ];

        foreach ($progressRows as $row) {
            ClinicalProgressPoint::updateOrCreate(
                [
                    'therapy_program_id' => $programs[$row['program']]->id,
                    'domain' => $row['domain'],
                    'recorded_at' => now()->subDays($row['days'])->toDateString(),
                ],
                [
                    'recorded_by' => $programs[$row['program']]->therapist_id,
                    'score' => $row['score'],
                    'notes' => $row['notes'],
                ]
            );
        }

        $articulationResponses = collect(ArticulationAssessment::soundBank())
            ->map(function (array $item) {
                $status = match ($item['sound']) {
                    'ر' => ArticulationAssessment::STATUS_SUBSTITUTED,
                    'س' => ArticulationAssessment::STATUS_DISTORTED,
                    'ق' => ArticulationAssessment::STATUS_OMITTED,
                    default => ArticulationAssessment::STATUS_CORRECT,
                };

                return $item + [
                    'status' => $status,
                    'substitution' => $item['sound'] === 'ر' ? 'ل' : null,
                    'notes' => $status === ArticulationAssessment::STATUS_CORRECT ? null : 'يحتاج تدريب داخل كلمات متعددة.',
                ];
            })
            ->values()
            ->all();

        $this->upsertArticulationAssessment(
            $programs['PAT-DEMO-002'],
            $saraUser,
            now()->subDays(6)->toDateString(),
            $articulationResponses,
            'اختبار النطق العربي - خط أساس',
            'تظهر صعوبة رئيسية في الراء مع أخطاء أقل في السين والقاف.'
        );

        $percentage = round((16 / 240) * 100, 2);
        $frequencyScore = StutteringAssessment::frequencyScore($percentage);
        $totalScore = $frequencyScore + 5 + 3;

        StutteringAssessment::updateOrCreate(
            [
                'therapy_program_id' => $programs['PAT-DEMO-003']->id,
                'assessed_at' => now()->subDays(4)->toDateString(),
            ],
            [
                'assessed_by' => $omarUser->id,
                'sample_context' => 'حوار حر لمدة خمس دقائق',
                'syllables_count' => 240,
                'stuttered_syllables_count' => 16,
                'stuttering_percentage' => $percentage,
                'frequency_score' => $frequencyScore,
                'duration_score' => 5,
                'physical_concomitants_score' => 3,
                'total_score' => $totalScore,
                'severity' => StutteringAssessment::severityForTotal($totalScore),
                'notes' => 'تزداد التأتأة مع الأسئلة المفتوحة وتقل مع النمذجة البطيئة.',
            ]
        );

        DischargeSummary::updateOrCreate(
            ['therapy_program_id' => $programs['PAT-DEMO-004']->id],
            [
                'prepared_by' => $omarUser->id,
                'discharge_date' => now()->subDays(1)->toDateString(),
                'reason' => 'تحقق أهداف المرحلة الأولى',
                'initial_status' => 'تواصل بصري محدود واستجابة ضعيفة للمبادرة الاجتماعية.',
                'final_status' => 'زيادة واضحة في المبادرة وتقليد الأصوات والحركات.',
                'goals_outcome' => 'تم تحقيق معظم أهداف التواصل الاجتماعي المبكر.',
                'recommendations' => 'استمرار اللعب التبادلي اليومي وتقليل وقت الشاشة.',
                'follow_up_plan' => 'مراجعة متابعة بعد شهر لتحديد احتياج مرحلة جديدة.',
                'notes' => 'يمكن إعادة فتح برنامج جديد إذا ظهرت أهداف لغوية أعلى.',
            ]
        );
    }

    private function upsertArticulationAssessment(
        TherapyProgram $program,
        User $user,
        string $assessedAt,
        array $responses,
        string $title,
        ?string $notes = null
    ): void {
        $counts = collect($responses)->countBy('status');
        $total = count($responses);
        $correct = (int) $counts->get(ArticulationAssessment::STATUS_CORRECT, 0);

        ArticulationAssessment::updateOrCreate(
            [
                'therapy_program_id' => $program->id,
                'assessed_at' => $assessedAt,
                'title' => $title,
            ],
            [
                'assessed_by' => $user->id,
                'responses' => $responses,
                'total_items' => $total,
                'correct_count' => $correct,
                'substitution_count' => (int) $counts->get(ArticulationAssessment::STATUS_SUBSTITUTED, 0),
                'omission_count' => (int) $counts->get(ArticulationAssessment::STATUS_OMITTED, 0),
                'distortion_count' => (int) $counts->get(ArticulationAssessment::STATUS_DISTORTED, 0),
                'accuracy_percent' => $total > 0 ? round(($correct / $total) * 100, 2) : 0,
                'notes' => $notes,
            ]
        );
    }

    private function seedPackages(array $patients, array $sessions): void
    {
        $package = SessionPackage::updateOrCreate(
            ['patient_id' => $patients['PAT-DEMO-001']->id, 'name' => 'باقة 8 جلسات تخاطب'],
            [
                'total_sessions' => 8,
                'used_sessions' => 2,
                'price_paid' => 1600,
                'start_date' => now()->subWeeks(4)->toDateString(),
                'expiry_date' => now()->addMonths(2)->toDateString(),
                'status' => 'نشط',
            ]
        );

        foreach (['p1-s1', 'p1-s2'] as $sessionKey) {
            PackageUsage::updateOrCreate(
                ['session_package_id' => $package->id, 'therapy_session_id' => $sessions[$sessionKey]->id],
                ['used_at' => $sessions[$sessionKey]->session_date]
            );
        }

        SessionPackage::updateOrCreate(
            ['patient_id' => $patients['PAT-DEMO-002']->id, 'name' => 'باقة 4 جلسات نطق'],
            [
                'total_sessions' => 4,
                'used_sessions' => 4,
                'price_paid' => 720,
                'start_date' => now()->subMonths(2)->toDateString(),
                'expiry_date' => now()->subDays(3)->toDateString(),
                'status' => 'مستنفد',
            ]
        );
    }

    private function seedFinance(array $patients, array $programs): void
    {
        $invoice = Invoice::updateOrCreate(
            ['invoice_number' => 'INV-DEMO-001'],
            [
                'patient_id' => $patients['PAT-DEMO-001']->id,
                'issue_date' => now()->subDays(10)->toDateString(),
                'due_date' => now()->addDays(5)->toDateString(),
                'total' => 1600,
                'status' => 'مدفوعة جزئياً',
                'notes' => 'فاتورة باقة جلسات تخاطب تجريبية.',
            ]
        );
        $this->replaceInvoiceItems($invoice, [
            ['description' => 'باقة 8 جلسات تخاطب', 'quantity' => 1, 'unit_price' => 1600],
        ]);
        $this->replaceInvoicePayments($invoice, [
            ['amount' => 900, 'payment_date' => now()->subDays(8)->toDateString(), 'method' => 'cash', 'notes' => 'دفعة أولى.'],
        ]);

        $paidInvoice = Invoice::updateOrCreate(
            ['invoice_number' => 'INV-DEMO-002'],
            [
                'patient_id' => $patients['PAT-DEMO-002']->id,
                'issue_date' => now()->subDays(20)->toDateString(),
                'due_date' => now()->subDays(5)->toDateString(),
                'total' => 720,
                'status' => 'مدفوعة',
                'notes' => 'فاتورة مدفوعة بالكامل.',
            ]
        );
        $this->replaceInvoiceItems($paidInvoice, [
            ['description' => 'باقة 4 جلسات نطق', 'quantity' => 1, 'unit_price' => 720],
        ]);
        $this->replaceInvoicePayments($paidInvoice, [
            ['amount' => 720, 'payment_date' => now()->subDays(19)->toDateString(), 'method' => 'card', 'notes' => 'مدفوعة بالبطاقة.'],
        ]);

        $quotation = Quotation::updateOrCreate(
            ['quotation_number' => 'QUO-DEMO-001'],
            [
                'patient_id' => $patients['PAT-DEMO-003']->id,
                'issue_date' => now()->subDays(3)->toDateString(),
                'valid_until' => now()->addDays(11)->toDateString(),
                'total' => 1440,
                'status' => 'مسودة',
                'notes' => 'عرض سعر لبرنامج وضوح الكلام.',
            ]
        );
        $this->replaceQuotationItems($quotation, [
            ['description' => '8 جلسات نطق', 'quantity' => 8, 'unit_price' => 180],
        ]);

        ProgramPayment::updateOrCreate(
            ['therapy_program_id' => $programs['PAT-DEMO-001']->id, 'payment_date' => now()->subDays(8)->toDateString()],
            ['amount' => 900, 'method' => 'cash', 'notes' => 'دفعة مرتبطة بالبرنامج العلاجي.']
        );
    }

    private function replaceInvoiceItems(Invoice $invoice, array $items): void
    {
        $invoice->items()->delete();
        foreach ($items as $item) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $item['quantity'] * $item['unit_price'],
            ]);
        }
    }

    private function replaceInvoicePayments(Invoice $invoice, array $payments): void
    {
        $invoice->payments()->delete();
        foreach ($payments as $payment) {
            InvoicePayment::create([
                'invoice_id' => $invoice->id,
                'amount' => $payment['amount'],
                'payment_date' => $payment['payment_date'],
                'method' => $payment['method'],
                'notes' => $payment['notes'],
            ]);
        }
    }

    private function replaceQuotationItems(Quotation $quotation, array $items): void
    {
        $quotation->items()->delete();
        foreach ($items as $item) {
            QuotationItem::create([
                'quotation_id' => $quotation->id,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $item['quantity'] * $item['unit_price'],
            ]);
        }
    }

    private function seedHr(Therapist $saraTherapist, Therapist $omarTherapist, array $sessions): void
    {
        foreach ([$saraTherapist, $omarTherapist] as $therapist) {
            for ($i = 0; $i < 12; $i++) {
                $date = now()->startOfMonth()->addDays($i);
                Attendance::updateOrCreate(
                    ['therapist_id' => $therapist->id, 'attendance_date' => $date->toDateString()],
                    ['status' => $i === 5 ? 'إجازة' : 'حاضر', 'notes' => $i === 5 ? 'إجازة مجدولة' : null]
                );
            }
        }

        PayrollRecord::updateOrCreate(
            ['therapist_id' => $saraTherapist->id, 'month' => now()->month, 'year' => now()->year, 'description' => 'مكافأة التزام تجريبية'],
            ['type' => 'إضافة', 'amount' => 300]
        );
        PayrollRecord::updateOrCreate(
            ['therapist_id' => $omarTherapist->id, 'month' => now()->month, 'year' => now()->year, 'description' => 'خصم تأخير تجريبي'],
            ['type' => 'خصم', 'amount' => 150]
        );

        TherapistEarning::updateOrCreate(
            ['therapist_id' => $saraTherapist->id, 'therapy_session_id' => $sessions['p2-s1']->id],
            ['amount' => 72, 'type' => 'commission']
        );
        TherapistEarning::updateOrCreate(
            ['therapist_id' => $omarTherapist->id, 'therapy_session_id' => $sessions['p3-s1']->id],
            ['amount' => 180, 'type' => 'session_fee']
        );
    }

    private function seedProgramAssets(array $programs, $equipment): void
    {
        if ($equipment->has('بطاقات صور كلمات')) {
            ProgramEquipment::updateOrCreate(
                ['therapy_program_id' => $programs['PAT-DEMO-001']->id, 'equipment_id' => $equipment['بطاقات صور كلمات']->id],
                ['quantity' => 1, 'unit_price' => 250, 'notes' => 'مجموعة مستخدمة في تدريب المفردات.']
            );
        }

        if ($equipment->has('مرآة تدريب نطق')) {
            ProgramEquipment::updateOrCreate(
                ['therapy_program_id' => $programs['PAT-DEMO-002']->id, 'equipment_id' => $equipment['مرآة تدريب نطق']->id],
                ['quantity' => 1, 'unit_price' => 120, 'notes' => 'تدريب مخارج الحروف.']
            );
        }

        Storage::disk('public')->put('attachments/demo-progress-note.txt', 'Demo attachment for speech clinic review.');

        ProgramAttachment::updateOrCreate(
            ['therapy_program_id' => $programs['PAT-DEMO-001']->id, 'file_name' => 'demo-progress-note.txt'],
            [
                'file_path' => 'attachments/demo-progress-note.txt',
                'attachment_type' => 'مرفق_عام',
                'description' => 'مرفق تجريبي لمراجعة شاشة ملفات البرنامج.',
            ]
        );
    }

    private function seedSupplierPayments(array $suppliers): void
    {
        SupplierPayment::updateOrCreate(
            ['supplier_id' => $suppliers['شركة النور للمستلزمات']->id, 'payment_date' => now()->subDays(4)->toDateString()],
            ['amount' => 700, 'notes' => 'سداد جزئي تجريبي.']
        );
    }
}
