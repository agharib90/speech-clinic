<x-app-layout>
    <x-slot name="title">إعدادات النظام</x-slot>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">{{ session('success') }}</div>
    @endif

    <div class="max-w-2xl mx-auto bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
        <h2 class="text-2xl font-bold mb-6 text-gray-800 dark:text-gray-200">إعدادات النظام</h2>

        <form action="{{ route('settings.update') }}" method="POST">

            @csrf
            @method('PUT')

            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">اسم العيادة / المركز</label>
                    <input type="text" name="clinic_name" value="{{ $settings->clinic_name }}" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">العملة المتعارف عليها</label>
                    <input type="text" name="currency" value="{{ $settings->currency }}" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">مثال: ر.س ، ج.م ، د.إ</p>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">حفظ الإعدادات</button>
            </div>
        </form>
    </div>
</x-app-layout>
