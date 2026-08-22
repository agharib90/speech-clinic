@php
    $workspaceMode = $workspaceMode ?? false;
    $embeddedWorkspace = $embeddedWorkspace ?? $workspaceMode;
    $planItems = $planItems ?? collect();
    $planOptions = $planItems->map(fn ($item) => [
        'id' => (string) $item->id,
        'label' => $item->service->name.' — خطة #'.$item->patient_service_plan_id,
        'unit_price' => $item->final_unit_price,
    ])->values();
    $invoiceMode = $workspaceMode ? old('invoice_item_mode', 'plan') : 'manual';
    $oldInvoiceItems = old('workspace_panel') === 'invoice' || ! $workspaceMode ? old('items', []) : [];
    $initialPlanItems = $invoiceMode === 'plan' && $oldInvoiceItems !== [] ? $oldInvoiceItems : [['patient_service_plan_item_id' => '', 'quantity' => 1]];
    $initialManualItems = $invoiceMode === 'manual' && $oldInvoiceItems !== [] ? $oldInvoiceItems : [['description' => '', 'quantity' => 1, 'unit_price' => 0]];
@endphp

<form action="{{ $formAction }}" method="POST" x-data="invoiceForm({{ Illuminate\Support\Js::from($planOptions) }}, {{ Illuminate\Support\Js::from($invoiceMode) }}, {{ Illuminate\Support\Js::from($initialPlanItems) }}, {{ Illuminate\Support\Js::from($initialManualItems) }})">
    @csrf
    @if($workspaceMode)
        <input type="hidden" name="workspace" value="1"><input type="hidden" name="workspace_panel" value="invoice"><input type="hidden" name="patient_id" value="{{ $patient->id }}"><input type="hidden" name="invoice_item_mode" :value="mode">
        <p class="mb-4 rounded-lg border border-primary/30 bg-primary-soft px-3 py-2 text-sm text-primary">إنشاء فاتورة للحالة: <strong>{{ $patient->name }}</strong></p>
    @else
        <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-3">
            <div><label class="mb-1 block text-sm font-medium text-text">المريض *</label><select name="patient_id" class="clinic-field w-full" required><option value="">اختر المريض</option>@foreach($patients as $patientOption)<option value="{{ $patientOption->id }}" @selected((int) old('patient_id', $selectedPatientId) === $patientOption->id)>{{ $patientOption->name }}</option>@endforeach</select></div>
            <div><label class="mb-1 block text-sm font-medium text-text">تاريخ الإصدار *</label><input type="date" name="issue_date" value="{{ old('issue_date', now()->toDateString()) }}" class="clinic-field w-full" required></div>
            <div><label class="mb-1 block text-sm font-medium text-text">تاريخ الاستحقاق</label><input type="date" name="due_date" value="{{ old('due_date') }}" class="clinic-field w-full"></div>
        </div>
    @endif

    @if($workspaceMode)
        <div class="mb-4 grid gap-4 sm:grid-cols-2"><label><span class="mb-1 block text-sm font-medium text-text">تاريخ الإصدار *</span><input type="date" name="issue_date" value="{{ old('issue_date', now()->toDateString()) }}" class="clinic-field w-full" required></label><label><span class="mb-1 block text-sm font-medium text-text">تاريخ الاستحقاق</span><input type="date" name="due_date" value="{{ old('due_date') }}" class="clinic-field w-full"></label></div>
        <div class="mb-4 inline-flex rounded-lg border border-surface-border bg-surface-muted p-1" aria-label="نوع بند الفاتورة"><button type="button" class="rounded-md px-3 py-2 text-sm font-medium" :class="mode === 'plan' ? 'bg-surface-elevated text-primary shadow-sm' : 'text-text-muted'" @click="mode = 'plan'">خدمة من خطة الحالة</button><button type="button" class="rounded-md px-3 py-2 text-sm font-medium" :class="mode === 'manual' ? 'bg-surface-elevated text-primary shadow-sm' : 'text-text-muted'" @click="mode = 'manual'">بند إضافي حر</button></div>
        <template x-if="mode === 'plan'">
            <div>
                <div class="overflow-x-auto"><table class="clinic-table min-w-[680px] text-right"><thead><tr><th>الخدمة</th><th class="w-24">الكمية</th><th class="w-40">سعر الوحدة</th><th class="w-32">الإجمالي</th><th class="w-12"></th></tr></thead><tbody><template x-for="(item, index) in planRows" :key="item.key"><tr>
                    <td><input type="hidden" :name="`items[${index}][mode]`" value="plan"><select x-model="item.patient_service_plan_item_id" :name="`items[${index}][patient_service_plan_item_id]`" class="clinic-field w-full" required><option value="">اختر خدمة من خطة الحالة</option><template x-for="option in planOptions" :key="option.id"><option :value="option.id" x-text="option.label"></option></template></select></td>
                    <td><input type="number" x-model.number="item.quantity" :name="`items[${index}][quantity]`" min="1" class="clinic-field w-full" required></td>
                    <td><p class="font-semibold text-text" x-text="selectedPlanItem(item) ? `${money(selectedPlanItem(item).unit_price)} ج.م` : '—'"></p><span class="text-xs text-text-subtle">من خطة الحالة</span></td>
                    <td class="font-semibold text-text" x-text="selectedPlanItem(item) ? money(Number(item.quantity || 0) * Number(selectedPlanItem(item).unit_price)) : '0.00'"></td>
                    <td><button type="button" @click="planRows.splice(index, 1)" x-show="planRows.length > 1" class="text-danger" aria-label="حذف البند" title="حذف البند">×</button></td>
                </tr></template></tbody></table></div>
                @if($planOptions->isEmpty())<p class="mt-3 text-sm text-warning">لا توجد خدمات صالحة للفوترة في خطط هذه الحالة. يمكنك إضافة بند حر أو إنشاء خطة خدمات أولًا.</p>@else<button type="button" @click="addPlanRow()" class="mt-3 text-sm font-semibold text-primary hover:text-primary-hover">+ إضافة خدمة من الخطة</button>@endif
            </div>
        </template>
    @endif

    <template x-if="mode === 'manual'">
        <div>
            <div class="overflow-x-auto"><table class="clinic-table min-w-[680px] text-right"><thead><tr><th>البند</th><th class="w-24">الكمية</th><th class="w-32">سعر الوحدة</th><th class="w-32">الإجمالي</th><th class="w-12"></th></tr></thead><tbody><template x-for="(item, index) in manualRows" :key="item.key"><tr>
                <td>@if($workspaceMode)<input type="hidden" :name="`items[${index}][mode]`" value="manual">@endif<input type="text" x-model="item.description" :name="`items[${index}][description]`" class="clinic-field w-full" required></td>
                <td><input type="number" x-model.number="item.quantity" :name="`items[${index}][quantity]`" min="1" class="clinic-field w-full" required></td><td><input type="number" x-model.number="item.unit_price" :name="`items[${index}][unit_price]`" step="0.01" min="0" class="clinic-field w-full" required></td><td class="font-semibold text-text" x-text="money(Number(item.quantity || 0) * Number(item.unit_price || 0))"></td><td><button type="button" @click="manualRows.splice(index, 1)" x-show="manualRows.length > 1" class="text-danger" aria-label="حذف البند" title="حذف البند">×</button></td>
            </tr></template></tbody></table></div><button type="button" @click="addManualRow()" class="mt-3 text-sm font-semibold text-primary hover:text-primary-hover">+ إضافة بند</button>
        </div>
    </template>

    @error('items.*')<p class="mt-3 text-sm text-danger">{{ $message }}</p>@enderror
    <label class="mt-4 block"><span class="mb-1 block text-sm font-medium text-text">ملاحظات</span><textarea name="notes" rows="2" class="clinic-field w-full">{{ old('notes') }}</textarea></label>
    <div class="mt-5 flex justify-end gap-2">
        @if($workspaceMode)@if($embeddedWorkspace)<button type="button" class="clinic-btn-secondary" @click="panel = null">إلغاء</button>@else<a href="{{ route('patients.workspace', $patient) }}" class="clinic-btn-secondary">إلغاء</a>@endif @else<a href="{{ route('invoices.index') }}" class="clinic-btn-secondary">إلغاء</a>@endif
        <button type="submit" class="clinic-btn-primary" :disabled="mode === 'plan' && planOptions.length === 0">حفظ الفاتورة</button>
    </div>
</form>

<script>
    function invoiceForm(planOptions, mode, planRows, manualRows) {
        let nextKey = 1;
        const keyed = (rows) => rows.map((row) => ({ ...row, key: nextKey++, patient_service_plan_item_id: String(row.patient_service_plan_item_id || '') }));

        return {
            planOptions,
            mode,
            planRows: keyed(planRows),
            manualRows: keyed(manualRows),
            selectedPlanItem(item) { return this.planOptions.find((option) => String(option.id) === String(item.patient_service_plan_item_id)); },
            addPlanRow() { this.planRows.push({ key: nextKey++, patient_service_plan_item_id: '', quantity: 1 }); },
            addManualRow() { this.manualRows.push({ key: nextKey++, description: '', quantity: 1, unit_price: 0 }); },
            money(value) { const number = Number(value); return (Number.isFinite(number) ? number : 0).toFixed(2); },
        };
    }
</script>
