<x-app-layout>
    <x-slot name="title">فاتورة رقم: {{ $invoice->invoice_number }}</x-slot>

    <div class="max-w-4xl mx-auto bg-white dark:bg-gray-800 p-6 rounded-lg shadow mb-6">
        <div class="flex justify-between items-start mb-8">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">فاتورة: {{ $invoice->invoice_number }}</h2>
                <p class="text-gray-500 dark:text-gray-400">المريض: {{ $invoice->patient->name }}</p>
            </div>
            <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg dark:bg-purple-500 dark:hover:bg-purple-600">تحميل PDF</a>
        </div>

        <!-- عناصر الفاتورة -->
        <table class="w-full text-right mb-6">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-2 text-sm text-gray-500 dark:text-gray-300">الوصف</th>
                    <th class="px-4 py-2 text-sm text-gray-500 dark:text-gray-300">الكمية</th>
                    <th class="px-4 py-2 text-sm text-gray-500 dark:text-gray-300">سعر الوحدة</th>
                    <th class="px-4 py-2 text-sm text-gray-500 dark:text-gray-300">الإجمالي</th>
                </tr>
            </thead>
            <tbody class="divide-y dark:divide-gray-700">
                @foreach($invoice->items as $item)
                <tr>
                    <td class="px-4 py-3 dark:text-gray-200">{{ $item->description }}</td>
                    <td class="px-4 py-3 dark:text-gray-200">{{ $item->quantity }}</td>
                    <td class="px-4 py-3 dark:text-gray-200">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="px-4 py-3 font-bold dark:text-white">{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 dark:border-gray-600">
                    <td colspan="3" class="px-4 py-3 text-left font-bold text-gray-800 dark:text-gray-200">الإجمالي النهائي</td>
                    <!-- التعديل هنا: استبدال ر.س بالمتغير الديناميكي -->
                    <td class="px-4 py-3 font-extrabold text-xl text-blue-600 dark:text-blue-400">{{ number_format($invoice->total, 2) }} {{ $settings->currency ?? 'ر.س' }}</td>
                </tr>
            </tfoot>
        </table>

        <!-- سجل الدفعات -->
        <div class="border-t dark:border-gray-700 pt-6">
            <h3 class="font-bold mb-4 text-gray-800 dark:text-gray-200">سجل الدفعات (المبلغ المدفوع: {{ number_format($invoice->payments->sum('amount'), 2) }} {{ $settings->currency ?? 'ر.س' }})</h3>
            @if($invoice->payments->count() > 0)
            <ul class="space-y-2 mb-4">
                @foreach($invoice->payments as $payment)
                <li class="flex justify-between bg-gray-50 dark:bg-gray-700 p-3 rounded">
                    <span class="dark:text-gray-300">{{ $payment->payment_date }} - {{ $payment->method }}</span>
                    <!-- التعديل هنا: استبدال ر.س بالمتغير الديناميكي -->
                    <span class="font-bold text-green-600 dark:text-green-400">{{ number_format($payment->amount, 2) }} {{ $settings->currency ?? 'ر.س' }}</span>
                </li>
                @endforeach
            </ul>
            @endif

            <!-- فورم إضافة دفعة جديدة -->
            @if($invoice->status != 'مدفوعة' && $invoice->status != 'ملغاة')
            <form action="{{ route('invoices.payments', $invoice) }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">المبلغ</label>
                    <input type="number" name="amount" step="0.01" required class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">طريقة الدفع</label>
                    <select name="method" class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200">
                        <option value="كاش">كاش</option>
                        <option value="تحويل">تحويل</option>
                        <option value="بطاقة">بطاقة</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">التاريخ</label>
                    <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200">
                </div>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg h-fit dark:bg-green-500 dark:hover:bg-green-600">تسجيل دفعة</button>
            </form>
            @endif
        </div>
    </div>
</x-app-layout>
