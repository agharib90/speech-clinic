<x-app-layout>
    <x-slot name="title">الموردون</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- فورم إضافة مورد جديد -->
        <div class="lg:col-span-1 bg-white dark:bg-gray-800 p-6 rounded-lg shadow h-fit">
            <h2 class="text-xl font-bold mb-4 text-gray-800 dark:text-gray-200">إضافة مورد جديد</h2>
            <form action="{{ route('suppliers.store') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">اسم المورد *</label>
                        <input type="text" name="name" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الهاتف</label>
                        <input type="text" name="phone" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">البريد الإلكتروني</label>
                        <input type="email" name="email" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">العنوان</label>
                        <input type="text" name="address" class="w-full border rounded-lg px-4 py-2 dark:bg-gray-700 dark:text-gray-200">
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg">حفظ المورد</button>
                </div>
            </form>
        </div>

        <!-- قائمة الموردين -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h2 class="text-xl font-bold mb-4 text-gray-800 dark:text-gray-200">سجل الموردين</h2>
            <table class="w-full text-right">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">الاسم</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">الهاتف</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">الرصيد المستحق</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-gray-700">
                    @foreach($suppliers as $supplier)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-6 py-4 font-bold dark:text-white">{{ $supplier->name }}</td>
                        <td class="px-6 py-4 dark:text-gray-300">{{ $supplier->phone }}</td>
                        <td class="px-6 py-4 text-red-600 font-bold">{{ number_format($supplier->balance, 2) }} ر.س</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>
</x-app-layout>
