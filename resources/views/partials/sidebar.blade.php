<!-- Sidebar Container -->
<aside class="w-64 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 flex flex-col transition-all duration-300">

    <!-- Logo / Brand -->
    <div class="h-16 flex items-center justify-center border-b border-gray-200 dark:border-gray-700 px-6">
        <a href="/" class="text-xl font-bold text-blue-600 dark:text-blue-400">
            عيادة التخاطب
        </a>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 overflow-y-auto py-4 px-4 space-y-2">

        <!-- الروابط الأساسية -->
        @role('أخصائي تخاطب')
        <x-nav-link :href="route('therapist.dashboard')" :active="request()->routeIs('therapist.dashboard')" class="flex items-center space-x-reverse space-x-3 px-4 py-2.5 rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            <span>لوحتي</span>
        </x-nav-link>
        @else
        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="flex items-center space-x-reverse space-x-3 px-4 py-2.5 rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            <span>لوحة التحكم</span>
        </x-nav-link>
        @endrole
        @can('view patients')
        <x-nav-link :href="route('patients.index')" :active="request()->is('patients*')" class="flex items-center space-x-reverse space-x-3 px-4 py-2.5 rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            <span>المرضى (الحالات)</span>
        </x-nav-link>

        <x-nav-link :href="route('guardians.index')" :active="request()->is('guardians*')" class="flex items-center space-x-reverse space-x-3 px-4 py-2.5 rounded-lg transition mr-6">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            <span>أولياء الأمور</span>
        </x-nav-link>
        @endcan

                <!-- الاستقبال والباركود -->
        @can('manage checkins')
        <x-nav-link :href="route('reception.index')" :active="request()->is('reception*')" class="flex items-center space-x-reverse space-x-3 px-4 py-2.5 rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
            <span>شاشة الاستقبال</span>
        </x-nav-link>
        @endcan

        <!-- المواعيد -->
        @can('view appointments')
        <x-nav-link :href="route('appointments.index')" :active="request()->is('appointments*')" class="flex items-center space-x-reverse space-x-3 px-4 py-2.5 rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            <span>المواعيد والجدولة</span>
        </x-nav-link>
        @endcan

                <!-- البرامج العلاجية -->
        @can('view therapy')
        <x-nav-link :href="route('programs.index')" :active="request()->is('programs*')" class="flex items-center space-x-reverse space-x-3 px-4 py-2.5 rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
            <span>البرامج العلاجية</span>
        </x-nav-link>
        @endcan

        <!-- الفواتير والمالية -->
        @can('view finance')
        <x-nav-link :href="route('invoices.index')" :active="request()->is('invoices*') || request()->is('quotations*')" class="flex items-center space-x-reverse space-x-3 px-4 py-2.5 rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            <span>الفواتير والمالية</span>
        </x-nav-link>

        <!-- الباقات (إضافة جديدة) -->
        <x-nav-link :href="route('packages.index')" :active="request()->is('packages*')" class="flex items-center space-x-reverse space-x-3 px-4 py-2.5 rounded-lg transition mr-4">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            <span>باقات الجلسات</span>
        </x-nav-link>
        @endcan

                <!-- الكوادر والرواتب -->
        @can('view hr')
        <x-nav-link :href="route('payroll.index')" :active="request()->is('payroll*')" class="flex items-center space-x-reverse space-x-3 px-4 py-2.5 rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span>الكوادر والرواتب</span>
        </x-nav-link>
        @endcan

                <!-- المخزن والموردون -->
        @can('manage inventory')
        <x-nav-link :href="route('equipment.index')" :active="request()->is('equipment*') || request()->is('suppliers*')" class="flex items-center space-x-reverse space-x-3 px-4 py-2.5 rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            <span>المستلزمات والموردون</span>
        </x-nav-link>
        @endcan

                <!-- الإعدادات -->
        @can('manage settings')
        <x-nav-link :href="route('settings.edit')" :active="request()->is('settings*')" class="flex items-center space-x-reverse space-x-3 px-4 py-2.5 rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            <span>الإعدادات</span>
        </x-nav-link>
        @endcan
    </nav>
</aside>
