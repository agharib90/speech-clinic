<x-app-layout>
    <x-slot name="title">المستلزمات والمخزون</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- فورم إضافة مستلزم جديد -->
        <div class="lg:col-span-1 bg-white dark:bg-gray-800 p-6 rounded-lg shadow h-fit">
            <h2 class="text-xl font-bold mb-4 text-gray-800 dark:text-gray-200">إضافة مستلزم جديد</h2>
            <form action="{{ route('equipment.store') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">اسم المستلزم *</label>
                        <input type="text" name="name" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الفئة</label>
                        <input type="text" name="category" placeholder="مثال: ألعاب تعليمية" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الكمية *</label>
                            <input type="number" name="quantity" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الوحدة</label>
                            <input type="text" name="unit" value="قطعة" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">حد التنبيه (أقل من كده يعطيك إنذار)</label>
                        <input type="number" name="reorder_level" value="5" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">المورد</label>
                        <select name="supplier_id" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200">
                            <option value="">-- اختياري --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg">حفظ المستلزم</button>
                </div>
            </form>
        </div>

        <!-- قائمة المخزون -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 p-6 rounded-lg shadow">

            <!-- تنبيهات النقص -->
            @php $lowStock = $equipment->filter(fn($item) => $item->quantity <= $item->reorder_level); @endphp

            @if($lowStock->count() > 0)
                <div class="bg-red-50 dark:bg-red-900 border-r-4 border-red-500 p-4 mb-6 rounded">
                    <h3 class="text-red-800 dark:text-red-200 font-bold mb-2">⚠️ تنبيه نقص مخزون ({{ $lowStock->count() }} أصناف)</h3>
                    <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-300">
                        @foreach($lowStock as $lowItem)
                            <li>{{ $lowItem->name }} (المتبقي: {{ $lowItem->quantity }})</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <h2 class="text-xl font-bold mb-4 text-gray-800 dark:text-gray-200">سجل المخزون</h2>
            <table class="w-full text-right">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">الصنف</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">الفئة</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">الكمية</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">الحالة</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-gray-700">
                    @foreach($equipment as $item)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-6 py-4 font-bold dark:text-white">{{ $item->name }}</td>
                        <td class="px-6 py-4 dark:text-gray-300">{{ $item->category }}</td>
                        <td class="px-6 py-4 dark:text-gray-300">{{ $item->quantity }} {{ $item->unit }}</td>
                        <td class="px-6 py-4">
                            @if($item->quantity <= $item->reorder_level)
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">نقص!</span>
                            @else
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">متوفر</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>
</x-app-layout>
