<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ١. تعريف الصلاحيات (Permissions) لكل وحدة
        $permissions = [
            // المرضى وأولياء الأمور
            'view patients', 'create patients', 'edit patients', 'delete patients',
            // الحضور والانصراف
            'manage checkins', 'view checkins',
            // المواعيد
            'view appointments', 'create appointments', 'create legacy appointments', 'edit appointments', 'delete appointments',
            // البرامج والجلسات العلاجية
            'view therapy', 'create therapy', 'edit therapy', 'delete therapy',
            'manage clinical evaluations',
            'manage clinical evaluation assignments',
            // التطور والواجبات
            'manage milestones', 'manage home tasks',
            // المالية (الفواتير والباقات وعروض الأسعار)
            'view finance', 'manage invoices', 'manage quotations', 'manage packages',
            // الكوادر والرواتب
            'view hr', 'manage therapists', 'manage payroll',
            // التخصصات والخدمات واستحقاقات الأخصائيين
            'manage specialties', 'manage services', 'manage therapist services',
            // خطط خدمات المرضى وخصومات العملاء
            'manage patient service plans', 'manage patient discounts',
            // المخزن والموردون
            'manage inventory', 'manage suppliers',
            // التقارير والإعدادات
            'view reports', 'manage settings',
        ];

        // إنشاء الصلاحيات في قاعدة البيانات
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ٢. تعريف الأدوار (Roles)
        $adminRole = Role::firstOrCreate(['name' => 'مدير النظام', 'guard_name' => 'web']);
        $financeRole = Role::firstOrCreate(['name' => 'مسؤول مالي', 'guard_name' => 'web']);
        $receptionRole = Role::firstOrCreate(['name' => 'موظف استقبال', 'guard_name' => 'web']);
        $therapistRole = Role::firstOrCreate(['name' => 'أخصائي تخاطب', 'guard_name' => 'web']);

        // ٣. توزيع الصلاحيات على الأدوار

        // المدير: صلاحيات كاملة
        $adminRole->givePermissionTo(Permission::all());

        // مسؤول مالي: فواتير، عروض أسعار، موردون، رواتب، تقارير مالية
        $financeRole->givePermissionTo([
            'view patients',
            'view appointments',
            'view finance', 'manage invoices', 'manage quotations', 'manage packages',
            'view hr', 'manage therapists', 'manage payroll',
            'manage suppliers',
            'view reports',
        ]);

        // موظف استقبال: الحضور بالباركود، المواعيد، إنشاء ملفات مرضى
        $receptionRole->givePermissionTo([
            'view patients', 'create patients', 'edit patients',
            'manage checkins', 'view checkins',
            'view appointments', 'create appointments', 'edit appointments',
            'manage clinical evaluation assignments',
        ]);

        // أخصائي تخاطب: جلساته فقط، تتبع التطور، الواجبات، ملاحظاته
        $therapistRole->givePermissionTo([
            'view patients',
            'view appointments', 'edit appointments',
            'view therapy', 'create therapy', 'edit therapy',
            'manage clinical evaluations',
            'manage milestones', 'manage home tasks',
        ]);
    }
}
