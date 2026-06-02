<x-app-layout>
    <x-slot name="title">باقات الجلسات</x-slot>

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">باقات الجلسات المدفوعة</h2>
        <a href="{{ route('packages.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">بيع باقة جديدة</a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
        <table class="w-full text-right">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">المريض</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">اسم الباقة</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">الاستخدام</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">الحالة</th>
                </tr>
            </thead>
            <tbody class="divide-y dark:divide-gray-700">
                @foreach($packages as $package)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td class="px-6 py-4 dark:text-white">{{ $package->patient->name }}</td>
                    <td class="px-6 py-4 dark:text-gray-300">{{ $package->name }}</td>
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-reverse space-x-2">
                            <span class="text-sm font-bold dark:text-white">{{ $package->used_sessions }} / {{ $package->total_sessions }}</span>
                            <div class="w-24 bg-gray-200 dark:bg-gray-600 rounded-full h-2.5">
                                <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ ($package->used_sessions / $package->total_sessions) * 100 }}%"></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        @if($package->status == 'نشط')
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">نشط</span>
                        @else
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">{{ $package->status }}</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
