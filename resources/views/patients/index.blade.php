<x-app-layout>
    <x-slot name="title">سجل المرضى</x-slot>

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">سجل المرضى</h2>
        <a href="{{ route('patients.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">تسجيل طفل جديد</a>
    </div>

    <!-- محرك البحث -->
    <form action="{{ route('patients.index') }}" method="GET" class="mb-4">
        <div class="flex">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="بحث باسم الطفل أو الباركود أو ولي الأمر..." class="flex-1 border rounded-r-lg px-4 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
            <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-6 rounded-l-lg">بحث</button>
        </div>
    </form>

    <!-- الجدول -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
        <table class="w-full text-right">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">اسم الطفل</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ولي الأمر</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">العمر</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">التشخيص</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">الحالة</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($patients as $patient)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $patient->name }}</td>
                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $patient->guardian->name ?? 'غير محدد' }}</td>
                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $patient->birth_date->age }} سنة</td>
                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $patient->diagnosis }}</td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $patient->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                            {{ $patient->is_active ? 'نشط' : 'غير نشط' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 space-x-reverse space-x-2">
                        <a href="{{ route('patients.show', $patient) }}" class="text-blue-600 hover:underline">عرض</a>
                        <a href="{{ route('patients.edit', $patient) }}" class="text-yellow-600 hover:underline">تعديل</a>
                        <a href="{{ route('patients.print-card', $patient) }}" target="_blank" class="text-green-600 hover:underline">بطاقة</a>
                        <form action="{{ route('patients.destroy', $patient) }}" method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا المريض؟');">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">حذف</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- الترقيم -->
    <div class="mt-4">
        {{ $patients->links() }}
    </div>
</x-app-layout>
