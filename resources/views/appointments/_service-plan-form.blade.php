@php
    $workspaceMode = $workspaceMode ?? false;
    $fixedPatientId = $workspaceMode ? (string) $patient->id : (string) old('patient_id', $selectedPatientId ?? '');
    $oldScheduledAt = (string) old('scheduled_at', '');
@endphp

<form action="{{ $formAction }}" method="POST" x-data="appointmentAvailability({
    items: {{ Illuminate\Support\Js::from($servicePlanItems) }},
    patientId: {{ Illuminate\Support\Js::from($fixedPatientId) }},
    itemId: {{ Illuminate\Support\Js::from((string) old('patient_service_plan_item_id', '')) }},
    therapistId: {{ Illuminate\Support\Js::from((string) old('therapist_id', '')) }},
    date: {{ Illuminate\Support\Js::from($oldScheduledAt ? substr($oldScheduledAt, 0, 10) : '') }},
    scheduledAt: {{ Illuminate\Support\Js::from($oldScheduledAt) }},
    availabilityUrl: {{ Illuminate\Support\Js::from(route('appointments.availability')) }},
})">
    @csrf
    @if($workspaceMode)
        <input type="hidden" name="workspace" value="1">
        <input type="hidden" name="workspace_panel" value="appointment">
        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
        <p class="mb-4 rounded-lg border border-primary/30 bg-primary-soft px-3 py-2 text-sm text-primary">حجز موعد للحالة: <strong>{{ $patient->name }}</strong></p>
    @endif

    <div class="space-y-4">
        @unless($workspaceMode)
            <div>
                <label class="mb-1 block text-sm font-medium text-text">المريض *</label>
                <select name="patient_id" x-model="patientId" @change="resetItem()" class="clinic-field w-full" required>
                    <option value="">اختر المريض...</option>
                    @foreach($patients as $patientOption)
                        <option value="{{ $patientOption->id }}">{{ $patientOption->name }}</option>
                    @endforeach
                </select>
                @error('patient_id')<span class="text-xs text-danger">{{ $message }}</span>@enderror
            </div>
        @endunless

        <div>
            <label class="mb-1 block text-sm font-medium text-text">الخدمة من الخطة *</label>
            <select name="patient_service_plan_item_id" x-model="itemId" @change="resetTherapist()" class="clinic-field w-full" required>
                <option value="">اختر خدمة ممولة...</option>
                <template x-for="item in patientItems" :key="item.id"><option :value="item.id" :disabled="!item.can_book || !item.duration_minutes" x-text="`${item.service_name} — ${item.duration_minutes ? item.booking_state : 'مدة الخدمة غير محددة'}`"></option></template>
            </select>
            @error('patient_service_plan_item_id')<span class="text-xs text-danger">{{ $message }}</span>@enderror
        </div>

        <div x-show="selectedItem" x-cloak class="rounded-lg border border-surface-border bg-surface-muted p-3 text-sm">
            <p class="font-semibold text-text" x-text="selectedItem?.service_name"></p>
            <p class="text-xs text-text-muted" x-text="selectedItem?.specialty_name"></p>
            <div class="mt-2 grid grid-cols-2 gap-2 text-xs">
                <div><span class="text-text-muted">مدة الخدمة</span><p class="font-semibold text-text" x-text="selectedItem?.duration_minutes ? `${selectedItem.duration_minutes} دقيقة` : 'غير محددة'"></p></div>
                <div><span class="text-text-muted">المقدم المطلوب</span><p class="font-semibold text-primary" x-text="`${selectedItem?.required_deposit} ج.م`"></p></div>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-text">الأخصائي المؤهل *</label>
            <select name="therapist_id" x-model="therapistId" @change="date = ''; resetAvailability()" class="clinic-field w-full" required><option value="">اختر الأخصائي...</option><template x-for="therapist in (selectedItem?.therapists || [])" :key="therapist.user_id"><option :value="therapist.user_id" x-text="therapist.name"></option></template></select>
            @error('therapist_id')<span class="text-xs text-danger">{{ $message }}</span>@enderror
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-text">التاريخ *</label>
            <input type="date" x-model="date" @change="loadAvailability()" min="{{ today()->toDateString() }}" class="clinic-field w-full" :disabled="!therapistId" required>
        </div>

        <input type="hidden" name="scheduled_at" :value="scheduledAt">
        @error('scheduled_at')<span class="text-xs text-danger">{{ $message }}</span>@enderror

        <p x-show="loading" x-cloak class="rounded-lg bg-surface-muted px-3 py-3 text-sm text-text-muted">جارٍ تحميل المواعيد المتاحة...</p>
        <p x-show="error" x-cloak class="rounded-lg border border-danger/30 bg-danger-soft px-3 py-3 text-sm text-danger" x-text="error"></p>

        <div x-show="availability && !loading" x-cloak class="space-y-4">
            <div>
                <p class="text-sm font-semibold text-text">ساعات عمل الأخصائي اليوم</p>
                <div class="mt-2 flex flex-wrap gap-2" x-show="availability?.work_periods.length">
                    <template x-for="period in availability?.work_periods || []" :key="`${period.start}-${period.end}`"><span class="rounded-full border border-surface-border bg-surface-muted px-3 py-1 text-xs text-text" x-text="`${formatTime(period.start)} – ${formatTime(period.end)}`"></span></template>
                </div>
                <p class="mt-2 text-sm text-warning" x-show="availability && !availability.work_periods.length">لا توجد فترات عمل لهذا الأخصائي في هذا اليوم.</p>
            </div>

            <div x-show="availability?.work_periods.length">
                <p class="text-sm font-semibold text-text">الفترات المتاحة</p>
                <div class="mt-2 space-y-3" x-show="bookableWindows.length">
                    <template x-for="window in bookableWindows" :key="`${window.start}-${window.end}`">
                        <div class="rounded-lg border border-surface-border p-3">
                            <div class="flex flex-wrap items-center justify-between gap-2"><p class="font-semibold text-text" x-text="`${formatTime(window.start)} – ${formatTime(window.end)}`"></p><span class="text-xs text-text-muted" x-text="`مدة متاحة: ${formatDuration(window.duration_minutes)}`"></span></div>
                            <div class="mt-3 flex flex-wrap gap-2"><template x-for="time in window.start_times" :key="time"><button type="button" class="clinic-btn-secondary" :class="scheduledAt === `${date}T${time}` ? '!border-primary !bg-primary-soft !text-primary' : ''" @click="selectStart(time)" x-text="formatTime(time)"></button></template></div>
                        </div>
                    </template>
                </div>
                <p class="mt-2 rounded-lg bg-warning-soft px-3 py-3 text-sm text-warning" x-show="availability && !bookableWindows.length">لا توجد مواعيد متاحة تناسب مدة هذه الخدمة في هذا اليوم.</p>
            </div>

            <p x-show="scheduledAt" class="rounded-lg border border-success/30 bg-success-soft px-3 py-2 text-sm font-medium text-success">تم اختيار موعد يبدأ في <span x-text="formatTime(scheduledAt.slice(11, 16))"></span></p>
        </div>

        <label><span class="mb-1 block text-sm font-medium text-text">ملاحظات</span><textarea name="notes" rows="2" class="clinic-field w-full">{{ old('notes') }}</textarea></label>

        <div class="flex justify-end gap-2">
            @if($workspaceMode)<button type="button" class="clinic-btn-secondary" @click="panel = null">إلغاء</button>@endif
            <button type="submit" class="clinic-btn-primary" :disabled="!selectedItem?.can_book || !scheduledAt">حجز وتأكيد الموعد</button>
        </div>
    </div>
</form>
