<x-app-layout>
    <x-slot name="title">مساحة عمل الأخصائي</x-slot>

    @php
        $errorKeys = $errors->keys();
        $initialTab = session('workspace_tab', request('tab', 'overview'));
        if (collect($errorKeys)->contains(fn ($key) => str_starts_with($key, 'rates.') || in_array($key, ['service_ids', 'specialty_ids']))) {
            $initialTab = 'services';
        } elseif (collect($errorKeys)->contains(fn ($key) => str_starts_with($key, 'periods'))) {
            $initialTab = 'schedule';
        }
        $allowedTabs = ['overview', 'appointments', 'services', 'schedule', 'compensation'];
        $initialTab = in_array($initialTab, $allowedTabs, true) ? $initialTab : 'overview';
    @endphp

    <div class="space-y-5" x-data="{ tab: @js($initialTab) }">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-xs font-semibold text-primary">مساحة عمل الأخصائي · ملف الأخصائي</p>
                    <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $therapist->is_active ? 'bg-success-soft text-success' : 'bg-surface-muted text-text-muted' }}">{{ $therapist->is_active ? 'نشط' : 'غير نشط' }}</span>
                </div>
                <h1 class="mt-1 text-2xl font-bold text-text">{{ $therapist->name }}</h1>
                <p class="mt-1 text-sm text-text-muted">{{ $therapist->specialization ?: 'تخصص رئيسي غير محدد' }} · {{ $therapist->specialties->pluck('name')->join('، ') ?: 'لا توجد تخصصات مسندة' }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('manage therapists')
                    <a href="{{ route('therapists.edit', $therapist) }}" class="clinic-btn-secondary">تعديل البيانات</a>
                @endcan

                <a href="{{ route('therapists.index') }}" class="clinic-btn-secondary">رجوع إلى الفريق</a>
            </div>
        </div>

        <section class="grid gap-3 rounded-lg border border-surface-border bg-surface px-4 py-3 sm:grid-cols-3">
            <div><p class="text-xs text-text-muted">حالة الحساب</p><p class="mt-1 font-semibold {{ $therapist->user ? 'text-success' : 'text-warning' }}">حساب الدخول: {{ $therapist->user ? 'مرتبط' : 'غير مرتبط' }}</p>@if($therapist->user)<p class="mt-1 break-all text-xs text-text-muted">{{ $therapist->user->name }} · {{ $therapist->user->email }}</p>@endif</div>
            <div><p class="text-xs text-text-muted">نوع الأجر</p><p class="mt-1 font-semibold text-text">{{ $therapist->salary_type === 'monthly' ? 'شهري' : ($therapist->salary_type === 'daily' ? 'يومي' : 'عمولة') }}</p></div>
            <div><p class="text-xs text-text-muted">القيمة الحالية</p><p class="mt-1 font-semibold text-text">@if($therapist->salary_type === 'monthly'){{ number_format((float) $therapist->monthly_salary, 2) }} ج.م @elseif($therapist->salary_type === 'daily'){{ number_format((float) $therapist->daily_salary, 2) }} ج.م @else{{ number_format((float) $therapist->commission_rate, 2) }}% @endif</p></div>
        </section>

        @if(session('success'))
            <div class="rounded-lg border border-success/30 bg-success-soft px-4 py-3 text-sm text-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="rounded-lg border border-danger/30 bg-danger-soft px-4 py-3 text-sm text-danger">تعذر الحفظ. راجع الحقول المحددة في التبويب المفتوح.</div>
        @endif

        <nav class="flex gap-1 overflow-x-auto border-b border-surface-border" aria-label="تبويبات مساحة عمل الأخصائي">
            @foreach(['overview' => 'نظرة عامة', 'appointments' => 'المواعيد', 'services' => 'الخدمات والاستحقاقات', 'schedule' => 'جدول العمل', 'compensation' => 'الأجر والتعاقد'] as $tabCode => $tabLabel)
                <button type="button" @click="tab = '{{ $tabCode }}'" class="shrink-0 border-b-2 px-4 py-3 text-sm font-semibold" :class="tab === '{{ $tabCode }}' ? 'border-primary text-primary' : 'border-transparent text-text-muted hover:text-text'">{{ $tabLabel }}</button>
            @endforeach
        </nav>

        <div x-show="tab === 'overview'" x-cloak class="space-y-4">
        <section class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-lg border border-surface-border bg-surface p-5 lg:col-span-2">
                <h2 class="text-lg font-bold text-text">البيانات الأساسية</h2>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div><dt class="text-xs text-text-muted">الهاتف</dt><dd class="mt-1 font-medium text-text" dir="ltr">{{ $therapist->phone ?: 'غير مسجل' }}</dd></div>
                    <div><dt class="text-xs text-text-muted">البريد الإلكتروني</dt><dd class="mt-1 break-all font-medium text-text">{{ $therapist->email ?: $therapist->user?->email ?: 'غير مسجل' }}</dd></div>
                    <div><dt class="text-xs text-text-muted">رقم الترخيص</dt><dd class="mt-1 font-medium text-text">{{ $therapist->license_number ?: 'غير مسجل' }}</dd></div>
                    <div><dt class="text-xs text-text-muted">تاريخ التعيين</dt><dd class="mt-1 font-medium text-text">{{ $therapist->hire_date?->format('Y-m-d') ?: 'غير مسجل' }}</dd></div>
                    <div><dt class="text-xs text-text-muted">التخصص المسجل</dt><dd class="mt-1 font-medium text-text">{{ $therapist->specialization ?: 'غير محدد' }}</dd></div>
                    <div><dt class="text-xs text-text-muted">الحالة</dt><dd class="mt-1 font-semibold {{ $therapist->is_active ? 'text-success' : 'text-text-muted' }}">{{ $therapist->is_active ? 'نشط' : 'غير نشط' }}</dd></div>
                </dl>
            </div>

            <div class="rounded-lg border border-surface-border bg-surface p-5">
                <h2 class="text-lg font-bold text-text">التخصصات</h2>
                <div class="mt-4 flex flex-wrap gap-2">
                    @forelse($therapist->specialties as $specialty)
                        <span class="rounded-full bg-primary-soft px-3 py-1 text-xs font-medium text-primary">{{ $specialty->name }}</span>
                    @empty
                        <p class="text-sm text-text-muted">لا توجد تخصصات مسندة.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3" aria-label="مؤشرات أداء الأخصائي">
            <div class="rounded-lg border border-surface-border bg-surface p-4" data-kpi="today-appointments"><p class="text-xs text-text-muted">مواعيد اليوم</p><p class="mt-1 text-2xl font-bold text-text" data-kpi-value="{{ $canViewAppointments ? $workspaceStats['today_appointments'] : 'unavailable' }}">{{ $canViewAppointments ? $workspaceStats['today_appointments'] : '—' }}</p></div>
            <div class="rounded-lg border border-surface-border bg-surface p-4" data-kpi="upcoming-appointments"><p class="text-xs text-text-muted">القادمة خلال 7 أيام</p><p class="mt-1 text-2xl font-bold text-text" data-kpi-value="{{ $canViewAppointments ? $workspaceStats['upcoming_appointments_7_days'] : 'unavailable' }}">{{ $canViewAppointments ? $workspaceStats['upcoming_appointments_7_days'] : '—' }}</p></div>
            <div class="rounded-lg border border-surface-border bg-surface p-4" data-kpi="completed-sessions"><p class="text-xs text-text-muted">الجلسات المكتملة هذا الشهر</p><p class="mt-1 text-2xl font-bold text-text" data-kpi-value="{{ $canViewTherapyKpis && $therapist->user_id ? $workspaceStats['completed_sessions_this_month'] : 'unavailable' }}">{{ $canViewTherapyKpis && $therapist->user_id ? $workspaceStats['completed_sessions_this_month'] : '—' }}</p></div>
            <div class="rounded-lg border border-surface-border bg-surface p-4" data-kpi="active-cases"><p class="text-xs text-text-muted">الحالات النشطة</p><p class="mt-1 text-2xl font-bold text-text" data-kpi-value="{{ $canViewTherapyKpis && $therapist->user_id ? $workspaceStats['active_cases'] : 'unavailable' }}">{{ $canViewTherapyKpis && $therapist->user_id ? $workspaceStats['active_cases'] : '—' }}</p></div>
            <div class="rounded-lg border border-surface-border bg-surface p-4" data-kpi="monthly-earnings"><p class="text-xs text-text-muted">استحقاقات الجلسات هذا الشهر</p><p class="mt-1 text-xl font-bold text-text" data-kpi-value="{{ $canViewEarningKpi ? number_format((float) $workspaceStats['monthly_earnings'], 2, '.', '') : 'unavailable' }}">{{ $canViewEarningKpi ? number_format((float) $workspaceStats['monthly_earnings'], 2).' ج.م' : '—' }}</p></div>
            <div class="rounded-lg border border-surface-border bg-surface p-4" data-kpi="assigned-services"><p class="text-xs text-text-muted">الخدمات المسندة</p><p class="mt-1 text-2xl font-bold text-text" data-kpi-value="{{ $workspaceStats['assigned_services'] }}">{{ $workspaceStats['assigned_services'] }}</p></div>
        </section>
        </div>

        <section x-show="tab === 'compensation'" x-cloak class="rounded-lg border border-surface-border bg-surface p-5">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <div>
                    <h2 class="text-lg font-bold text-text">الأجر والتعاقد</h2>
                    <p class="mt-1 text-sm text-text-muted">القيم الحالية محفوظة للعرض فقط. تغيير الأجر سيتم لاحقًا من خلال سجل تاريخي مستقل.</p>
                </div>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-surface-border bg-surface-muted p-4">
                    <p class="text-xs text-text-muted">نظام الأجر</p>
                    <p class="mt-1 font-semibold text-text">
                        @if($therapist->salary_type === 'monthly')
                            راتب شهري
                        @elseif($therapist->salary_type === 'daily')
                            راتب يومي
                        @else
                            عمولة
                        @endif
                    </p>
                </div>

                <div class="rounded-lg border border-surface-border bg-surface-muted p-4">
                    <p class="text-xs text-text-muted">القيمة الحالية</p>
                    <p class="mt-1 font-semibold text-text">
                        @if($therapist->salary_type === 'monthly')
                            {{ number_format((float) $therapist->monthly_salary, 2) }} ج.م
                        @elseif($therapist->salary_type === 'daily')
                            {{ number_format((float) $therapist->daily_salary, 2) }} ج.م
                        @else
                            {{ number_format((float) $therapist->commission_rate, 2) }}%
                        @endif
                    </p>
                </div>

                <div class="rounded-lg border border-surface-border bg-surface-muted p-4">
                    <p class="text-xs text-text-muted">تعديل الأجر</p>
                    <p class="mt-1 text-sm font-medium text-warning">سيتم من سجل الأجور التاريخي</p>
                </div>
            </div>
        </section>

        <section x-show="tab === 'appointments'" x-cloak class="space-y-5">
            @if(! $therapist->user_id)
                <div class="rounded-lg border border-warning/30 bg-warning-soft p-5 text-sm text-warning">لا يمكن عرض مواعيد هذا الأخصائي قبل ربطه بحساب دخول.</div>
            @elseif(! $canViewAppointments)
                <div class="rounded-lg border border-surface-border bg-surface p-5 text-sm text-text-muted">لا تملك صلاحية عرض المواعيد.</div>
            @else
                @foreach([['title' => 'مواعيد اليوم', 'appointments' => $todayAppointments], ['title' => 'المواعيد القادمة', 'appointments' => $upcomingAppointments]] as $appointmentSection)
                    <section class="overflow-hidden rounded-lg border border-surface-border bg-surface">
                        <div class="border-b border-surface-border px-4 py-3"><h2 class="font-bold text-text">{{ $appointmentSection['title'] }}</h2></div>
                        <div class="divide-y divide-surface-border">
                            @forelse($appointmentSection['appointments'] as $appointment)
                                <div class="grid gap-3 px-4 py-3 sm:grid-cols-[90px_minmax(160px,1fr)_minmax(180px,1fr)_auto] sm:items-center">
                                    <p class="font-semibold text-text">{{ $appointment->scheduled_at->format('h:i A') }}</p>
                                    <div>@can('view patients')<a href="{{ route('patients.workspace', $appointment->patient) }}" class="clinic-entity-link font-semibold">{{ $appointment->patient->name }}</a>@else<p class="font-semibold text-text">{{ $appointment->patient->name }}</p>@endcan<p class="mt-1 text-xs text-text-muted">{{ $appointment->scheduled_at->format('Y-m-d') }}</p></div>
                                    <p class="text-sm text-text-muted">{{ $appointment->patientServicePlanItem?->service?->name ?? $appointment->sessionType?->name ?? 'خدمة غير محددة' }}</p>
                                    <div class="flex flex-wrap gap-2"><span class="rounded-full border border-surface-border px-2 py-1 text-xs text-text-muted">{{ $appointment->status }}</span>@if($appointment->therapySession)<span class="rounded-full bg-success-soft px-2 py-1 text-xs text-success">تم إتمام الخدمة</span>@elseif($appointment->checkin)<span class="rounded-full bg-primary-soft px-2 py-1 text-xs text-primary">تم تأكيد الحضور</span>@endif</div>
                                </div>
                            @empty
                                <p class="px-4 py-8 text-center text-sm text-text-muted">لا توجد مواعيد في هذا النطاق.</p>
                            @endforelse
                        </div>
                    </section>
                @endforeach
            @endif
        </section>

        <section x-show="tab === 'services'" x-cloak class="rounded-lg border border-surface-border bg-surface p-5">
            <div class="flex items-center justify-between gap-3">
                <div><h2 class="text-lg font-bold text-text">الخدمات والاستحقاقات</h2><p class="mt-1 text-sm text-text-muted">الخدمات المسندة وسعر الاستحقاق الحالي لكل خدمة.</p></div>
            </div>
            <div class="mt-4 overflow-x-auto rounded-lg border border-surface-border">
                <table class="w-full min-w-[560px] text-right text-sm">
                    <thead class="bg-surface-muted text-xs text-text-muted"><tr><th class="px-4 py-3">الخدمة</th><th class="px-4 py-3">التخصص</th><th class="px-4 py-3">الاستحقاق الحالي</th></tr></thead>
                    <tbody class="divide-y divide-surface-border">
                        @forelse($therapist->services as $service)
                            <tr><td class="px-4 py-3 font-medium text-text">{{ $service->name }}</td><td class="px-4 py-3 text-text-muted">{{ $service->specialty?->name }}</td><td class="px-4 py-3 font-semibold text-text" data-current-rate-service="{{ $service->id }}" data-current-rate-value="{{ $currentRates[$service->id]?->amount ?? 'unavailable' }}">{{ $currentRates[$service->id] !== null ? number_format((float) $currentRates[$service->id]->amount, 2).' ج.م' : 'غير محدد' }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-8 text-center text-text-muted">لا توجد خدمات مسندة.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div
                class="mt-5 border-t border-surface-border pt-5"
                x-data="{ editing: @js($initialTab === 'services' && $errors->any()) }"
            >
                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                    <div>
                        <h3 class="font-bold text-text">إدارة الخدمات والاستحقاقات</h3>
                        <p class="mt-1 text-sm text-text-muted">
                            افتح الإدارة فقط عند إضافة خدمة أو تغيير استحقاق، بينما تظل القيم الحالية ظاهرة بالأعلى.
                        </p>
                    </div>

                    @can('manage therapist services')
                        <button
                            type="button"
                            class="clinic-btn-primary"
                            @click="editing = !editing"
                        >
                            <span x-show="!editing">إدارة الخدمات والاستحقاقات</span>
                            <span x-show="editing" x-cloak>إغلاق الإدارة</span>
                        </button>
                    @else
                        <button
                            type="button"
                            class="clinic-btn-secondary"
                            @click="editing = !editing"
                        >
                            <span x-show="!editing">عرض سجل الاستحقاقات</span>
                            <span x-show="editing" x-cloak>إغلاق السجل</span>
                        </button>
                    @endcan
                </div>

                <div x-show="editing" x-cloak class="mt-5">
                    @include('hr.therapists._services-management')
                </div>
            </div>
        </section>

        <section x-show="tab === 'schedule'" x-cloak class="rounded-lg border border-surface-border bg-surface p-5" x-data="{ days: {{ Illuminate\Support\Js::from(old('periods') ? collect($schedule)->map(fn ($day) => ['weekday' => $day['weekday'], 'label' => $day['label'], 'periods' => collect(old('periods.'.$day['weekday'], []))->values()]) : $schedule) }} }">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <div><h2 class="text-lg font-bold text-text">جدول العمل الأسبوعي</h2><p class="mt-1 text-sm text-text-muted">الوقت بنظام 12 ساعة AM/PM، مع دعم أكثر من فترة في اليوم.</p></div>
                @can('manage therapists')<button type="submit" form="therapist-schedule-form" class="clinic-btn-primary">حفظ جدول العمل</button>@endcan
            </div>

            @if($errors->any())
                <div class="mt-4 rounded-lg border border-danger/30 bg-danger-soft px-4 py-3 text-sm text-danger">
                    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                </div>
            @endif

            @can('manage therapists')
                <form id="therapist-schedule-form" action="{{ route('therapists.work-schedule.update', $therapist) }}" method="POST" class="mt-4 divide-y divide-surface-border" data-schedule-editor>
                    @csrf
                    @method('PUT')
                    <template x-for="day in days" :key="day.weekday">
                        <div class="grid gap-3 py-3 sm:grid-cols-[110px_minmax(0,1fr)_auto] sm:items-start" data-schedule-day>
                            <h3 class="pt-2 font-semibold text-text" x-text="day.label"></h3>
                            <div>
                            <span x-show="!day.periods.length" class="inline-flex rounded-full bg-surface-muted px-3 py-1 text-sm text-text-muted" data-rest-day>راحة</span>
                            <div class="space-y-2" x-show="day.periods.length">
                                <template x-for="(period, index) in day.periods" :key="index">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <div
    dir="ltr"
    class="flex items-center gap-1.5"
    data-time12-control="start"
    x-data="{
        hour: '',
        minute: '00',
        meridiem: 'AM',

        load() {
            const raw = String(period.starts_at || '').slice(0, 5);
            const match = raw.match(/^([01]\d|2[0-3]):([0-5]\d)$/);

            if (!match) {
                this.hour = '';
                this.minute = '00';
                this.meridiem = 'AM';
                return;
            }

            const hour24 = Number(match[1]);

            this.hour = String(((hour24 + 11) % 12) + 1).padStart(2, '0');
            this.minute = match[2];
            this.meridiem = hour24 >= 12 ? 'PM' : 'AM';
        },

        sync() {
            if (
                !/^\d{1,2}$/.test(String(this.hour)) ||
                !/^\d{1,2}$/.test(String(this.minute))
            ) {
                period.starts_at = '';
                return;
            }

            let hour = Number(this.hour);
            const minute = Number(this.minute);

            if (hour < 1 || hour > 12 || minute < 0 || minute > 59) {
                period.starts_at = '';
                return;
            }

            hour = (hour % 12) + (this.meridiem === 'PM' ? 12 : 0);

            period.starts_at =
                String(hour).padStart(2, '0') +
                ':' +
                String(minute).padStart(2, '0');
        },

        normalizeHour() {
            const value = Number(this.hour);

            if (!Number.isInteger(value) || value < 1 || value > 12) {
                this.hour = '';
            } else {
                this.hour = String(value).padStart(2, '0');
            }

            this.sync();
        },

        normalizeMinute() {
            const value = Number(this.minute);

            if (!Number.isInteger(value) || value < 0 || value > 59) {
                this.minute = '';
            } else {
                this.minute = String(value).padStart(2, '0');
            }

            this.sync();
        },

        stepHour(delta) {
            let hour = Number(this.hour);

            if (!Number.isInteger(hour) || hour < 1 || hour > 12) {
                hour = 12;
            }

            if (delta > 0) {
                if (hour === 11) {
                    hour = 12;
                    this.meridiem = this.meridiem === 'AM' ? 'PM' : 'AM';
                } else if (hour === 12) {
                    hour = 1;
                } else {
                    hour++;
                }
            } else {
                if (hour === 12) {
                    hour = 11;
                    this.meridiem = this.meridiem === 'AM' ? 'PM' : 'AM';
                } else if (hour === 1) {
                    hour = 12;
                } else {
                    hour--;
                }
            }

            this.hour = String(hour).padStart(2, '0');
            this.sync();
        },

        stepMinute(delta) {
            this.sync();

            const raw = String(period.starts_at || '');

            if (!/^([01]\d|2[0-3]):([0-5]\d)$/.test(raw)) {
                this.minute = '00';
                this.sync();
                return;
            }

            let [hour24, minute] = raw.split(':').map(Number);

            let total = hour24 * 60 + minute + delta;
            total = ((total % 1440) + 1440) % 1440;

            hour24 = Math.floor(total / 60);
            minute = total % 60;

            this.hour = String(((hour24 + 11) % 12) + 1).padStart(2, '0');
            this.minute = String(minute).padStart(2, '0');
            this.meridiem = hour24 >= 12 ? 'PM' : 'AM';

            this.sync();
        }
    }"
    x-init="load()"
>
    <div class="inline-flex overflow-hidden rounded-lg border border-surface-border bg-surface">
        <input
            type="text"
            inputmode="numeric"
            maxlength="2"
            x-model="hour"
            @input="hour = hour.replace(/\D/g, '').slice(0, 2); sync()"
            @blur="normalizeHour()"
            class="w-12 border-0 bg-transparent px-2 text-center font-semibold text-text focus:ring-0"
            data-time-hour-input
            aria-label="الساعة"
        >

        <div class="flex w-7 flex-col border-l border-surface-border">
            <button
                type="button"
                class="flex h-5 items-center justify-center text-[9px] text-text-muted hover:bg-surface-muted hover:text-primary"
                @click="stepHour(1)"
                data-time-hour-up
                title="زيادة ساعة"
            >▲</button>

            <button
                type="button"
                class="flex h-5 items-center justify-center border-t border-surface-border text-[9px] text-text-muted hover:bg-surface-muted hover:text-primary"
                @click="stepHour(-1)"
                data-time-hour-down
                title="نقص ساعة"
            >▼</button>
        </div>
    </div>

    <span class="font-bold text-text-muted">:</span>

    <div class="inline-flex overflow-hidden rounded-lg border border-surface-border bg-surface">
        <input
            type="text"
            inputmode="numeric"
            maxlength="2"
            x-model="minute"
            @input="minute = minute.replace(/\D/g, '').slice(0, 2); sync()"
            @blur="normalizeMinute()"
            class="w-12 border-0 bg-transparent px-2 text-center font-semibold text-text focus:ring-0"
            data-time-minute-input
            aria-label="الدقائق"
        >

        <div class="flex w-7 flex-col border-l border-surface-border">
            <button
                type="button"
                class="flex h-5 items-center justify-center text-[9px] text-text-muted hover:bg-surface-muted hover:text-primary"
                @click="stepMinute(5)"
                data-time-minute-up
                data-time-step="5"
                title="زيادة 5 دقائق"
            >▲</button>

            <button
                type="button"
                class="flex h-5 items-center justify-center border-t border-surface-border text-[9px] text-text-muted hover:bg-surface-muted hover:text-primary"
                @click="stepMinute(-5)"
                data-time-minute-down
                data-time-step="5"
                title="نقص 5 دقائق"
            >▼</button>
        </div>
    </div>

    <div
        class="inline-flex shrink-0 rounded-lg border border-surface-border bg-surface-muted p-1"
        data-meridiem-toggle
    >
        <button
            type="button"
            class="rounded-md px-2 py-1 text-xs font-bold"
            :class="meridiem === 'AM' ? 'bg-primary text-white' : 'text-text-muted'"
            @click="meridiem = 'AM'; sync()"
        >AM</button>

        <button
            type="button"
            class="rounded-md px-2 py-1 text-xs font-bold"
            :class="meridiem === 'PM' ? 'bg-primary text-white' : 'text-text-muted'"
            @click="meridiem = 'PM'; sync()"
        >PM</button>
    </div>

    <input
        type="hidden"
        :name="`periods[${day.weekday}][${index}][starts_at]`"
        :value="period.starts_at"
    >
</div>
                                        <span class="text-text-muted">إلى</span>
                                        <div
    dir="ltr"
    class="flex items-center gap-1.5"
    data-time12-control="end"
    x-data="{
        hour: '',
        minute: '00',
        meridiem: 'AM',

        load() {
            const raw = String(period.ends_at || '').slice(0, 5);
            const match = raw.match(/^([01]\d|2[0-3]):([0-5]\d)$/);

            if (!match) {
                this.hour = '';
                this.minute = '00';
                this.meridiem = 'AM';
                return;
            }

            const hour24 = Number(match[1]);

            this.hour = String(((hour24 + 11) % 12) + 1).padStart(2, '0');
            this.minute = match[2];
            this.meridiem = hour24 >= 12 ? 'PM' : 'AM';
        },

        sync() {
            if (
                !/^\d{1,2}$/.test(String(this.hour)) ||
                !/^\d{1,2}$/.test(String(this.minute))
            ) {
                period.ends_at = '';
                return;
            }

            let hour = Number(this.hour);
            const minute = Number(this.minute);

            if (hour < 1 || hour > 12 || minute < 0 || minute > 59) {
                period.ends_at = '';
                return;
            }

            hour = (hour % 12) + (this.meridiem === 'PM' ? 12 : 0);

            period.ends_at =
                String(hour).padStart(2, '0') +
                ':' +
                String(minute).padStart(2, '0');
        },

        normalizeHour() {
            const value = Number(this.hour);

            if (!Number.isInteger(value) || value < 1 || value > 12) {
                this.hour = '';
            } else {
                this.hour = String(value).padStart(2, '0');
            }

            this.sync();
        },

        normalizeMinute() {
            const value = Number(this.minute);

            if (!Number.isInteger(value) || value < 0 || value > 59) {
                this.minute = '';
            } else {
                this.minute = String(value).padStart(2, '0');
            }

            this.sync();
        },

        stepHour(delta) {
            let hour = Number(this.hour);

            if (!Number.isInteger(hour) || hour < 1 || hour > 12) {
                hour = 12;
            }

            if (delta > 0) {
                if (hour === 11) {
                    hour = 12;
                    this.meridiem = this.meridiem === 'AM' ? 'PM' : 'AM';
                } else if (hour === 12) {
                    hour = 1;
                } else {
                    hour++;
                }
            } else {
                if (hour === 12) {
                    hour = 11;
                    this.meridiem = this.meridiem === 'AM' ? 'PM' : 'AM';
                } else if (hour === 1) {
                    hour = 12;
                } else {
                    hour--;
                }
            }

            this.hour = String(hour).padStart(2, '0');
            this.sync();
        },

        stepMinute(delta) {
            this.sync();

            const raw = String(period.ends_at || '');

            if (!/^([01]\d|2[0-3]):([0-5]\d)$/.test(raw)) {
                this.minute = '00';
                this.sync();
                return;
            }

            let [hour24, minute] = raw.split(':').map(Number);

            let total = hour24 * 60 + minute + delta;
            total = ((total % 1440) + 1440) % 1440;

            hour24 = Math.floor(total / 60);
            minute = total % 60;

            this.hour = String(((hour24 + 11) % 12) + 1).padStart(2, '0');
            this.minute = String(minute).padStart(2, '0');
            this.meridiem = hour24 >= 12 ? 'PM' : 'AM';

            this.sync();
        }
    }"
    x-init="load()"
>
    <div class="inline-flex overflow-hidden rounded-lg border border-surface-border bg-surface">
        <input
            type="text"
            inputmode="numeric"
            maxlength="2"
            x-model="hour"
            @input="hour = hour.replace(/\D/g, '').slice(0, 2); sync()"
            @blur="normalizeHour()"
            class="w-12 border-0 bg-transparent px-2 text-center font-semibold text-text focus:ring-0"
            data-time-hour-input
            aria-label="الساعة"
        >

        <div class="flex w-7 flex-col border-l border-surface-border">
            <button
                type="button"
                class="flex h-5 items-center justify-center text-[9px] text-text-muted hover:bg-surface-muted hover:text-primary"
                @click="stepHour(1)"
                data-time-hour-up
                title="زيادة ساعة"
            >▲</button>

            <button
                type="button"
                class="flex h-5 items-center justify-center border-t border-surface-border text-[9px] text-text-muted hover:bg-surface-muted hover:text-primary"
                @click="stepHour(-1)"
                data-time-hour-down
                title="نقص ساعة"
            >▼</button>
        </div>
    </div>

    <span class="font-bold text-text-muted">:</span>

    <div class="inline-flex overflow-hidden rounded-lg border border-surface-border bg-surface">
        <input
            type="text"
            inputmode="numeric"
            maxlength="2"
            x-model="minute"
            @input="minute = minute.replace(/\D/g, '').slice(0, 2); sync()"
            @blur="normalizeMinute()"
            class="w-12 border-0 bg-transparent px-2 text-center font-semibold text-text focus:ring-0"
            data-time-minute-input
            aria-label="الدقائق"
        >

        <div class="flex w-7 flex-col border-l border-surface-border">
            <button
                type="button"
                class="flex h-5 items-center justify-center text-[9px] text-text-muted hover:bg-surface-muted hover:text-primary"
                @click="stepMinute(5)"
                data-time-minute-up
                data-time-step="5"
                title="زيادة 5 دقائق"
            >▲</button>

            <button
                type="button"
                class="flex h-5 items-center justify-center border-t border-surface-border text-[9px] text-text-muted hover:bg-surface-muted hover:text-primary"
                @click="stepMinute(-5)"
                data-time-minute-down
                data-time-step="5"
                title="نقص 5 دقائق"
            >▼</button>
        </div>
    </div>

    <div
        class="inline-flex shrink-0 rounded-lg border border-surface-border bg-surface-muted p-1"
        data-meridiem-toggle
    >
        <button
            type="button"
            class="rounded-md px-2 py-1 text-xs font-bold"
            :class="meridiem === 'AM' ? 'bg-primary text-white' : 'text-text-muted'"
            @click="meridiem = 'AM'; sync()"
        >AM</button>

        <button
            type="button"
            class="rounded-md px-2 py-1 text-xs font-bold"
            :class="meridiem === 'PM' ? 'bg-primary text-white' : 'text-text-muted'"
            @click="meridiem = 'PM'; sync()"
        >PM</button>
    </div>

    <input
        type="hidden"
        :name="`periods[${day.weekday}][${index}][ends_at]`"
        :value="period.ends_at"
    >
</div>
                                        <button type="button" class="clinic-btn-danger-soft" @click="day.periods.splice(index, 1)">حذف</button>
                                    </div>
                                </template>
                            </div>
                            </div>
                            <button type="button" class="clinic-btn-secondary" @click="day.periods.push({ starts_at: '', ends_at: '' })">+ إضافة فترة</button>
                        </div>
                    </template>
                    <div class="flex justify-end pt-4"><button type="submit" class="clinic-btn-primary">حفظ جدول العمل</button></div>
                </form>
            @else
                <div class="mt-4 divide-y divide-surface-border">
                    <template x-for="day in days" :key="day.weekday"><div class="grid gap-2 py-3 sm:grid-cols-[110px_1fr]" data-schedule-day><h3 class="font-semibold text-text" x-text="day.label"></h3><template x-if="day.periods.length"><div class="flex flex-wrap gap-2 text-sm text-text-muted"><template x-for="period in day.periods"><p class="rounded-lg bg-surface-muted px-3 py-1"><span x-text="new Date('1970-01-01T' + period.starts_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true })"></span> — <span x-text="new Date('1970-01-01T' + period.ends_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true })"></span></p></template></div></template><p class="text-sm text-text-muted" x-show="!day.periods.length" data-rest-day>راحة</p></div></template>
                </div>
            @endcan
        </section>
    </div>
</x-app-layout>
