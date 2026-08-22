<x-app-layout>
    <x-slot name="title">أولياء الأمور</x-slot>

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">أولياء الأمور</h2>
        <a href="{{ route('guardians.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">إضافة ولي أمر جديد</a>
    </div>

    <!-- محرك البحث -->
    <form action="{{ route('guardians.index') }}" method="GET" class="mb-4">
        <div class="flex">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="بحث بالاسم أو الهاتف..." class="flex-1 border rounded-r-lg px-4 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
            <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-6 rounded-l-lg">بحث</button>
        </div>
    </form>

    <!-- الجدول -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
        <table class="w-full text-right">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">الاسم</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">الهاتف</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">الرقم الوطني</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">الأبناء</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($guardians as $guardian)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white"><a href="{{ route('guardians.show', $guardian) }}" class="clinic-entity-link">{{ $guardian->name }}</a></td>
                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $guardian->phone }}</td>
                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $guardian->national_id }}</td>
                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $guardian->patients->count() }}</td>
                    <td class="px-6 py-4 space-x-reverse space-x-2">
                        <a href="{{ route('guardians.edit', $guardian) }}" class="text-yellow-600 hover:underline">تعديل</a>
                        <form action="{{ route('guardians.destroy', $guardian) }}" method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من الحذف؟');">
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
        {{ $guardians->links() }}
    </div>
</x-app-layout>
