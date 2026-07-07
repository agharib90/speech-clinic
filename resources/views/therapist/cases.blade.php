<x-app-layout>
    <x-slot name="title">حالاتي</x-slot>

    @php
        $statusClasses = [
            \App\Models\TherapyProgram::STATUS_ACTIVE => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-300 dark:border-emerald-800',
            \App\Models\TherapyProgram::STATUS_COMPLETED => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-300 dark:border-blue-800',
            \App\Models\TherapyProgram::STATUS_STOPPED => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600',
        ];
    @endphp

    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">حالاتي</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">مساحة متابعة الحالات المرتبطة ببرامجك العلاجية فقط.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('therapist.dashboard') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    لوحتي
                </a>
                <a href="{{ route('appointments.index', ['date' => today()->format('Y-m-d'), 'therapist_id' => auth()->id()]) }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    جدول اليوم
                </a>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-lg border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">إجمالي الحالات</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $caseStats['total'] }}</p>
            </div>
            <div class="rounded-lg border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">برامج جارية</p>
                <p class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $caseStats['active'] }}</p>
            </div>
            <div class="rounded-lg border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">حالات اليوم</p>
                <p class="mt-2 text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $caseStats['today'] }}</p>
            </div>
            <div class="rounded-lg border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">مواعيد قادمة</p>
                <p class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $caseStats['upcoming'] }}</p>
            </div>
        </div>

        <form method="GET" action="{{ route('therapist.cases') }}" class="rounded-lg border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="grid gap-3 lg:grid-cols-[1fr_220px_auto_auto] lg:items-end">
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">بحث</span>
                    <input type="search" name="search" value="{{ $search }}" placeholder="اسم الحالة، الباركود، ولي الأمر، أو البرنامج" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">حالة البرنامج</span>
                    <select name="status" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        <option value="">كل الحالات</option>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="flex min-h-[42px] items-center gap-2 rounded-lg border border-gray-200 px-3 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-200">
                    <input type="checkbox" name="today" value="1" @checked(request()->boolean('today')) class="rounded border-gray-300 text-blue-600">
                    مواعيد اليوم فقط
                </label>
                <button type="submit" class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 text-sm font-medium text-white transition hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                    </svg>
                    تطبيق
                </button>
            </div>
        </form>

        <div class="overflow-hidden rounded-lg border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                <h2 class="font-bold text-gray-900 dark:text-white">قائمة الحالات</h2>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $programs->total() }} برنامج</span>
            </div>

            @if($programs->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                    لا توجد حالات مطابقة للفلاتر الحالية.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[980px] text-right">
                        <thead class="bg-gray-50 text-xs font-medium uppercase text-gray-500 dark:bg-gray-700/50 dark:text-gray-300">
                            <tr>
                                <th class="px-5 py-3">الحالة</th>
                                <th class="px-5 py-3">البرنامج</th>
                                <th class="px-5 py-3">آخر جلسة</th>
                                <th class="px-5 py-3">الموعد القادم</th>
                                <th class="px-5 py-3">الباقة</th>
                                <th class="px-5 py-3">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($programs as $program)
                                @php
                                    $patient = $program->patient;
                                    $lastSession = $program->sessions->first();
                                    $nextAppointment = $nextAppointments->get($program->patient_id);
                                    $activePackage = $activePackages->get($program->patient_id);
                                    $remainingSessions = $activePackage ? max(0, (int) $activePackage->total_sessions - (int) $activePackage->used_sessions) : null;
                                    $statusClass = $statusClasses[$program->status] ?? 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600';
                                @endphp
                                <tr class="align-top transition hover:bg-gray-50/80 dark:hover:bg-gray-700/40">
                                    <td class="px-5 py-4">
                                        <div class="flex items-start gap-3">
                                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-blue-50 text-sm font-bold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                                {{ mb_substr($patient?->name ?? '-', 0, 1) }}
                                            </div>
                                            <div class="min-w-0">
                                                @if($patient)
                                                    <a href="{{ route('patients.show', $patient) }}" class="font-semibold text-gray-900 hover:text-blue-600 dark:text-white dark:hover:text-blue-400">
                                                        {{ $patient->name }}
                                                    </a>
                                                @else
                                                    <span class="font-semibold text-gray-900 dark:text-white">حالة غير متاحة</span>
                                                @endif
                                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $patient?->barcode ?? 'بدون باركود' }}
                                                    @if($patient?->age)
                                                        · {{ $patient->age }}
                                                    @endif
                                                </p>
                                                @if($patient?->guardian)
                                                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ $patient->guardian->name }} · {{ $patient->guardian->phone }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <a href="{{ route('programs.show', $program) }}" class="font-medium text-gray-900 hover:text-blue-600 dark:text-white dark:hover:text-blue-400">
                                            {{ $program->name }}
                                        </a>
                                        <div class="mt-2 flex flex-wrap items-center gap-2">
                                            <span class="rounded-full border px-2.5 py-1 text-xs font-medium {{ $statusClass }}">
                                                {{ $program->status }}
                                            </span>
                                            @if($program->disorder_type)
                                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $program->disorder_type }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        @if($lastSession)
                                            <span class="font-medium">{{ \Carbon\Carbon::parse($lastSession->session_date)->format('d/m/Y') }}</span>
                                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ $lastSession->status }}</p>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">لا توجد جلسات</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        @if($nextAppointment)
                                            <span class="font-medium">{{ \Carbon\Carbon::parse($nextAppointment->scheduled_at)->format('d/m/Y') }}</span>
                                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ \Carbon\Carbon::parse($nextAppointment->scheduled_at)->format('h:i A') }}</p>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">لا يوجد موعد قادم</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        @if($activePackage)
                                            <span class="font-medium">{{ $activePackage->name }}</span>
                                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ $remainingSessions }} جلسة متبقية</p>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">لا توجد باقة نشطة</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap gap-2">
                                            <a href="{{ route('programs.show', $program) }}" class="rounded-lg border border-blue-200 px-3 py-1.5 text-xs font-medium text-blue-700 transition hover:bg-blue-50 dark:border-blue-800 dark:text-blue-300 dark:hover:bg-blue-900/20">البرنامج</a>
                                            @if($patient)
                                                <a href="{{ route('patients.show', $patient) }}" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700">الملف</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($programs->hasPages())
                <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-700">
                    {{ $programs->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
