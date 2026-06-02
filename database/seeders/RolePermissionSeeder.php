<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

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
            'view appointments', 'create appointments', 'edit appointments', 'delete appointments',
            // البرامج والجلسات العلاجية
            'view therapy', 'create therapy', 'edit therapy', 'delete therapy',
            // التطور والواجبات
            'manage milestones', 'manage home tasks',
            // المالية (الفواتير والباقات وعروض الأسعار)
            'view finance', 'manage invoices', 'manage quotations', 'manage packages',
            // الكوادر والرواتب
            'view hr', 'manage therapists', 'manage payroll',
            // المخزن والموردون
            'manage inventory', 'manage suppliers',
            // التقارير والإعدادات
            'view reports', 'manage settings',
        ];

        // إنشاء الصلاحيات في قاعدة البيانات
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // ٢. تعريف الأدوار (Roles)
        $adminRole = Role::create(['name' => 'مدير النظام']);
        $financeRole = Role::create(['name' => 'مسؤول مالي']);
        $receptionRole = Role::create(['name' => 'موظف استقبال']);
        $therapistRole = Role::create(['name' => 'أخصائي تخاطب']);

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
        ]);

        // أخصائي تخاطب: جلساته فقط، تتبع التطور، الواجبات، ملاحظاته
        $therapistRole->givePermissionTo([
            'view patients',
            'view appointments', 'edit appointments',
            'view therapy', 'create therapy', 'edit therapy',
            'manage milestones', 'manage home tasks',
        ]);
    }
}
