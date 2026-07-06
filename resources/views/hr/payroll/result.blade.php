<x-app-layout>
    <x-slot name="title">كشف راتب: {{ $therapist->name }}</x-slot>

    <div class="max-w-4xl mx-auto space-y-6">

        <!-- ملخص الراتب -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h2 class="text-2xl font-bold mb-4 text-gray-800 dark:text-gray-200">كشف راتب: {{ $therapist->name }} ({{ $month }}/{{ $year }})</h2>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
                    <p class="text-sm text-blue-600 dark:text-blue-300">الراتب الأساسي</p>
                    <!-- إضافة العملة الديناميكية -->
                    <p class="text-xl font-bold text-blue-800 dark:text-blue-100">{{ number_format($baseSalary, 2) }} {{ $settings->currency ?? 'ر.س' }}</p>
                </div>
                <div class="bg-green-50 dark:bg-green-900 p-4 rounded-lg">
                    <p class="text-sm text-green-600 dark:text-green-300">عمولات الجلسات</p>
                    <!-- إضافة العملة الديناميكية -->
                    <p class="text-xl font-bold text-green-800 dark:text-green-100">{{ number_format($totalEarnings, 2) }} {{ $settings->currency ?? 'ر.س' }}</p>
                </div>
                <div class="bg-yellow-50 dark:bg-yellow-900 p-4 rounded-lg">
                    <p class="text-sm text-yellow-600 dark:text-yellow-300">بدلات إضافية</p>
                    <!-- إضافة العملة الديناميكية -->
                    <p class="text-xl font-bold text-yellow-800 dark:text-yellow-100">{{ number_format($additions, 2) }} {{ $settings->currency ?? 'ر.س' }}</p>
                </div>
                <div class="bg-red-50 dark:bg-red-900 p-4 rounded-lg">
                    <p class="text-sm text-red-600 dark:text-red-300">خصومات</p>
                    <!-- إضافة العملة الديناميكية -->
                    <p class="text-xl font-bold text-red-800 dark:text-red-100">{{ number_format($deductions, 2) }} {{ $settings->currency ?? 'ر.س' }}</p>
                </div>
            </div>

            <div class="mt-6 bg-gray-100 dark:bg-gray-700 p-4 rounded-lg text-left">
                <p class="text-gray-600 dark:text-gray-400 text-sm">صافي الراتب المستحق</p>
                <!-- التعديل الرئيسي هنا: استبدال ر.س بالمتغير الديناميكي -->
                <p class="text-4xl font-extrabold text-gray-900 dark:text-white">{{ number_format($netSalary, 2) }} <span class="text-lg">{{ $settings->currency ?? 'ر.س' }}</span></p>
            </div>
        </div>

        <!-- تفاصيل العمولات -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h3 class="font-bold mb-3 text-gray-700 dark:text-gray-300">تفاصيل الجلسات المحققة ({{ $earnings->count() }} جلسة)</h3>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-right">التاريخ</th>
                        <th class="px-4 py-2 text-right">النوع</th>
                        <th class="px-4 py-2 text-right">المبلغ</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-gray-700">
                    @foreach($earnings as $earning)
                    <tr>
                        <td class="px-4 py-2 dark:text-gray-300">{{ $earning->created_at->format('Y-m-d') }}</td>
                        <td class="px-4 py-2 dark:text-gray-300">{{ $earning->type }}</td>
                        <!-- إضافة العملة الديناميكية للمبلغ -->
                        <td class="px-4 py-2 font-bold dark:text-white">{{ number_format($earning->amount, 2) }} {{ $settings->currency ?? 'ر.س' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- إضافة بدل / خصم -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h3 class="font-bold mb-3 text-gray-700 dark:text-gray-300">إضافة بدل / خصم يدوي لهذا الشهر</h3>
            <form action="{{ route('payroll.addRecord') }}" method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                @csrf
                <input type="hidden" name="therapist_id" value="{{ $therapist->id }}">
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="year" value="{{ $year }}">

                <div>
                    <label class="block text-xs mb-1 dark:text-gray-400">النوع</label>
                    <select name="type" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200" required>
                        <option value="إضافة">إضافة (بدل)</option>
                        <option value="خصم">خصم</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs mb-1 dark:text-gray-400">المبلغ</label>
                    <input type="number" name="amount" step="0.01" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200" required>
                </div>
                <div class="col-span-2">
                    <label class="block text-xs mb-1 dark:text-gray-400">السبب</label>
                    <input type="text" name="description" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200" required>
                </div>
                <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white py-2 rounded-lg dark:bg-gray-600 dark:hover:bg-gray-700">إضافة</button>
            </form>
        </div>

    </div>
</x-app-layout>
