<x-app-layout>
    <x-slot name="title">إضافة أخصائي جديد</x-slot>

    <div class="max-w-2xl mx-auto bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
        <h2 class="text-2xl font-bold mb-6 text-gray-800 dark:text-gray-200">إضافة أخصائي جديد</h2>

        <form action="{{ route('therapists.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الاسم الكامل *</label>
                    <input type="text" name="name" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">التخصص</label>
                    <input type="text" name="specialization" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">نظام الراتب *</label>
                    <select name="salary_type" id="salary_type" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200" required>
                        <option value="monthly">راتب شهري</option>
                        <option value="daily">راتب يومي</option>
                        <option value="commission">عمولة</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الهاتف</label>
                    <input type="text" name="phone" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الراتب الشهري</label>
                    <input type="number" name="monthly_salary" step="0.01" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الراتب اليومي</label>
                    <input type="number" name="daily_salary" step="0.01" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">نسبة العمولة (%)</label>
                    <input type="number" name="commission_rate" step="0.01" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200">
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <a href="{{ route('therapists.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg ml-3">إلغاء</a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">حفظ</button>
            </div>
        </form>
    </div>
</x-app-layout>
