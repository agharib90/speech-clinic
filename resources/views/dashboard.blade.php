<x-app-layout>
    <x-slot name="title">لوحة التحكم</x-slot>

    <!-- بطاقات المؤشرات العلوية -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

        <!-- مواعيد اليوم -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow flex items-center space-x-reverse space-x-4">
            <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">مواعيد اليوم</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $todayAppointments }}</p>
            </div>
        </div>

        <!-- من في العيادة الآن -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow flex items-center space-x-reverse space-x-4">
            <div class="p-3 rounded-full bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-300">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">حالات داخل العيادة</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $currentCheckins }}</p>
            </div>
        </div>

        <!-- إيرادات الشهر -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow flex items-center space-x-reverse space-x-4">
            <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900 text-yellow-600 dark:text-yellow-300">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">إيرادات الشهر</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $settings->currency ?? 'ر.س' }}</p>
            </div>
        </div>

        <!-- فواتير غير مدفوعة -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow flex items-center space-x-reverse space-x-4">
            <div class="p-3 rounded-full bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-300">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">فواتير معلقة</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $unpaidInvoices }}</p>
            </div>
        </div>

    </div>

    <!-- جدول المواعيد القادمة اليوم -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">مواعيد اليوم القادمة</h2>

        @if($upcomingAppointments->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-right">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase">الوقت</th>
                            <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase">المريض</th>
                            <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase">الأخصائي</th>
                            <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase">نوع الجلسة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-gray-700">
                        @foreach($upcomingAppointments as $app)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-4 py-3 font-bold dark:text-white">{{ \Carbon\Carbon::parse($app->scheduled_at)->format('h:i A') }}</td>
                            <td class="px-4 py-3 dark:text-gray-300">{{ $app->patient->name }}</td>
                            <td class="px-4 py-3 dark:text-gray-300">{{ $app->therapist->name }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs" style="background-color: {{ $app->sessionType->color }}20; color: {{ $app->sessionType->color }}">
                                    {{ $app->sessionType->name }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500 dark:text-gray-400 text-center py-6">لا توجد مواعيد مجدولة لليوم.</p>
        @endif
    </div>

</x-app-layout>
