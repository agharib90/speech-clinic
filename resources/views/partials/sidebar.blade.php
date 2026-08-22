<!-- Sidebar Container -->
<aside class="flex h-full w-72 flex-col border-l border-surface-border bg-surface-elevated transition-colors duration-150 lg:w-64">

    <!-- Logo / Brand -->
    <div class="h-16 flex items-center border-b border-surface-border px-6">
        <a href="/" class="flex min-w-0 items-center gap-3">
            @if($settings?->logo_path)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($settings->logo_path) }}" alt="شعار العيادة" class="h-9 w-9 rounded-lg border border-surface-border bg-surface object-contain p-1">
            @else
                <span class="flex h-9 w-9 items-center justify-center rounded-lg border border-surface-border bg-primary-soft" aria-hidden="true">
                    <span class="clinic-soundwave scale-75">
                        <span></span>
                        <span></span>
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </span>
            @endif
            <span class="truncate text-xl font-bold text-primary">
                {{ $settings->clinic_name ?? 'عيادة التخاطب' }}
            </span>
        </a>
    </div>

<!-- Navigation Links -->
<nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">

    <!-- الروابط الأساسية -->
    <div class="px-3 pt-1 pb-1 text-[11px] font-semibold tracking-normal text-text-subtle">الرئيسية</div>
    @role('أخصائي تخاطب')
    <x-nav-link :href="route('therapist.dashboard')" :active="request()->routeIs('therapist.dashboard')" class="flex items-center px-4 py-2.5 rounded-lg transition">
        <svg class="w-5 h-5 me-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
        <span>لوحتي</span>
    </x-nav-link>

    <x-nav-link :href="route('therapist.cases')" :active="request()->routeIs('therapist.cases')" class="flex items-center px-4 py-2.5 rounded-lg transition">
        <svg class="w-5 h-5 me-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z"></path></svg>
        <span>حالاتي</span>
    </x-nav-link>
    @else
    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="flex items-center px-4 py-2.5 rounded-lg transition">
        <svg class="w-5 h-5 me-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
        <span>لوحة التحكم</span>
    </x-nav-link>
    @endrole

    @can('view patients')
    <div class="px-3 pt-3 pb-1 text-[11px] font-semibold tracking-normal text-text-subtle">الملفات</div>
    <x-nav-link :href="route('patients.index')" :active="request()->is('patients*')" class="flex items-center px-4 py-2.5 rounded-lg transition">
        <svg class="w-5 h-5 me-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
        <span>المرضى (الحالات)</span>
    </x-nav-link>

    <x-nav-link :href="route('guardians.index')" :active="request()->is('guardians*')" class="flex items-center px-4 py-2.5 rounded-lg transition">
        <svg class="w-5 h-5 me-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
        <span>أولياء الأمور</span>
    </x-nav-link>
    @endcan

    <!-- الاستقبال والباركود -->
    @if(auth()->user()->can('manage checkins') || auth()->user()->can('view appointments'))
    <div class="px-3 pt-3 pb-1 text-[11px] font-semibold tracking-normal text-text-subtle">التشغيل اليومي</div>
    @endif

    @can('manage checkins')
    <x-nav-link :href="route('reception.index')" :active="request()->is('reception*')" class="flex items-center px-4 py-2.5 rounded-lg transition">
        <svg class="w-5 h-5 me-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
        <span>شاشة الاستقبال</span>
    </x-nav-link>
    @endcan

    <!-- المواعيد -->
    @can('view appointments')
    <x-nav-link :href="route('appointments.index')" :active="request()->is('appointments*')" class="flex items-center px-4 py-2.5 rounded-lg transition">
        <svg class="w-5 h-5 me-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
        <span>المواعيد والجدولة</span>
    </x-nav-link>
    @endcan

    <!-- البرامج العلاجية -->
    @can('view therapy')
    <div class="px-3 pt-3 pb-1 text-[11px] font-semibold tracking-normal text-text-subtle">العلاج</div>
    <x-nav-link :href="route('programs.index')" :active="request()->is('programs*')" class="flex items-center px-4 py-2.5 rounded-lg transition">
        <svg class="w-5 h-5 me-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
        <span>البرامج العلاجية</span>
    </x-nav-link>
    @endcan

    <!-- الفواتير والمالية -->
    @can('view finance')
    <div class="px-3 pt-3 pb-1 text-[11px] font-semibold tracking-normal text-text-subtle">المالية</div>
    <x-nav-link :href="route('invoices.index')" :active="request()->is('invoices*') || request()->is('quotations*')" class="flex items-center px-4 py-2.5 rounded-lg transition">
        <svg class="w-5 h-5 me-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
        <span>الفواتير والمالية</span>
    </x-nav-link>

    <!-- الباقات -->
    <x-nav-link :href="route('packages.index')" :active="request()->is('packages*')" class="flex items-center px-4 py-2.5 rounded-lg transition">
        <svg class="w-5 h-5 me-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
        <span>باقات الجلسات</span>
    </x-nav-link>
    @endcan

    <!-- الكوادر والرواتب -->
    @if(auth()->user()->can('view hr') || auth()->user()->can('manage specialties') || auth()->user()->can('manage services') || auth()->user()->can('manage inventory') || auth()->user()->can('manage settings') || auth()->user()->hasRole('مدير النظام'))
    <div class="px-3 pt-3 pb-1 text-[11px] font-semibold tracking-normal text-text-subtle">الإدارة</div>
    @endif

    @can('view hr')
    <x-nav-link :href="route('therapists.index')" :active="request()->is('therapists*')" class="flex items-center px-4 py-2.5 rounded-lg transition">
        <svg class="w-5 h-5 me-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M9 20H4v-2a3 3 0 015.356-1.857M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
        <span>الفريق العلاجي</span>
    </x-nav-link>

    <x-nav-link :href="route('payroll.index')" :active="request()->is('payroll*')" class="flex items-center px-4 py-2.5 rounded-lg transition">
        <svg class="w-5 h-5 me-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <span>الكوادر والرواتب</span>
    </x-nav-link>
    @endcan

    @can('manage specialties')
    <x-nav-link :href="route('specialties.index')" :active="request()->is('specialties*')" class="flex items-center px-4 py-2.5 rounded-lg transition">
        <svg class="w-5 h-5 me-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-3-3v6m8-3a8 8 0 11-16 0 8 8 0 0116 0z" /></svg>
        <span>التخصصات</span>
    </x-nav-link>
    @endcan

    @can('manage services')
    <x-nav-link :href="route('services.index')" :active="request()->is('services*')" class="flex items-center px-4 py-2.5 rounded-lg transition">
        <svg class="w-5 h-5 me-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a3 3 0 006 0M9 12h6m-6 4h6" /></svg>
        <span>الخدمات العلاجية</span>
    </x-nav-link>
    @endcan

    <!-- المخزن والموردون -->
    @can('manage inventory')
    <x-nav-link :href="route('equipment.index')" :active="request()->is('equipment*') || request()->is('suppliers*')" class="flex items-center px-4 py-2.5 rounded-lg transition">
        <svg class="w-5 h-5 me-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
        <span>المستلزمات والموردون</span>
    </x-nav-link>
    @endcan

    <!-- الإعدادات -->
    @if(auth()->user()->can('manage settings') || auth()->user()->hasRole('مدير النظام'))
    <x-nav-link :href="route('settings.edit')" :active="request()->is('settings*')" class="flex items-center px-4 py-2.5 rounded-lg transition">
        <svg class="w-5 h-5 me-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
        <span>الإعدادات</span>
    </x-nav-link>

    @endif

    @role('مدير النظام')
    <x-nav-link :href="route('users.index')" :active="request()->is('users*')" class="flex items-center px-4 py-2.5 rounded-lg transition">
        <svg class="w-5 h-5 me-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        <span>إدارة المستخدمين</span>
    </x-nav-link>
    @endrole
</nav></aside>
