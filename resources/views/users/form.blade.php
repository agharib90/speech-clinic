{{--
    هذا الـ partial مشترك بين صفحتي الإنشاء والتعديل
    يُستدعى من create.blade.php و edit.blade.php
    المتغيرات: $user (nullable), $roles, $therapist (nullable), $currentRole (nullable)
--}}

<x-app-layout>
    <x-slot name="title">{{ isset($user) ? 'تعديل: ' . $user->name : 'مستخدم جديد' }}</x-slot>

    @php
        $isEdit  = isset($user);
        $action  = $isEdit ? route('users.update', $user) : route('users.store');
        $method  = $isEdit ? 'PUT' : 'POST';
        $oldRole = old('role', $currentRole ?? '');
    @endphp

    <div class="max-w-2xl mx-auto">

        {{-- Breadcrumb --}}
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6">
            <a href="{{ route('users.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">المستخدمون</a>
            <span>/</span>
            <span class="text-gray-900 dark:text-white">{{ $isEdit ? 'تعديل' : 'إضافة جديد' }}</span>
        </div>

        <form action="{{ $action }}" method="POST" x-data="userForm('{{ $oldRole }}')" x-init="init()">
            @csrf
            @if($isEdit) @method('PUT') @endif

            {{-- ─── بيانات الحساب ─────────────────────────────────────────── --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-6 mb-5">
                <h3 class="font-bold text-gray-900 dark:text-white mb-5 pb-3 border-b border-gray-100 dark:border-gray-700">
                    بيانات الحساب
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    {{-- الاسم --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            الاسم الكامل <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name"
                               value="{{ old('name', $user?->name) }}"
                               class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2.5 text-sm dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                               required>
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- البريد --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            البريد الإلكتروني <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email"
                               value="{{ old('email', $user?->email) }}"
                               class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2.5 text-sm dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                               required>
                        @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- الدور --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            الدور / الصلاحية <span class="text-red-500">*</span>
                        </label>
                        <select name="role" x-model="role"
                                class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2.5 text-sm dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 transition"
                                required>
                            <option value="">اختر الدور...</option>
                            @foreach($roles as $r)
                                <option value="{{ $r->name }}"
                                    {{ $oldRole === $r->name ? 'selected' : '' }}>
                                    {{ $r->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('role') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- كلمة المرور --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            كلمة المرور
                            @if($isEdit) <span class="text-gray-400 font-normal">(اتركها فارغة للإبقاء على الحالية)</span> @else <span class="text-red-500">*</span> @endif
                        </label>
                        <input type="password" name="password"
                               class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2.5 text-sm dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 transition"
                               {{ $isEdit ? '' : 'required' }}>
                        @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- تأكيد كلمة المرور --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            تأكيد كلمة المرور
                        </label>
                        <input type="password" name="password_confirmation"
                               class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2.5 text-sm dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 transition">
                    </div>

                </div>
            </div>

            {{-- ─── بيانات الأخصائي (تظهر فقط لو الدور أخصائي تخاطب) ────── --}}
            <div x-show="role === 'أخصائي تخاطب'" x-transition
                 class="bg-white dark:bg-gray-800 rounded-xl border border-teal-200 dark:border-teal-800 p-6 mb-5">

                <h3 class="font-bold text-gray-900 dark:text-white mb-1 pb-3 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                    بيانات الأخصائي الإضافية
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-5">هذه البيانات تُضاف تلقائياً لسجل الأخصائيين</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">التخصص</label>
                        <input type="text" name="specialization"
                               value="{{ old('specialization', $therapist?->specialization) }}"
                               placeholder="مثال: اضطرابات الكلام، اللغة"
                               class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2.5 text-sm dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-teal-500 transition">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">الهاتف</label>
                        <input type="text" name="phone"
                               value="{{ old('phone', $therapist?->phone) }}"
                               class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2.5 text-sm dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-teal-500 transition">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">رقم الترخيص المهني</label>
                        <input type="text" name="license_number"
                               value="{{ old('license_number', $therapist?->license_number) }}"
                               class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2.5 text-sm dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-teal-500 transition">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">تاريخ التعيين</label>
                        <input type="date" name="hire_date"
                               value="{{ old('hire_date', $therapist?->hire_date?->format('Y-m-d')) }}"
                               class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2.5 text-sm dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-teal-500 transition">
                    </div>

                    {{-- نظام الراتب --}}
                    <div x-data="{ salaryType: '{{ old('salary_type', $therapist?->salary_type ?? 'monthly') }}' }">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">نظام الراتب</label>
                        <select name="salary_type" x-model="salaryType"
                                class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2.5 text-sm dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-teal-500 transition">
                            <option value="monthly">راتب شهري ثابت</option>
                            <option value="daily">راتب يومي</option>
                            <option value="commission">عمولة على الجلسات</option>
                        </select>
                    </div>

                    {{-- الراتب الشهري --}}
                    <div x-show="salaryType === 'monthly'" x-data="{ salaryType: '{{ old('salary_type', $therapist?->salary_type ?? 'monthly') }}' }">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">الراتب الشهري (ج.م)</label>
                        <input type="number" name="monthly_salary" min="0" step="0.01"
                               value="{{ old('monthly_salary', $therapist?->monthly_salary) }}"
                               class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2.5 text-sm dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-teal-500 transition">
                    </div>

                    <div x-show="salaryType === 'daily'" x-data="{ salaryType: '{{ old('salary_type', $therapist?->salary_type ?? 'monthly') }}' }">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">الراتب اليومي (ج.م)</label>
                        <input type="number" name="daily_salary" min="0" step="0.01"
                               value="{{ old('daily_salary', $therapist?->daily_salary) }}"
                               class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2.5 text-sm dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-teal-500 transition">
                    </div>

                    <div x-show="salaryType === 'commission'" x-data="{ salaryType: '{{ old('salary_type', $therapist?->salary_type ?? 'monthly') }}' }">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">نسبة العمولة (%)</label>
                        <input type="number" name="commission_rate" min="0" max="100" step="0.5"
                               value="{{ old('commission_rate', $therapist?->commission_rate) }}"
                               class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2.5 text-sm dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-teal-500 transition">
                    </div>

                </div>
            </div>

            {{-- ─── أزرار الحفظ ─────────────────────────────────────────────── --}}
            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('users.index') }}"
                   class="px-5 py-2.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    إلغاء
                </a>
                <button type="submit"
                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                    {{ $isEdit ? 'حفظ التعديلات' : 'إنشاء الحساب' }}
                </button>
            </div>

        </form>
    </div>

    {{-- Alpine.js component --}}
    <script>
    function userForm(initialRole) {
        return {
            role: initialRole,
            init() {
                // تأكد إن الـ salaryType في كل x-data بيتزامن عند تغيير الدور
            }
        }
    }
    </script>

</x-app-layout>
