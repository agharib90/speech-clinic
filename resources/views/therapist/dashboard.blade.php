<x-app-layout>
    <x-slot name="title">لوحة الأخصائي - {{ Auth::user()->name }}</x-slot>

    {{-- ─── ترحيب ─────────────────────────────────────────────────────── --}}
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                مرحباً، {{ Auth::user()->name }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ now()->isoFormat('dddd، D MMMM YYYY') }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('therapist.cases') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z"/>
                </svg>
                حالاتي
            </a>
            <a href="{{ route('appointments.index', ['date' => today()->format('Y-m-d'), 'therapist_id' => Auth::id()]) }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                جدول اليوم
            </a>
            <a href="{{ route('programs.index', ['status' => \App\Models\TherapyProgram::STATUS_ACTIVE]) }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 7h6m-6 4h6"/>
                </svg>
                البرامج الجارية
            </a>
        </div>
    </div>

    {{-- ─── بطاقات الإحصائيات ───────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

        {{-- مواعيد اليوم --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/40 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $todayAppointments }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">مواعيد اليوم</p>
        </div>

        {{-- مكتمل اليوم --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/40 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $completedToday }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">مكتملة اليوم</p>
        </div>

        {{-- مرضى نشطون --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/40 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $activePatientsCount }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">مرضى نشطون</p>
        </div>

        {{-- نسبة الحضور هذا الشهر --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/40 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $monthlyStats['attendance_rate'] }}%</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">نسبة الحضور – {{ now()->translatedFormat('F') }}</p>
        </div>

    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

        {{-- ─── جدول اليوم ──────────────────────────────────────────────── --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h2 class="font-bold text-gray-900 dark:text-white">جدول اليوم</h2>
                <span class="text-xs text-gray-400 dark:text-gray-500">{{ today()->format('d/m/Y') }}</span>
            </div>

            @if($todaySchedule->isEmpty())
                <div class="px-6 py-12 text-center">
                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-gray-400 dark:text-gray-500">لا توجد مواعيد اليوم</p>
                </div>
            @else
                <div class="divide-y divide-gray-50 dark:divide-gray-700">
                    @foreach($todaySchedule as $apt)
                    <div class="px-6 py-4 flex items-center gap-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">

                        {{-- الوقت --}}
                        <div class="w-16 text-center flex-shrink-0">
                            <p class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ \Carbon\Carbon::parse($apt->scheduled_at)->format('h:i') }}
                            </p>
                            <p class="text-xs text-gray-400">
                                {{ \Carbon\Carbon::parse($apt->scheduled_at)->format('A') }}
                            </p>
                        </div>

                        {{-- خط عمودي ملوّن حسب الحالة --}}
                        <div class="w-1 h-10 rounded-full flex-shrink-0
                            @if($apt->status === 'مكتمل') bg-green-400
                            @elseif($apt->status === 'غياب') bg-red-400
                            @elseif($apt->status === 'ملغى') bg-gray-300
                            @else bg-blue-400 @endif">
                        </div>

                        {{-- بيانات المريض --}}
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 dark:text-white truncate">
                                {{ $apt->patient->name }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $apt->sessionType->name }}
                                · {{ $apt->sessionType->duration_minutes }} دقيقة
                            </p>
                        </div>

                        {{-- بادج الحالة --}}
                        @if($apt->status === 'مجدول')
                            <span class="text-xs bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-2.5 py-1 rounded-full font-medium">
                                مجدول
                            </span>
                        @elseif($apt->status === 'مكتمل')
                            <span class="text-xs bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 px-2.5 py-1 rounded-full font-medium">
                                مكتمل
                            </span>
                        @elseif($apt->status === 'غياب')
                            <span class="text-xs bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 px-2.5 py-1 rounded-full font-medium">
                                غياب
                            </span>
                        @else
                            <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 px-2.5 py-1 rounded-full font-medium">
                                ملغى
                            </span>
                        @endif

                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ─── إحصائيات الشهر ──────────────────────────────────────────── --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="font-bold text-gray-900 dark:text-white">
                    إحصائيات {{ now()->translatedFormat('F') }}
                </h2>
            </div>
            <div class="p-6 space-y-4">

                {{-- شريط الحضور --}}
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600 dark:text-gray-400">نسبة الحضور</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $monthlyStats['attendance_rate'] }}%</span>
                    </div>
                    <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full bg-green-500 rounded-full transition-all duration-500"
                             style="width: {{ $monthlyStats['attendance_rate'] }}%"></div>
                    </div>
                </div>

                {{-- الأرقام --}}
                <div class="space-y-3 pt-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-green-500 flex-shrink-0"></span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">جلسات مكتملة</span>
                        </div>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $monthlyStats['completed'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-red-400 flex-shrink-0"></span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">غياب</span>
                        </div>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $monthlyStats['absent'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-gray-400 flex-shrink-0"></span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">ملغى</span>
                        </div>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $monthlyStats['cancelled'] }}</span>
                    </div>
                </div>

                <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">استحقاقات الشهر</span>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400">
                            {{ number_format($monthlyStats['earnings'], 0) }} ج.م
                        </span>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6 mb-6">

        {{-- ─── حالاتي النشطة ────────────────────────────────────────────── --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h2 class="font-bold text-gray-900 dark:text-white">حالاتي النشطة</h2>
                <a href="{{ route('therapist.cases') }}"
                   class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                    عرض الكل
                </a>
            </div>

            @if($activePrograms->isEmpty())
                <div class="px-6 py-10 text-center text-gray-400 dark:text-gray-500 text-sm">
                    لا توجد برامج نشطة حالياً
                </div>
            @else
                <div class="divide-y divide-gray-50 dark:divide-gray-700">
                    @foreach($activePrograms as $program)
                    <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                {{-- اسم المريض --}}
                                <a href="{{ route('programs.show', $program) }}"
                                   class="font-medium text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 truncate block">
                                    {{ $program->patient->name }}
                                </a>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ $program->name }}
                                    @if($program->disorder_type)
                                        · {{ $program->disorder_type }}
                                    @endif
                                </p>

                                {{-- شريط التقدم --}}
                                <div class="mt-2">
                                    <div class="flex justify-between text-xs text-gray-400 mb-1">
                                        <span>{{ $program->total_sessions_done }} جلسة مكتملة</span>
                                        <span>{{ $program->progress }}%</span>
                                    </div>
                                    <div class="h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-500
                                            @if($program->progress >= 75) bg-green-500
                                            @elseif($program->progress >= 40) bg-blue-500
                                            @else bg-amber-500 @endif"
                                             style="width: {{ $program->progress }}%"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- آخر جلسة --}}
                            @if($program->last_session)
                            <div class="text-left flex-shrink-0">
                                <p class="text-xs text-gray-400 dark:text-gray-500">آخر جلسة</p>
                                <p class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                    {{ \Carbon\Carbon::parse($program->last_session->session_date)->format('d/m') }}
                                </p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ─── الواجبات المتأخرة ────────────────────────────────────────── --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h2 class="font-bold text-gray-900 dark:text-white">
                    واجبات تحتاج متابعة
                    @if($pendingTasks->isNotEmpty())
                        <span class="mr-2 text-xs bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 px-2 py-0.5 rounded-full">
                            {{ $pendingTasks->count() }}
                        </span>
                    @endif
                </h2>
            </div>

            @if($pendingTasks->isEmpty())
                <div class="px-6 py-10 text-center">
                    <svg class="w-10 h-10 text-green-300 dark:text-green-700 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-gray-400 dark:text-gray-500">لا توجد واجبات متأخرة</p>
                </div>
            @else
                <div class="divide-y divide-gray-50 dark:divide-gray-700">
                    @foreach($pendingTasks as $task)
                    <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ $task->session->program->patient->name ?? '—' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-1">
                                    {{ $task->description }}
                                </p>
                            </div>
                            <div class="flex-shrink-0 text-left">
                                @php
                                    $daysLate = \Carbon\Carbon::parse($task->due_date)->diffInDays(today());
                                @endphp
                                <span class="text-xs font-medium
                                    @if($daysLate > 3) text-red-600 dark:text-red-400
                                    @else text-amber-600 dark:text-amber-400 @endif">
                                    متأخر {{ $daysLate }} يوم
                                </span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ─── آخر تطورات الحالات ─────────────────────────────────────── --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h2 class="font-bold text-gray-900 dark:text-white">آخر تطورات الحالات</h2>
                <a href="{{ route('therapist.cases') }}" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">حالاتي</a>
            </div>

            @if($recentProgressPoints->isEmpty())
                <div class="px-6 py-10 text-center">
                    <svg class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-6a2 2 0 012-2h2a2 2 0 012 2v6m-8 0h8m-10 4h12a2 2 0 002-2V7a2 2 0 00-.586-1.414l-3-3A2 2 0 0015 2H7a2 2 0 00-2 2v15a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-sm text-gray-400 dark:text-gray-500">لا توجد نقاط تطور مسجلة مؤخراً</p>
                </div>
            @else
                <div class="divide-y divide-gray-50 dark:divide-gray-700">
                    @foreach($recentProgressPoints as $point)
                        <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $point->program->patient->name ?? 'حالة غير متاحة' }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $point->domain }} · {{ $point->program->name ?? 'برنامج غير متاح' }}
                                    </p>
                                    @if($point->notes)
                                        <p class="mt-2 line-clamp-2 text-xs text-gray-500 dark:text-gray-400">{{ $point->notes }}</p>
                                    @endif
                                </div>
                                <div class="flex-shrink-0 text-left">
                                    <span class="block text-sm font-bold text-blue-600 dark:text-blue-400">{{ number_format((float) $point->score, 0) }}</span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ $point->recorded_at?->format('d/m') }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

    {{-- ─── المواعيد القادمة ────────────────────────────────────────────── --}}
    @if($upcomingAppointments->isNotEmpty())
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="font-bold text-gray-900 dark:text-white">المواعيد القادمة</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-right">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">التاريخ</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">الوقت</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">المريض</th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">نوع الجلسة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                    @foreach($upcomingAppointments as $apt)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="px-6 py-3 text-sm text-gray-900 dark:text-white">
                            {{ \Carbon\Carbon::parse($apt->scheduled_at)->translatedFormat('l، d/m') }}
                        </td>
                        <td class="px-6 py-3 text-sm text-gray-700 dark:text-gray-300">
                            {{ \Carbon\Carbon::parse($apt->scheduled_at)->format('h:i A') }}
                        </td>
                        <td class="px-6 py-3 text-sm font-medium text-gray-900 dark:text-white">
                            {{ $apt->patient->name }}
                        </td>
                        <td class="px-6 py-3">
                            <span class="text-xs bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-2.5 py-1 rounded-full">
                                {{ $apt->sessionType->name }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</x-app-layout>
