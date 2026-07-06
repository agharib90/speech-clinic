<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Therapist;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    // ── عرض جميع المستخدمين ───────────────────────────────────────────
    public function index()
    {
        $users = User::with('roles')->latest()->get();
        $roles = Role::all();
        return view('users.index', compact('users', 'roles'));
    }

    // ── فورم إنشاء مستخدم جديد ────────────────────────────────────────
    public function create()
    {
        $roles = Role::all();
        return view('users.create', compact('roles'));
    }

    // ── حفظ مستخدم جديد ───────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)],
            'role'     => 'required|exists:roles,name',
            // حقول الأخصائي — مطلوبة فقط لو الدور أخصائي
            'specialization'  => 'nullable|string',
            'phone'           => 'nullable|string',
            'license_number'  => 'nullable|string',
            'hire_date'       => 'nullable|date',
            'salary_type'     => 'nullable|in:monthly,daily,commission',
            'monthly_salary'  => 'nullable|numeric|min:0',
            'daily_salary'    => 'nullable|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        // ١. إنشاء المستخدم
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // ٢. تعيين الدور
        $user->assignRole($request->role);

        // ٣. لو الدور أخصائي تخاطب — أنشئ سجل Therapist وربطه بالـ User
        if ($request->role === 'أخصائي تخاطب') {
            Therapist::create([
                'user_id'         => $user->id,
                'name'            => $request->name,
                'specialization'  => $request->specialization,
                'phone'           => $request->phone,
                'email'           => $request->email,
                'license_number'  => $request->license_number,
                'hire_date'       => $request->hire_date,
                'salary_type'     => $request->salary_type ?? 'monthly',
                'monthly_salary'  => $request->monthly_salary ?? 0,
                'daily_salary'    => $request->daily_salary ?? 0,
                'commission_rate' => $request->commission_rate ?? 0,
                'is_active'       => true,
            ]);
        }

        return redirect()->route('users.index')
            ->with('success', "تم إنشاء حساب {$user->name} بنجاح");
    }

    // ── فورم تعديل مستخدم ─────────────────────────────────────────────
    public function edit(User $user)
    {
        $roles = Role::all();
        $therapist = Therapist::where('user_id', $user->id)->first();
        $currentRole = $user->roles->first()?->name;
        return view('users.edit', compact('user', 'roles', 'therapist', 'currentRole'));
    }

    // ── تحديث مستخدم ──────────────────────────────────────────────────
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => "required|email|unique:users,email,{$user->id}",
            'role'     => 'required|exists:roles,name',
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'specialization'  => 'nullable|string',
            'phone'           => 'nullable|string',
            'license_number'  => 'nullable|string',
            'hire_date'       => 'nullable|date',
            'salary_type'     => 'nullable|in:monthly,daily,commission',
            'monthly_salary'  => 'nullable|numeric|min:0',
            'daily_salary'    => 'nullable|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        // تحديث بيانات المستخدم
        $data = ['name' => $request->name, 'email' => $request->email];
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }
        $user->update($data);

        // تغيير الدور
        $user->syncRoles([$request->role]);

        // تحديث سجل الأخصائي لو موجود أو إنشاؤه
        if ($request->role === 'أخصائي تخاطب') {
            Therapist::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'name'            => $request->name,
                    'specialization'  => $request->specialization,
                    'phone'           => $request->phone,
                    'email'           => $request->email,
                    'license_number'  => $request->license_number,
                    'hire_date'       => $request->hire_date,
                    'salary_type'     => $request->salary_type ?? 'monthly',
                    'monthly_salary'  => $request->monthly_salary ?? 0,
                    'daily_salary'    => $request->daily_salary ?? 0,
                    'commission_rate' => $request->commission_rate ?? 0,
                ]
            );
        }

        return redirect()->route('users.index')
            ->with('success', "تم تحديث بيانات {$user->name} بنجاح");
    }

    // ── حذف مستخدم ────────────────────────────────────────────────────
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'لا يمكنك حذف حسابك الخاص!');
        }

        // تعطيل الأخصائي المرتبط بدل حذفه حتى لا تتأثر السجلات
        Therapist::where('user_id', $user->id)->update(['is_active' => false]);

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'تم حذف المستخدم بنجاح');
    }

    // ── تبديل حالة الحساب (تفعيل/تعطيل) ─────────────────────────────
    public function toggleActive(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'لا يمكنك تعطيل حسابك الخاص!');
        }

        // نستخدم حقل email_verified_at كمؤشر للتفعيل، أو نضيف is_active
        // هنا نعطّل/نفعّل الأخصائي المرتبط
        $therapist = Therapist::where('user_id', $user->id)->first();
        if ($therapist) {
            $therapist->update(['is_active' => !$therapist->is_active]);
        }

        return back()->with('success', 'تم تحديث حالة الحساب');
    }

    public function resetPassword(User $user)
    {
        $temporaryPassword = 'Temp-' . Str::random(10);

        DB::transaction(function () use ($user, $temporaryPassword) {
            $user->forceFill([
                'password' => Hash::make($temporaryPassword),
                'email_verified_at' => now(),
            ])->save();

            Therapist::where('user_id', $user->id)->update(['is_active' => true]);
        });

        return back()
            ->with('success', "تم إعادة ضبط كلمة مرور {$user->name} بنجاح")
            ->with('temporary_password', $temporaryPassword)
            ->with('reset_user_email', $user->email);
    }
}
