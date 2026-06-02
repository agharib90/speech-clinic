<x-app-layout>
    <x-slot name="title">تعديل بيانات ولي الأمر</x-slot>

    <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
        <h2 class="text-2xl font-bold mb-6 text-gray-800 dark:text-gray-200">تعديل بيانات: {{ $guardian->name }}</h2>

        <form action="{{ route('guardians.update', $guardian) }}" method="POST">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الاسم الكامل *</label>
                    <input type="text" name="name" value="{{ old('name', $guardian->name) }}" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">رقم الهاتف *</label>
                    <input type="text" name="phone" value="{{ old('phone', $guardian->phone) }}" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">رقم هاتف بديل</label>
                    <input type="text" name="phone2" value="{{ old('phone2', $guardian->phone2) }}" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">البريد الإلكتروني</label>
                    <input type="email" name="email" value="{{ old('email', $guardian->email) }}" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الرقم الوطني</label>
                    <input type="text" name="national_id" value="{{ old('national_id', $guardian->national_id) }}" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">العنوان</label>
                    <input type="text" name="address" value="{{ old('address', $guardian->address) }}" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ملاحظات</label>
                    <textarea name="notes" rows="3" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">{{ old('notes', $guardian->notes) }}</textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <a href="{{ route('guardians.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg ml-3">إلغاء</a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">تحديث</button>
            </div>
        </form>
    </div>
</x-app-layout>
