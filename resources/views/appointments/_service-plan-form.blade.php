@php
    $workspaceMode = $workspaceMode ?? false;
    $fixedPatientId = $workspaceMode ? (string) $patient->id : (string) old('patient_id', $selectedPatientId ?? '');
@endphp

<form action="{{ $formAction }}" method="POST" x-data="{ items: {{ Illuminate\Support\Js::from($servicePlanItems) }}, patientId: {{ Illuminate\Support\Js::from($fixedPatientId) }}, itemId: {{ Illuminate\Support\Js::from((string) old('patient_service_plan_item_id', '')) }}, therapistId: {{ Illuminate\Support\Js::from((string) old('therapist_id', '')) }}, get patientItems() { return this.items.filter(item => String(item.patient_id) === String(this.patientId)); }, get selectedItem() { return this.items.find(item => String(item.id) === String(this.itemId)); }, resetItem() { this.itemId = ''; this.therapistId = ''; } }">
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
            <select name="patient_service_plan_item_id" x-model="itemId" @change="therapistId = ''" class="clinic-field w-full" required>
                <option value="">اختر خدمة ممولة...</option>
                <template x-for="item in patientItems" :key="item.id"><option :value="item.id" :disabled="!item.can_book" x-text="`${item.service_name} — ${item.booking_state}`"></option></template>
            </select>
            @error('patient_service_plan_item_id')<span class="text-xs text-danger">{{ $message }}</span>@enderror
        </div>

        <div x-show="selectedItem" x-cloak class="rounded-lg border border-surface-border bg-surface-muted p-3 text-sm">
            <p class="font-semibold text-text" x-text="selectedItem?.service_name"></p>
            <p class="text-xs text-text-muted" x-text="selectedItem?.specialty_name"></p>
            <div class="mt-2 grid grid-cols-2 gap-2 text-xs">
                <div><span class="text-text-muted">السعر النهائي</span><p class="font-semibold text-text" x-text="`${selectedItem?.final_unit_price} ج.م`"></p></div>
                <div><span class="text-text-muted">المقدم المطلوب</span><p class="font-semibold text-primary" x-text="`${selectedItem?.required_deposit} ج.م`"></p></div>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-text">الأخصائي المؤهل *</label>
            <select name="therapist_id" x-model="therapistId" class="clinic-field w-full" required><option value="">اختر الأخصائي...</option><template x-for="therapist in (selectedItem?.therapists || [])" :key="therapist.user_id"><option :value="therapist.user_id" x-text="therapist.name"></option></template></select>
            @error('therapist_id')<span class="text-xs text-danger">{{ $message }}</span>@enderror
        </div>

        <label><span class="mb-1 block text-sm font-medium text-text">موعد البدء *</span><input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}" class="clinic-field w-full" required></label>
        @error('scheduled_at')<span class="text-xs text-danger">{{ $message }}</span>@enderror

        <label><span class="mb-1 block text-sm font-medium text-text">ملاحظات</span><textarea name="notes" rows="2" class="clinic-field w-full">{{ old('notes') }}</textarea></label>

        <div class="flex justify-end gap-2">
            @if($workspaceMode)<button type="button" class="clinic-btn-secondary" @click="panel = null">إلغاء</button>@endif
            <button type="submit" class="clinic-btn-primary" :disabled="!selectedItem?.can_book">حجز وتأكيد الموعد</button>
        </div>
    </div>
</form>
