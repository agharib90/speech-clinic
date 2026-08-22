@php
    $isEdit = isset($patientServicePlan);
    $canFullyEdit = $canFullyEdit ?? true;
    $workspaceMode = $workspaceMode ?? false;
    $canManageDiscounts = auth()->user()->can('manage patient discounts');
    $patient = $isEdit ? $patientServicePlan->patient : $patient;
    $fromWorkspace = $fromWorkspace ?? false;
    $serviceOptions = $services->map(fn ($service) => [
        'id' => (string) $service->id,
        'name' => $service->name,
        'specialty' => $service->specialty->name,
        'customer_price' => $service->customer_price,
        'billable' => $service->customer_price !== null && (float) $service->customer_price > 0,
    ])->values();
@endphp

<div class="{{ $workspaceMode ? 'space-y-5' : 'mx-auto max-w-6xl space-y-5' }}">
    @unless($workspaceMode)
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <div>
            <p class="text-sm font-medium text-primary">{{ $patient->name }}</p>
            <h1 class="mt-1 text-2xl font-bold text-text">{{ $isEdit ? 'تعديل خطة الخدمات' : 'خطة خدمات جديدة' }}</h1>
            <p class="mt-1 text-sm text-text-muted">رتّب الخدمات حسب تسلسل تقديمها، وسيظل الحفظ النهائي محسوبًا على الخادم.</p>
        </div>
        <a href="{{ $fromWorkspace ? route('patients.workspace', $patient) : ($isEdit ? route('patient-service-plans.show', $patientServicePlan) : route('patients.service-plans.index', $patient)) }}" class="clinic-btn-secondary">{{ $fromWorkspace ? 'رجوع إلى ملف الحالة' : 'رجوع' }}</a>
    </div>
    @endunless

    @if($errors->any())
        <div class="rounded-lg border border-danger bg-danger-soft px-4 py-3 text-sm text-danger" role="alert">
            {{ $errors->first('plan') ?: 'راجع الحقول الموضحة ثم حاول مرة أخرى.' }}
        </div>
    @endif

    @if(! $canFullyEdit)
        <div class="rounded-lg border border-warning bg-warning-soft px-4 py-3 text-sm text-warning">
            <p class="font-semibold">بيانات الخطة المالية محمية</p>
            <p class="mt-1">لا يمكن تعديل الخدمات أو الأسعار أو الترتيب بعد تسجيل دفعة أو استخدام خدمة. يمكنك تحديث الملاحظات فقط.</p>
        </div>

        <form method="POST" action="{{ $fromWorkspace ? URL::signedRoute('patient-service-plans.update', ['patientServicePlan' => $patientServicePlan, 'workspace_patient' => $patient->id]) : route('patient-service-plans.update', $patientServicePlan) }}" class="clinic-card overflow-hidden">
            @csrf
            @method('PUT')
            @if($fromWorkspace)<input type="hidden" name="workspace" value="1">@endif

            <div class="border-b border-surface-border p-5">
                <h2 class="font-bold text-text">بيانات الخطة المحفوظة</h2>
                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-3">
                    <div><dt class="text-text-muted">الحالة</dt><dd class="mt-1"><x-plan-status-badge :status="$patientServicePlan->status" /></dd></div>
                    <div><dt class="text-text-muted">تاريخ البداية</dt><dd class="mt-1 font-medium text-text">{{ $patientServicePlan->starts_at?->format('Y-m-d') ?? 'غير محدد' }}</dd></div>
                    <div><dt class="text-text-muted">تاريخ النهاية</dt><dd class="mt-1 font-medium text-text">{{ $patientServicePlan->ends_at?->format('Y-m-d') ?? 'غير محدد' }}</dd></div>
                </dl>
            </div>

            <div class="border-b border-surface-border p-5">
                <h2 class="font-bold text-text">الخدمات</h2>
                <div class="mt-4 divide-y divide-surface-border rounded-lg border border-surface-border">
                    @foreach($patientServicePlan->items as $item)
                        <div class="grid gap-3 p-4 text-sm sm:grid-cols-[minmax(0,1fr)_repeat(4,minmax(5rem,auto))] sm:items-center">
                            <div><p class="font-semibold text-text">{{ $item->position }}. {{ $item->service->name }}</p><p class="text-xs text-text-muted">{{ $item->service->specialty->name }}</p></div>
                            <div><p class="text-xs text-text-muted">الكمية</p><p class="font-medium text-text">{{ $item->planned_quantity }}</p></div>
                            <div><p class="text-xs text-text-muted">سعر الوحدة</p><p class="font-medium text-text">{{ number_format((float) $item->customer_unit_price, 2) }}</p></div>
                            <div><p class="text-xs text-text-muted">الخصم</p><p class="font-medium text-text">{{ number_format((float) $item->discount_amount, 2) }}</p></div>
                            <div><p class="text-xs text-text-muted">بعد الخصم</p><p class="font-semibold text-primary">{{ number_format((float) $item->final_unit_price, 2) }}</p></div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="p-5">
                <label for="notes" class="mb-1 block text-sm font-medium text-text">الملاحظات</label>
                <textarea id="notes" name="notes" rows="4" class="clinic-field w-full">{{ old('notes', $patientServicePlan->notes) }}</textarea>
                @error('notes')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
            </div>

            <div class="flex justify-end border-t border-surface-border bg-surface-muted px-5 py-4">
                <button class="clinic-btn-primary">حفظ الملاحظات</button>
            </div>
        </form>
    @else
        <form method="POST" action="{{ $workspaceMode ? URL::signedRoute('patients.service-plans.store', ['patient' => $patient, 'workspace_patient' => $patient->id]) : ($isEdit ? ($fromWorkspace ? URL::signedRoute('patient-service-plans.update', ['patientServicePlan' => $patientServicePlan, 'workspace_patient' => $patient->id]) : route('patient-service-plans.update', $patientServicePlan)) : route('patients.service-plans.store', $patient)) }}" class="{{ $workspaceMode ? 'overflow-hidden rounded-lg border border-surface-border' : 'clinic-card overflow-hidden' }}" data-initial-service-ids="{{ collect($formItems)->pluck('service_id')->filter()->implode(',') }}" x-data="servicePlanForm({{ Illuminate\Support\Js::from($serviceOptions) }}, {{ Illuminate\Support\Js::from($formItems) }})">
            @csrf
            @if($isEdit) @method('PUT') @endif
            @if($workspaceMode)
                <input type="hidden" name="workspace" value="1">
                <input type="hidden" name="workspace_panel" value="plan">
            @elseif($fromWorkspace)
                <input type="hidden" name="workspace" value="1">
            @endif

            <section class="border-b border-surface-border p-5">
                <div class="mb-4"><h2 class="font-bold text-text">معلومات الخطة</h2><p class="mt-1 text-sm text-text-muted">الفترة المتوقعة قابلة للتعديل قبل وجود سجل مالي أو استخدام.</p></div>
                <div class="grid gap-4 md:grid-cols-2">
                    <label for="starts_at"><span class="mb-1 block text-sm font-medium text-text">تاريخ البداية</span><input id="starts_at" type="date" name="starts_at" value="{{ old('starts_at', $isEdit ? $patientServicePlan->starts_at?->format('Y-m-d') : '') }}" class="clinic-field w-full"></label>
                    <label for="ends_at"><span class="mb-1 block text-sm font-medium text-text">تاريخ النهاية المتوقع</span><input id="ends_at" type="date" name="ends_at" value="{{ old('ends_at', $isEdit ? $patientServicePlan->ends_at?->format('Y-m-d') : '') }}" class="clinic-field w-full"></label>
                </div>
                @error('starts_at')<p class="mt-2 text-sm text-danger">{{ $message }}</p>@enderror
                @error('ends_at')<p class="mt-2 text-sm text-danger">{{ $message }}</p>@enderror
            </section>

            <section class="border-b border-surface-border p-5">
                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                    <div><h2 class="font-bold text-text">الخدمات</h2><p class="mt-1 text-sm text-text-muted">استخدم الأسهم لتحديد ترتيب التخصيص والدفع.</p></div>
                    <button type="button" class="clinic-btn-secondary text-primary" @click="add()">إضافة خدمة</button>
                </div>

                <div class="mt-4 divide-y divide-surface-border rounded-lg border border-surface-border">
                    <template x-for="(item, index) in items" :key="item.key">
                        <div class="p-4 transition hover:bg-primary-soft">
                            <input type="hidden" :name="`items[${index}][id]`" :value="item.id || ''">
                            <input type="hidden" :name="`items[${index}][position]`" :value="index + 1">
                            <input type="hidden" :name="`items[${index}][original_service_id]`" :value="item.original_service_id || ''">

                            <div class="grid gap-4 lg:grid-cols-[5rem_minmax(13rem,1.6fr)_repeat(4,minmax(7rem,1fr))_5rem] lg:items-end">
                                <div>
                                    <span class="mb-1 block text-xs font-medium text-text-muted">الترتيب</span>
                                    <div class="flex items-center gap-1">
                                        <button type="button" class="flex h-9 w-9 items-center justify-center rounded-lg border border-surface-border text-text hover:bg-surface-muted disabled:opacity-30" :disabled="index === 0" @click="move(index, -1)" title="تحريك لأعلى" aria-label="تحريك الخدمة لأعلى">↑</button>
                                        <button type="button" class="flex h-9 w-9 items-center justify-center rounded-lg border border-surface-border text-text hover:bg-surface-muted disabled:opacity-30" :disabled="index === items.length - 1" @click="move(index, 1)" title="تحريك لأسفل" aria-label="تحريك الخدمة لأسفل">↓</button>
                                    </div>
                                </div>

                                <label><span class="mb-1 block text-xs font-medium text-text-muted">الخدمة</span><select class="clinic-field w-full" :name="`items[${index}][service_id]`" x-model="item.service_id" @change="syncPrice(item)" required><option value="">اختر الخدمة</option><template x-for="service in services" :key="service.id"><option :value="service.id" :disabled="!service.billable && String(item.original_service_id) !== String(service.id)" x-text="`${service.specialty} — ${service.name}${service.billable ? '' : ' — السعر غير معتمد'}`"></option></template></select></label>
                                <label><span class="mb-1 block text-xs font-medium text-text-muted">الكمية</span><input type="number" min="1" class="clinic-field w-full text-left" :name="`items[${index}][planned_quantity]`" x-model="item.quantity" required></label>
                                <div><span class="mb-1 block text-xs font-medium text-text-muted">السعر الرسمي</span><p class="flex h-10 items-center rounded-lg bg-surface-muted px-3 text-sm font-semibold text-text" x-text="item.price === '' ? 'غير معتمد' : `${money(item.price)} ج.م`"></p><span class="mt-1 block text-[11px] text-text-subtle" x-text="item.id && String(item.service_id) === String(item.original_service_id) ? 'سعر محفوظ وقت إضافة الخدمة' : 'من التسعيرة المعتمدة'"></span></div>

                                @if($canManageDiscounts)
                                    <label><span class="mb-1 block text-xs font-medium text-text-muted">الخصم للوحدة</span><input type="number" min="0" step="0.01" class="clinic-field w-full text-left" :name="`items[${index}][discount_amount]`" x-model="item.discount"></label>
                                @else
                                    <div><span class="mb-1 block text-xs font-medium text-text-muted">الخصم للوحدة</span><p class="flex h-10 items-center rounded-lg bg-surface-muted px-3 text-sm text-text" x-text="money(item.discount)"></p></div>
                                @endif

                                <div><span class="mb-1 block text-xs font-medium text-text-muted">بعد الخصم</span><p class="flex h-10 items-center font-semibold text-primary" x-text="money(finalPrice(item))"></p></div>
                                <button type="button" class="flex h-10 w-10 items-center justify-center rounded-lg text-danger hover:bg-danger-soft disabled:opacity-30" :disabled="items.length === 1" @click="remove(index)" title="إزالة الخدمة" aria-label="إزالة الخدمة">×</button>
                            </div>

                            <div class="mt-3 flex justify-between border-t border-surface-border pt-3 text-xs"><span class="text-text-muted" x-text="`البند ${index + 1}`"></span><span class="font-semibold text-text" x-text="`إجمالي البند: ${money(rowTotal(item))} ج.م`"></span></div>
                        </div>
                    </template>
                </div>
                @error('items')<p class="mt-2 text-sm text-danger">{{ $message }}</p>@enderror
                @error('items.*')<p class="mt-2 text-sm text-danger">{{ $message }}</p>@enderror
            </section>

            <section class="p-5">
                <label for="notes"><span class="mb-1 block text-sm font-medium text-text">الملاحظات</span><textarea id="notes" name="notes" rows="4" class="clinic-field w-full">{{ old('notes', $isEdit ? $patientServicePlan->notes : '') }}</textarea></label>
                @error('notes')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
            </section>

            <div class="flex flex-col justify-between gap-3 border-t border-surface-border bg-surface-muted px-5 py-4 sm:flex-row sm:items-center">
                <div><p class="text-xs text-text-muted">الإجمالي التقريبي</p><p class="text-xl font-bold text-text"><span x-text="money(planTotal())"></span> ج.م</p></div>
                <div class="flex gap-2">@if($workspaceMode)<button type="button" class="clinic-btn-secondary" @click="panel = null">إلغاء</button>@else<a href="{{ $fromWorkspace ? route('patients.workspace', $patient) : ($isEdit ? route('patient-service-plans.show', $patientServicePlan) : route('patients.service-plans.index', $patient)) }}" class="clinic-btn-secondary">إلغاء</a>@endif<button class="clinic-btn-primary">{{ $isEdit ? 'حفظ التعديلات' : 'حفظ كمسودة' }}</button></div>
            </div>
        </form>
    @endif
</div>

@if($canFullyEdit)
    <script>
        function servicePlanForm(services, initialItems) {
            return {
                services,
                nextKey: initialItems.length + 1,
                items: initialItems.map((item, index) => ({ key: index + 1, id: item.id || null, service_id: String(item.service_id || ''), original_service_id: String(item.original_service_id || item.service_id || ''), snapshot_price: item.customer_unit_price || '', quantity: Number(item.planned_quantity || 1), price: item.customer_unit_price || '', discount: item.discount_amount || 0 })),
                init() { this.items.forEach((item) => this.syncPrice(item)); },
                add() { this.items.push({ key: this.nextKey++, id: null, service_id: '', original_service_id: '', snapshot_price: '', quantity: 1, price: '', discount: 0 }); },
                remove(index) { if (this.items.length > 1) this.items.splice(index, 1); },
                move(index, direction) { const target = index + direction; if (target < 0 || target >= this.items.length) return; [this.items[index], this.items[target]] = [this.items[target], this.items[index]]; },
                number(value) { const parsed = Number(value); return Number.isFinite(parsed) ? parsed : 0; },
                syncPrice(item) { if (item.id && String(item.service_id) === String(item.original_service_id)) { item.price = item.snapshot_price; return; } const service = this.services.find((option) => String(option.id) === String(item.service_id)); item.price = service?.billable ? service.customer_price : ''; },
                finalPrice(item) { return Math.max(0, this.number(item.price) - this.number(item.discount)); },
                rowTotal(item) { return this.finalPrice(item) * Math.max(0, this.number(item.quantity)); },
                planTotal() { return this.items.reduce((total, item) => total + this.rowTotal(item), 0); },
                money(value) { return this.number(value).toLocaleString('ar-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            };
        }
    </script>
@endif
