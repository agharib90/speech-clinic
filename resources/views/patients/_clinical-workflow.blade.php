@php
    $clinicalItems = old('workspace_panel') === 'clinical-plan'
        ? old('items', [])
        : ($clinicalPlan?->items->map(fn ($item) => [
            'service_id' => (string) $item->service_id,
            'planned_quantity' => $item->planned_quantity,
        ])->values()->all() ?? []);
    if ($clinicalItems === []) {
        $clinicalItems = [['service_id' => '', 'planned_quantity' => 1]];
    }
    $clinicalActivityKey = $clinical['secondary_key'] ?? $clinical['key'];
@endphp

<section class="workspace-card scroll-mt-4 overflow-hidden" aria-labelledby="clinical-workflow-title">
    <div class="border-b border-surface-border bg-surface-muted px-5 py-4 sm:px-6">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <p class="text-xs font-semibold text-primary">المسار السريري</p>
                <h2 id="clinical-workflow-title" class="mt-1 text-xl font-bold text-text">{{ $clinical['title'] }}</h2>
                <p class="mt-1 text-sm text-text-muted">{{ $clinical['description'] }}</p>
            </div>
            <span class="w-fit rounded-full border border-primary/30 bg-primary-soft px-3 py-1 text-xs font-semibold text-primary">
                {{ match ($clinical['key']) {
                    'awaiting_assignment' => 'بانتظار الإسناد',
                    'awaiting_evaluation' => 'بانتظار التقييم',
                    'evaluation_draft' => 'مسودة تقييم',
                    'plan_preparation' => 'إعداد الخطة',
                    'handoff_ready' => 'جاهزة للاستقبال',
                    default => 'قيد التنفيذ',
                } }}
            </span>
        </div>
    </div>

    <div class="p-5 sm:p-6">
        @if($clinical['secondary_key'] ?? null)
            <div class="mb-5 rounded-lg border border-primary/30 bg-primary-soft px-4 py-3">
                <p class="text-sm font-semibold text-primary">{{ $clinical['secondary_title'] }}</p>
                <p class="mt-1 text-xs text-text-muted">{{ $clinical['secondary_description'] }}</p>
            </div>
        @endif

        @if($assignment)
            <div class="mb-5 flex flex-col justify-between gap-3 rounded-lg border border-surface-border bg-surface-muted p-4 sm:flex-row sm:items-center">
                <div>
                    <p class="text-xs text-text-subtle">المختص المكلّف بالتقييم</p>
                    <p class="mt-1 font-semibold text-text">{{ $assignment->assignee?->name ?? 'حساب سابق' }}</p>
                    <p class="mt-1 text-xs text-text-muted">
                        {{ \App\Models\PatientClinicalEvaluationAssignment::STATUS_LABELS[$assignment->status] }}
                        @if($assignment->started_at) · بدأ {{ $assignment->started_at->format('Y-m-d H:i') }} @endif
                    </p>
                </div>
                @if($assignment->isPending())
                    @can('manage clinical evaluation assignments')
                        <form method="POST" action="{{ route('patients.clinical-evaluation-assignment.store', $patient) }}" class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-end" data-workspace-direct-action>
                            @csrf
                            <input type="hidden" name="workspace_panel" value="clinical">
                            <input type="hidden" name="workspace_section" value="clinical">
                            <label class="min-w-64">
                                <span class="block text-sm font-medium text-text">تغيير المختص</span>
                                <select name="assigned_to" class="clinic-field mt-1 w-full" required>
                                    <option value="">اختر المختص السريري</option>
                                    @foreach($eligibleClinicians as $clinician)
                                        <option value="{{ $clinician->id }}" @selected(old('assigned_to') == $clinician->id)>{{ $clinician->name }}{{ $clinician->therapistIncludingTrashed?->specialization ? ' · '.$clinician->therapistIncludingTrashed->specialization : '' }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <button class="clinic-btn-secondary">تغيير المختص</button>
                        </form>
                    @endcan
                @endif
            </div>
        @elseif($clinical['key'] === 'awaiting_assignment')
            @can('manage clinical evaluation assignments')
                <form method="POST" action="{{ route('patients.clinical-evaluation-assignment.store', $patient) }}" class="mb-5 flex flex-col gap-3 rounded-lg border border-primary/30 bg-primary-soft p-4 sm:flex-row sm:items-end" data-workspace-direct-action>
                    @csrf
                    <input type="hidden" name="workspace_panel" value="clinical">
                    <input type="hidden" name="workspace_section" value="clinical">
                    <label class="min-w-0 flex-1">
                        <span class="block text-sm font-medium text-text">إسناد التقييم</span>
                        <select name="assigned_to" class="clinic-field mt-1 w-full" required>
                            <option value="">اختر المختص السريري</option>
                            @foreach($eligibleClinicians as $clinician)
                                <option value="{{ $clinician->id }}" @selected(old('assigned_to') == $clinician->id)>{{ $clinician->name }}{{ $clinician->therapistIncludingTrashed?->specialization ? ' · '.$clinician->therapistIncludingTrashed->specialization : '' }}</option>
                            @endforeach
                        </select>
                        @error('assigned_to')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
                    </label>
                    <button class="clinic-btn-primary">إسناد</button>
                </form>
            @else
                <p class="mb-5 rounded-lg border border-surface-border bg-surface-muted px-4 py-3 text-sm text-text-muted">تنتظر الحالة إسناد التقييم من الاستقبال.</p>
            @endcan
        @elseif($clinical['key'] === 'operational' && ! $assignment && ! ($clinical['secondary_key'] ?? null))
            @can('manage clinical evaluation assignments')
                <div class="mb-5 rounded-lg border border-primary/30 bg-primary-soft p-4">
                    <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-text">بدء إعادة تقييم</p>
                            <p class="mt-1 text-xs leading-6 text-text-muted">
                                ابدأ دورة تقييم سريري جديدة مع استمرار الخطة العلاجية الحالية دون تغيير.
                            </p>
                        </div>

                        @if($eligibleClinicians->isNotEmpty())
                            <form method="POST" action="{{ route('patients.clinical-evaluation-assignment.store', $patient) }}" class="flex w-full flex-col gap-2 sm:flex-row sm:items-end lg:w-auto" data-workspace-direct-action>
                                @csrf
                                <input type="hidden" name="workspace_panel" value="clinical">
                                <input type="hidden" name="workspace_section" value="clinical">

                                <label class="min-w-64 flex-1">
                                    <span class="block text-sm font-medium text-text">المختص السريري</span>

                                    <select name="assigned_to" class="clinic-field mt-1 w-full" required>
                                        <option value="">اختر المختص السريري</option>

                                        @foreach($eligibleClinicians as $clinician)
                                            <option value="{{ $clinician->id }}" @selected(old('assigned_to') == $clinician->id)>
                                                {{ $clinician->name }}{{ $clinician->therapistIncludingTrashed?->specialization ? ' · '.$clinician->therapistIncludingTrashed->specialization : '' }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @error('assigned_to')
                                        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                                    @enderror
                                </label>

                                <button class="clinic-btn-primary whitespace-nowrap">
                                    إسناد إعادة التقييم
                                </button>
                            </form>
                        @else
                            <p class="text-sm text-text-muted">
                                لا يوجد مختص سريري نشط متاح لإعادة التقييم حاليًا.
                            </p>
                        @endif
                    </div>
                </div>
            @endcan
        @endif

        @if(in_array($clinicalActivityKey, ['awaiting_assignment', 'awaiting_evaluation', 'evaluation_draft'], true))
            @can('manage clinical evaluations')
                @if($clinical['can_edit_evaluation'])
                    <form id="workspace-clinical" method="POST" action="{{ route('patients.clinical-evaluation.save-draft', $patient) }}" class="scroll-mt-4 space-y-4" x-show="panel === 'clinical' || panel === null" data-workspace-dirty-track>
                        @csrf
                        <input type="hidden" name="workspace_panel" value="clinical">
                        <input type="hidden" name="workspace_section" value="clinical">
                        <div>
                            <label for="clinical_summary" class="block text-sm font-medium text-text">ملخص التقييم السريري</label>
                            <textarea id="clinical_summary" name="clinical_summary" rows="8" class="clinic-field mt-1 min-h-48 w-full resize-y" placeholder="دوّن الملاحظات السريرية والنتائج الأولية...">{{ old('clinical_summary', $evaluation?->clinical_summary) }}</textarea>
                            @error('clinical_summary')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="submit" class="clinic-btn-secondary">حفظ المسودة</button>
                            @if($evaluation?->isDraft())
                                <button type="submit" formaction="{{ route('patients.clinical-evaluation.complete', [$patient, $evaluation]) }}" class="clinic-btn-primary">إكمال التقييم</button>
                            @endif
                        </div>
                    </form>
                @elseif($clinicalActivityKey === 'evaluation_draft')
                    <p class="rounded-lg border border-warning/30 bg-warning-soft px-4 py-3 text-sm text-warning">مسودة التقييم قيد الاستكمال بواسطة مختص سريري آخر.</p>
                @elseif($clinicalActivityKey === 'awaiting_evaluation')
                    <p class="text-sm text-text-muted">التقييم متاح للمختص المكلّف فقط.</p>
                @endif
            @else
                <p class="text-sm text-text-muted">التقييم السريري متاح للمختص المخوّل فقط.</p>
            @endcan
        @else
            <div class="rounded-lg border border-success/30 bg-success-soft p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h3 class="font-bold text-success">ملخص التقييم المكتمل</h3>
                    @if($evaluation?->completed_at)<span class="text-xs text-text-subtle">{{ $evaluation->completed_at->format('Y-m-d H:i') }}</span>@endif
                </div>
                <p class="mt-3 whitespace-pre-line text-sm leading-7 text-text">{{ $evaluation?->clinical_summary ?: 'لم يُسجل ملخص تفصيلي.' }}</p>
            </div>

            @if($clinicalActivityKey === 'plan_preparation')
                @can('manage clinical evaluations')
                    <div id="workspace-clinical-plan" class="mt-6 scroll-mt-4" x-show="panel === 'clinical-plan' || panel === null" x-data="{ items: {{ Illuminate\Support\Js::from($clinicalItems) }}, add() { this.items.push({ service_id: '', planned_quantity: 1 }); }, remove(index) { if (this.items.length > 1) this.items.splice(index, 1); } }">
                        <form method="POST" action="{{ route('patients.clinical-plan.save-draft', $patient) }}" data-workspace-dirty-track>
                            @csrf
                            <input type="hidden" name="workspace_panel" value="clinical-plan">
                            <input type="hidden" name="workspace_section" value="clinical">
                            <input type="hidden" name="clinical_evaluation_id" value="{{ $evaluation->id }}">
                            <div class="flex items-center justify-between gap-3">
                                <div><h3 class="font-bold text-text">الخطة العلاجية</h3><p class="mt-1 text-xs text-text-muted">حدد الخدمة والكمية فقط. تُحتسب الأسعار من سجل الخدمات المعتمد.</p></div>
                                <button type="button" class="clinic-btn-secondary" @click="add()">إضافة خدمة</button>
                            </div>
                            <div class="mt-4 space-y-3">
                                <template x-for="(item, index) in items" :key="index">
                                    <div class="grid gap-3 rounded-lg border border-surface-border p-4 md:grid-cols-[minmax(0,1fr)_160px_auto] md:items-end">
                                        <div>
                                            <label class="block text-sm font-medium text-text">الخدمة</label>
                                            <select class="clinic-field mt-1 w-full" x-model="item.service_id" :name="`items[${index}][service_id]`" required>
                                                <option value="">اختر الخدمة</option>
                                                @foreach($services as $service)<option value="{{ $service->id }}">{{ $service->name }} · {{ $service->specialty->name }}</option>@endforeach
                                            </select>
                                            <input type="hidden" :name="`items[${index}][position]`" :value="index + 1">
                                        </div>
                                        <div><label class="block text-sm font-medium text-text">الكمية المخططة</label><input type="number" min="1" max="10000" class="clinic-field mt-1 w-full" x-model="item.planned_quantity" :name="`items[${index}][planned_quantity]`" required></div>
                                        <button type="button" class="clinic-btn-secondary" @click="remove(index)" :disabled="items.length === 1">حذف</button>
                                    </div>
                                </template>
                            </div>
                            @error('items')<p class="mt-2 text-sm text-danger">{{ $message }}</p>@enderror
                            <div class="mt-4 flex flex-wrap gap-2">
                                <button type="submit" class="clinic-btn-secondary">حفظ مسودة الخطة</button>
                                <button type="submit" formaction="{{ $clinicalPlan
                                    ? route('patients.clinical-plan.approve', [$patient, $clinicalPlan])
                                    : route('patients.clinical-plan.approve-new', $patient) }}" class="clinic-btn-primary">اعتماد وتسليم للاستقبال</button>
                            </div>
                        </form>
                    </div>
                @endcan
            @elseif($clinical['plan'])
                <div class="mt-6">
                    <h3 class="font-bold text-text">الخدمات المعتمدة للاستقبال</h3>
                    <div class="mt-3 overflow-x-auto rounded-lg border border-surface-border">
                        <table class="clinic-table min-w-[520px] text-right">
                            <thead><tr><th>الترتيب</th><th>الخدمة</th><th>الكمية المخططة</th></tr></thead>
                            <tbody>
                                @foreach($clinical['plan']->items as $item)
                                    <tr><td>{{ $item->position }}</td><td class="font-semibold text-text">{{ $item->service->name }}</td><td>{{ $item->planned_quantity }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-3 text-xs text-text-muted">الخدمات والكميات معتمدة سريريًا وغير قابلة للتعديل من الاستقبال.</p>
                </div>
            @endif
        @endif
    </div>
</section>
