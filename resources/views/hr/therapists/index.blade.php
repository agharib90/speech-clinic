<x-app-layout>
    <x-slot name="title">الفريق العلاجي</x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
                {{ session('error') }}
            </div>
        @endif

        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">الفريق العلاجي</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">متابعة الأخصائيين، ربط الحسابات، والحضور اليومي من مكان واحد.</p>
            </div>
            @can('manage therapists')
                <a href="{{ route('therapists.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    إضافة أخصائي
                </a>
            @endcan
        </div>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-lg border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">إجمالي الفريق</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $teamStats['total'] }}</p>
            </div>
            <div class="rounded-lg border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">نشطون</p>
                <p class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $teamStats['active'] }}</p>
            </div>
            <div class="rounded-lg border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">حسابات مرتبطة</p>
                <p class="mt-2 text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $teamStats['linked'] }}</p>
            </div>
            <div class="rounded-lg border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">مواعيد اليوم</p>
                <p class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $teamStats['today_appointments'] }}</p>
            </div>
        </div>

        @can('manage therapists')
            <div class="rounded-lg border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 class="font-bold text-gray-900 dark:text-white">تسجيل حضور اليوم</h2>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ today()->format('Y-m-d') }}</span>
                </div>
                <form action="{{ route('therapists.attendance') }}" method="POST" class="grid gap-3 md:grid-cols-[1fr_180px_auto] md:items-end">
                    @csrf
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">الأخصائي</span>
                        <select name="therapist_id" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" required>
                            <option value="">اختر الأخصائي</option>
                            @foreach($therapists as $therapist)
                                <option value="{{ $therapist->id }}">{{ $therapist->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">الحالة</span>
                        <select name="status" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                            <option value="حاضر">حاضر</option>
                            <option value="إجازة">إجازة</option>
                            <option value="غائب">غائب</option>
                        </select>
                    </label>
                    <button type="submit" class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 text-sm font-medium text-white transition hover:bg-emerald-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        تسجيل
                    </button>
                </form>
            </div>
        @endcan

        <div class="overflow-hidden rounded-lg border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                <h2 class="font-bold text-gray-900 dark:text-white">الأخصائيون</h2>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $therapists->count() }} عضو</span>
            </div>

            @if($therapists->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                    لا يوجد أخصائيون مسجلون بعد.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-right">
                        <thead class="bg-gray-50 text-xs font-medium uppercase text-gray-500 dark:bg-gray-700/50 dark:text-gray-300">
                            <tr>
                                <th class="px-5 py-3">الأخصائي</th>
                                <th class="px-5 py-3">التخصص</th>
                                <th class="px-5 py-3">الحساب</th>
                                <th class="px-5 py-3">البرامج النشطة</th>
                                <th class="px-5 py-3">اليوم / الشهر</th>
                                <th class="px-5 py-3">نظام الراتب</th>
                                <th class="px-5 py-3">الحالة</th>
                                @can('manage therapist services')<th class="px-5 py-3">الخدمات</th>@endcan
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($therapists as $therapist)
                                @php
                                    $userId = $therapist->user_id;
                                    $activePrograms = $userId ? (int) ($activeProgramCounts[$userId] ?? 0) : 0;
                                    $todayAppointments = $userId ? (int) ($todayAppointmentCounts[$userId] ?? 0) : 0;
                                    $completedMonth = $userId ? (int) ($completedMonthCounts[$userId] ?? 0) : 0;
                                @endphp
                                <tr class="transition hover:bg-gray-50/80 dark:hover:bg-gray-700/40">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-blue-50 text-sm font-bold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                                {{ mb_substr($therapist->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-900 dark:text-white">{{ $therapist->name }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $therapist->phone ?: 'بدون هاتف' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $therapist->specialization ?: 'غير محدد' }}</td>
                                    <td class="px-5 py-4 text-sm">
                                        @if($therapist->user)
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $therapist->user->email }}</span>
                                        @else
                                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 dark:bg-amber-900/20 dark:text-amber-300">غير مرتبط</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ $activePrograms }}</span>
                                        برنامج
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ $todayAppointments }}</span>
                                        اليوم
                                        <span class="mx-1 text-gray-300">/</span>
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ $completedMonth }}</span>
                                        مكتملة
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        @if($therapist->salary_type === 'monthly')
                                            شهري · {{ number_format((float) $therapist->monthly_salary, 0) }}
                                        @elseif($therapist->salary_type === 'daily')
                                            يومي · {{ number_format((float) $therapist->daily_salary, 0) }}
                                        @else
                                            عمولة · {{ number_format((float) $therapist->commission_rate, 0) }}%
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">
                                        @if($therapist->is_active)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                نشط
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                                                غير نشط
                                            </span>
                                        @endif
                                    </td>
                                    @can('manage therapist services')
                                        <td class="px-5 py-4">
                                            <a href="{{ route('therapists.services.edit', $therapist) }}" class="text-sm font-medium text-primary hover:text-primary-hover">الخدمات والاستحقاقات</a>
                                        </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
