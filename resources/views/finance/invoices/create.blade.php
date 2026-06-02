<x-app-layout>
    <x-slot name="title">إنشاء فاتورة جديدة</x-slot>

    <div class="max-w-4xl mx-auto bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
        <h2 class="text-2xl font-bold mb-6 text-gray-800 dark:text-gray-200">إنشاء فاتورة جديدة</h2>
                <!-- كود عرض الأخطاء اللي هنضيفه -->
        @if ($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                <h3 class="text-red-800 font-bold mb-2">يوجد أخطاء في البيانات:</h3>
                <ul class="list-disc list-inside text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <!-- نهاية كود الأخطاء -->

        <form action="{{ route('invoices.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">المريض *</label>
                    <select name="patient_id" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200" required>
                        <option value="">اختر المريض</option>
                        @foreach($patients as $patient)
                            <option value="{{ $patient->id }}">{{ $patient->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">تاريخ الإصدار *</label>
                    <input type="date" name="issue_date" value="{{ date('Y-m-d') }}" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">تاريخ الاستحقاق</label>
                    <input type="date" name="due_date" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200">
                </div>
            </div>

            <!-- عناصر الفاتورة (ديناميكية بـ Alpine.js) -->
            <div x-data="{ items: [{description: '', quantity: 1, unit_price: 0}] }">
                <h3 class="font-bold mb-2 text-gray-700 dark:text-gray-300">عناصر الفاتورة</h3>
                <table class="w-full mb-4 text-sm">
                    <thead>
                        <tr class="text-gray-500 dark:text-gray-400 border-b">
                            <th class="py-2 text-right">الوصف</th>
                            <th class="py-2 text-right w-20">الكمية</th>
                            <th class="py-2 text-right w-32">سعر الوحدة</th>
                            <th class="py-2 text-right w-32">الإجمالي</th>
                            <th class="py-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="border-b dark:border-gray-700">
                <td class="py-2"><input type="text" x-model="item.description" :name="'items['+index+'][description]'" class="w-full border rounded px-2 py-1 dark:bg-gray-700 dark:text-gray-200" required></td>
                <td class="py-2"><input type="number" x-model.number="item.quantity" :name="'items['+index+'][quantity]'" min="1" class="w-full border rounded px-2 py-1 dark:bg-gray-700 dark:text-gray-200" required></td>
                <td class="py-2"><input type="number" x-model.number="item.unit_price" :name="'items['+index+'][unit_price]'" step="0.01" min="0" class="w-full border rounded px-2 py-1 dark:bg-gray-700 dark:text-gray-200" required></td>
                <td class="py-2 font-bold dark:text-white" x-text="(item.quantity * item.unit_price).toFixed(2)"></td>
                <td class="py-2">
                    <button type="button" @click="items.splice(index, 1)" class="text-red-500 font-bold" x-show="index > 0">X</button>
                </td>
            </tr>

                        </template>
                    </tbody>
                </table>
                <button type="button" @click="items.push({description: '', quantity: 1, unit_price: 0})" class="text-blue-600 hover:underline text-sm font-bold">+ إضافة سطر جديد</button>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ملاحظات</label>
                <textarea name="notes" rows="2" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200"></textarea>
            </div>

            <div class="mt-6 flex justify-end">
                <a href="{{ route('invoices.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg ml-3">إلغاء</a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">حفظ الفاتورة</button>
            </div>
        </form>
    </div>
</x-app-layout>
