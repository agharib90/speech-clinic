<x-app-layout>
    <x-slot name="title">تسجيل طفل جديد</x-slot>

    <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
        <h2 class="text-2xl font-bold mb-6 text-gray-800 dark:text-gray-200">تسجيل طفل جديد</h2>

        <form action="{{ route('patients.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- اختيار ولي الأمر -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ولي الأمر *</label>
                    <select name="guardian_id" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 @error('guardian_id') border-red-500 @enderror">
                        <option value="">-- اختر ولي الأمر --</option>
                        @foreach ($guardians as $id => $name)
                            <option value="{{ $id }}" {{ old('guardian_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('guardian_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">اسم الطفل *</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                    @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">تاريخ الميلاد *</label>
                    <input type="date" name="birth_date" value="{{ old('birth_date') }}" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الجنس *</label>
                    <select name="gender" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                        <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>ذكر</option>
                        <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>أنثى</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">التشخيص</label>
                    <input type="text" name="diagnosis" value="{{ old('diagnosis') }}" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">مصدر الإحالة</label>
                    <input type="text" name="referral_source" value="{{ old('referral_source') }}" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                </div>

                <div class="col-span-2 flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1" checked class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                    <label for="is_active" class="mr-2 text-sm font-medium text-gray-700 dark:text-gray-300">حالة الطفل نشط</label>
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ملاحظات</label>
                    <textarea name="notes" rows="3" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <a href="{{ route('patients.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg ml-3">إلغاء</a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">حفظ وتوليد الباركود</button>
            </div>
        </form>
    </div>
</x-app-layout>
