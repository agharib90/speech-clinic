<x-app-layout>
    <x-slot name="title">ملف الأخصائي</x-slot>

    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <p class="text-xs font-semibold text-primary">ملف الأخصائي</p>
                <h1 class="mt-1 text-2xl font-bold text-text">{{ $therapist->name }}</h1>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('manage therapist services')
                    <a href="{{ route('therapists.services.edit', $therapist) }}" class="clinic-btn-primary">الخدمات والاستحقاقات</a>
                @endcan
                <a href="{{ route('therapists.index') }}" class="clinic-btn-secondary">رجوع إلى الفريق</a>
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-lg border border-success/30 bg-success-soft px-4 py-3 text-sm text-success">{{ session('success') }}</div>
        @endif

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

        <section class="rounded-lg border border-surface-border bg-surface p-5">
            <div class="flex items-center justify-between gap-3">
                <div><h2 class="text-lg font-bold text-text">الخدمات والاستحقاقات</h2><p class="mt-1 text-sm text-text-muted">الخدمات المسندة وسعر الاستحقاق الحالي لكل خدمة.</p></div>
            </div>
            <div class="mt-4 overflow-x-auto rounded-lg border border-surface-border">
                <table class="w-full min-w-[560px] text-right text-sm">
                    <thead class="bg-surface-muted text-xs text-text-muted"><tr><th class="px-4 py-3">الخدمة</th><th class="px-4 py-3">التخصص</th><th class="px-4 py-3">الاستحقاق الحالي</th></tr></thead>
                    <tbody class="divide-y divide-surface-border">
                        @forelse($therapist->services as $service)
                            <tr><td class="px-4 py-3 font-medium text-text">{{ $service->name }}</td><td class="px-4 py-3 text-text-muted">{{ $service->specialty?->name }}</td><td class="px-4 py-3 font-semibold text-text">{{ $currentRates[$service->id] !== null ? number_format((float) $currentRates[$service->id], 2).' ج.م' : 'غير محدد' }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-8 text-center text-text-muted">لا توجد خدمات مسندة.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-lg border border-surface-border bg-surface p-5" x-data="{ days: {{ Illuminate\Support\Js::from(old('periods') ? collect($schedule)->map(fn ($day) => ['weekday' => $day['weekday'], 'label' => $day['label'], 'periods' => collect(old('periods.'.$day['weekday'], []))->values()]) : $schedule) }} }">
            <div><h2 class="text-lg font-bold text-text">جدول العمل الأسبوعي</h2><p class="mt-1 text-sm text-text-muted">يمكن إضافة أكثر من فترة في اليوم نفسه، وترك اليوم دون فترات يعني أنه يوم راحة.</p></div>

            @if($errors->any())
                <div class="mt-4 rounded-lg border border-danger/30 bg-danger-soft px-4 py-3 text-sm text-danger">
                    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                </div>
            @endif

            @can('manage therapists')
                <form action="{{ route('therapists.work-schedule.update', $therapist) }}" method="POST" class="mt-5 space-y-4">
                    @csrf
                    @method('PUT')
                    <template x-for="day in days" :key="day.weekday">
                        <div class="rounded-lg border border-surface-border p-4">
                            <div class="flex items-center justify-between gap-3"><h3 class="font-semibold text-text" x-text="day.label"></h3><button type="button" class="clinic-btn-secondary" @click="day.periods.push({ starts_at: '', ends_at: '' })">إضافة فترة</button></div>
                            <div class="mt-3 space-y-2" x-show="day.periods.length">
                                <template x-for="(period, index) in day.periods" :key="index">
                                    <div class="grid grid-cols-[1fr_auto_1fr_auto] items-center gap-2">
                                        <input type="time" :name="`periods[${day.weekday}][${index}][starts_at]`" x-model="period.starts_at" class="clinic-field w-full" required>
                                        <span class="text-text-muted">إلى</span>
                                        <input type="time" :name="`periods[${day.weekday}][${index}][ends_at]`" x-model="period.ends_at" class="clinic-field w-full" required>
                                        <button type="button" class="clinic-btn-danger-soft" @click="day.periods.splice(index, 1)">حذف</button>
                                    </div>
                                </template>
                            </div>
                            <p class="mt-3 text-sm text-text-muted" x-show="!day.periods.length">راحة</p>
                        </div>
                    </template>
                    <div class="flex justify-end"><button type="submit" class="clinic-btn-primary">حفظ جدول العمل</button></div>
                </form>
            @else
                <div class="mt-5 grid gap-3 md:grid-cols-2">
                    <template x-for="day in days" :key="day.weekday"><div class="rounded-lg border border-surface-border p-4"><h3 class="font-semibold text-text" x-text="day.label"></h3><template x-if="day.periods.length"><div class="mt-2 space-y-1 text-sm text-text-muted"><template x-for="period in day.periods"><p><span x-text="period.starts_at"></span> إلى <span x-text="period.ends_at"></span></p></template></div></template><p class="mt-2 text-sm text-text-muted" x-show="!day.periods.length">راحة</p></div></template>
                </div>
            @endcan
        </section>
    </div>
</x-app-layout>
