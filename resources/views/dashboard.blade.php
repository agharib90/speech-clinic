<x-app-layout>
    <x-slot name="title">لوحة التحكم</x-slot>

    <!-- بطاقات المؤشرات العلوية -->
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">

        <!-- مواعيد اليوم -->
        <div class="clinic-card flex items-center gap-4 p-5">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-[10px] bg-primary-soft text-primary">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </div>
            <div>
                <p class="text-sm text-text-muted">مواعيد اليوم</p>
                <p class="text-2xl font-bold text-text">{{ $todayAppointments }}</p>
            </div>
        </div>

        <!-- من في العيادة الآن -->
        <div class="clinic-card flex items-center gap-4 p-5">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-[10px] bg-success-soft text-success">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <p class="text-sm text-text-muted">حالات داخل العيادة</p>
                <p class="text-2xl font-bold text-text">{{ $currentCheckins }}</p>
            </div>
        </div>

        <!-- إيرادات الشهر -->
        <div class="clinic-card flex items-center gap-4 p-5">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-[10px] bg-accent-soft text-accent">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <p class="text-sm text-text-muted">إيرادات الشهر</p>
                <p class="text-2xl font-bold text-text">{{ number_format($monthlyRevenue, 2) }} {{ $settings->currency ?? 'ج.م' }}</p>
            </div>
        </div>

        <!-- فواتير غير مدفوعة -->
        <div class="clinic-card flex items-center gap-4 p-5">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-[10px] bg-danger-soft text-danger">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <div>
                <p class="text-sm text-text-muted">فواتير معلقة</p>
                <p class="text-2xl font-bold text-text">{{ $unpaidInvoices }}</p>
            </div>
        </div>

    </div>

    <!-- جدول المواعيد القادمة اليوم -->
    <div class="clinic-card p-5 sm:p-6">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-lg font-bold text-text">مواعيد اليوم القادمة</h2>
            <div class="clinic-soundwave text-primary" aria-hidden="true">
                <span></span>
                <span></span>
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>

        @if($upcomingAppointments->count() > 0)
            <div class="overflow-x-auto">
                <table class="clinic-table w-full text-right">
                    <thead class="bg-surface-muted text-text-muted">
                        <tr>
                            <th>الوقت</th>
                            <th>المريض</th>
                            <th>الأخصائي</th>
                            <th>نوع الجلسة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border">
                        @foreach($upcomingAppointments as $app)
                        <tr>
                            <td class="px-4 py-3 font-bold text-text">{{ \Carbon\Carbon::parse($app->scheduled_at)->format('h:i A') }}</td>
                            <td class="px-4 py-3 text-text-muted">{{ $app->patient->name }}</td>
                            <td class="px-4 py-3 text-text-muted">{{ $app->therapist->name }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-primary-soft px-2 py-1 text-xs text-primary">
                                    {{ $app->patientServicePlanItem?->service?->name ?? $app->sessionType?->name ?? 'خدمة غير محددة' }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="py-6 text-center text-text-muted">لا توجد مواعيد مجدولة لليوم.</p>
        @endif
    </div>

</x-app-layout>
