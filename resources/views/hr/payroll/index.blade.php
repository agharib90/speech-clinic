<x-app-layout>
    <x-slot name="title">حساب الرواتب الشهرية</x-slot>

    <div class="max-w-2xl mx-auto bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
        <h2 class="text-2xl font-bold mb-6 text-gray-800 dark:text-gray-200">احسب راتب أخصائي</h2>

        <form action="{{ route('payroll.calculate') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الأخصائي</label>
                    <select name="therapist_id" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200" required>
                        <option value="">اختر الأخصائي...</option>
                        @foreach($therapists as $therapist)
                            <option value="{{ $therapist->id }}">{{ $therapist->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الشهر</label>
                    <input type="number" name="month" min="1" max="12" value="{{ date('n') }}" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">السنة</label>
                    <input type="number" name="year" value="{{ date('Y') }}" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200" required>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">عرض تفاصيل الراتب</button>
            </div>
        </form>
    </div>
</x-app-layout>
