<x-app-layout>
    <x-slot name="title">تعديل بيانات المريض: {{ $patient->name }}</x-slot>

    <div class="mb-6 flex justify-between items-center">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">تعديل بيانات المريض: {{ $patient->name }}</h2>
        <a href="{{ route('patients.index') }}" class="text-blue-600 hover:underline dark:text-blue-400">العودة للقائمة</a>
    </div>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
        <form action="{{ route('patients.update', $patient->id) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- حاوية Alpine.js لحساب العمر -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6" x-data="{
                birthDate: '{{ old('birth_date', $patient->birth_date?->format('Y-m-d')) }}',
                getAge() {
                    if (!this.birthDate) return '';
                    let d = new Date(this.birthDate);
                    let today = new Date();
                    let y = today.getFullYear() - d.getFullYear();
                    let m = today.getMonth() - d.getMonth();
                    if (m < 0 || (m === 0 && today.getDate() < d.getDate())) { y--; m += 12; }
                    if (y === 0 && m === 0) return 'أقل من شهر';
                    if (y === 0) return m + ' شهر';
                    if (m === 0) return y + ' سنة';
                    return y + ' سنة و ' + m + ' شهر';
                }
            }">

                <!-- اسم المريض -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">اسم المريض</label>
                    <input type="text" name="name" value="{{ old('name', $patient->name) }}"
                           class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600" required>
                </div>

                <!-- ولي الأمر -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ولي الأمر</label>
                    <select name="guardian_id"
                            class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600" required>
                        <option value="">-- اختر ولي الأمر --</option>
                        @foreach($guardians as $id => $guardianName)
                            <option value="{{ $id }}" {{ old('guardian_id', $patient->guardian_id) == $id ? 'selected' : '' }}>
                                {{ $guardianName }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- تاريخ الميلاد (مربوط بـ Alpine) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">تاريخ الميلاد</label>
                    <input type="date" name="birth_date" x-model="birthDate"
                           class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600" required>
                </div>

                <!-- العمر (محسوب تلقائياً عبر Alpine) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">العمر الحالي</label>
                    <input type="text" :value="getAge()"
                           class="w-full border rounded px-3 py-2 bg-gray-100 dark:bg-gray-600 dark:text-gray-400 cursor-not-allowed" readonly disabled>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">يتم حسابه تلقائياً من تاريخ الميلاد</p>
                </div>

                <!-- الجنس -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الجنس</label>
                    <select name="gender" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
                        <option value="male" {{ old('gender', $patient->gender) == 'male' ? 'selected' : '' }}>ذكر</option>
                        <option value="female" {{ old('gender', $patient->gender) == 'female' ? 'selected' : '' }}>أنثى</option>
                    </select>
                </div>

                <!-- مصدر الإحالة -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">مصدر الإحالة</label>
                    <input type="text" name="referral_source" value="{{ old('referral_source', $patient->referral_source) }}"
                           class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
                </div>

                <!-- التشخيص -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">التشخيص</label>
                    <textarea name="diagnosis" rows="3" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">{{ old('diagnosis', $patient->diagnosis) }}</textarea>
                </div>

                <!-- حالة المريض (نشط/غير نشط) -->
                <div class="flex items-center mt-6">
                    <input type="checkbox" name="is_active" value="1" id="is_active"
                           {{ old('is_active', $patient->is_active) ? 'checked' : '' }}
                           class="w-4 h-4 text-blue-600 dark:bg-gray-700 dark:border-gray-600">
                    <label for="is_active" class="mr-2 text-sm font-medium text-gray-700 dark:text-gray-300">مريض نشط</label>
                </div>

                <!-- ملاحظات -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ملاحظات</label>
                    <textarea name="notes" rows="3" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">{{ old('notes', $patient->notes) }}</textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600">
                    حفظ التعديلات
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
