<x-app-layout>
    <x-slot name="title">الفواتير</x-slot>

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">سجل الفواتير</h2>
        <a href="{{ route('invoices.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg dark:bg-blue-500 dark:hover:bg-blue-600">إنشاء فاتورة جديدة</a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
        <table class="w-full text-right">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">رقم الفاتورة</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">المريض</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">الإجمالي</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">الحالة</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">التاريخ</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($invoices as $invoice)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td class="px-6 py-4 font-mono text-sm text-gray-900 dark:text-white">{{ $invoice->invoice_number }}</td>
                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $invoice->patient->name ?? 'محذوف' }}</td>

                    <!-- التعديل هنا: استبدال ر.س بالمتغير الديناميكي -->
                    <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">{{ number_format($invoice->total, 2) }} {{ $settings->currency ?? 'ر.س' }}</td>

                    <td class="px-6 py-4">
                        @if($invoice->status == 'مدفوعة')
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">مدفوعة</span>
                        @elseif($invoice->status == 'مدفوعة جزئياً')
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">مدفوعة جزئياً</span>
                        @else
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">غير مدفوعة</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $invoice->issue_date }}</td>
                    <td class="px-6 py-4 space-x-reverse space-x-2">
                        <a href="{{ route('invoices.show', $invoice) }}" class="text-blue-600 hover:underline dark:text-blue-400">عرض</a>
                        <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="text-purple-600 hover:underline dark:text-purple-400">PDF</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $invoices->links() }}
    </div>
</x-app-layout>
