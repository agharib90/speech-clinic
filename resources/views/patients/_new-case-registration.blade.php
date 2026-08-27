@php
    $guardianErrors = collect($errors->keys())->contains(fn ($key) => str_starts_with($key, 'guardian'));
    $patientErrors = collect($errors->keys())->contains(fn ($key) => in_array($key, ['name', 'birth_date', 'gender', 'diagnosis', 'referral_source', 'is_active', 'notes'], true));
    $initialStep = $guardianErrors ? 1 : ($patientErrors ? 2 : 1);
@endphp

<div class="mx-auto max-w-5xl" x-data="newCaseRegistration({
    initialStep: {{ $initialStep }},
    mode: @js(old('guardian_mode', $selectedGuardian ? 'existing' : 'search')),
    searchUrl: @js(route('patients.guardian-search')),
    selectedGuardian: @js($selectedGuardian ? ['id' => $selectedGuardian->id, 'name' => $selectedGuardian->name, 'phone' => $selectedGuardian->phone] : null),
    duplicateGuardian: @js($duplicateGuardian ? ['id' => $duplicateGuardian->id, 'name' => $duplicateGuardian->name, 'phone' => $duplicateGuardian->phone, 'patients_count' => $duplicateGuardian->patients_count] : null),
    guardianId: @js((string) old('guardian_id', $selectedGuardian?->id)),
    guardian: @js(['name' => old('guardian.name', ''), 'phone' => old('guardian.phone', ''), 'phone2' => old('guardian.phone2', ''), 'email' => old('guardian.email', ''), 'national_id' => old('guardian.national_id', ''), 'address' => old('guardian.address', ''), 'notes' => old('guardian.notes', '')]),
    patient: @js(['name' => old('name', ''), 'birth_date' => old('birth_date', ''), 'gender' => old('gender', 'male'), 'diagnosis' => old('diagnosis', ''), 'referral_source' => old('referral_source', ''), 'notes' => old('notes', '')]),
    isActive: @js((bool) old('is_active', true)),
})">
    <header class="mb-6">
        <h1 class="text-2xl font-bold text-text">إضافة حالة جديدة</h1>
        <p class="mt-1 text-sm text-text-muted">سجّل ولي الأمر وبيانات الطفل، ثم راجع الحالة قبل الحفظ.</p>
    </header>

    <ol class="mb-6 grid grid-cols-3 gap-2" aria-label="خطوات إضافة الحالة">
        <li class="flex min-w-0 items-center gap-2 border-b-2 px-1 pb-3" :class="step >= 1 ? 'border-primary text-primary' : 'border-surface-border text-text-muted'" :aria-current="step === 1 ? 'step' : null"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold" :class="step >= 1 ? 'bg-primary text-primary-contrast' : 'bg-surface-muted text-text-muted'">1</span><span class="truncate text-xs font-semibold sm:text-sm">ولي الأمر</span></li>
        <li class="flex min-w-0 items-center gap-2 border-b-2 px-1 pb-3" :class="step >= 2 ? 'border-primary text-primary' : 'border-surface-border text-text-muted'" :aria-current="step === 2 ? 'step' : null"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold" :class="step >= 2 ? 'bg-primary text-primary-contrast' : 'bg-surface-muted text-text-muted'">2</span><span class="truncate text-xs font-semibold sm:text-sm">بيانات الطفل</span></li>
        <li class="flex min-w-0 items-center gap-2 border-b-2 px-1 pb-3" :class="step >= 3 ? 'border-primary text-primary' : 'border-surface-border text-text-muted'" :aria-current="step === 3 ? 'step' : null"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold" :class="step >= 3 ? 'bg-primary text-primary-contrast' : 'bg-surface-muted text-text-muted'">3</span><span class="truncate text-xs font-semibold sm:text-sm">مراجعة وحفظ</span></li>
    </ol>

    <form action="{{ route('patients.store') }}" method="POST" class="clinic-card p-4 sm:p-6" novalidate>
        @csrf
        <input type="hidden" name="guardian_mode" :value="mode === 'new' ? 'new' : 'existing'">
        <input type="hidden" name="guardian_id" :value="guardianId">

        <section x-show="step === 1" aria-labelledby="guardian-step-title">
            <div class="mb-5">
                <h2 id="guardian-step-title" class="text-lg font-bold text-text">بيانات ولي الأمر</h2>
                <p class="mt-1 text-sm text-text-muted">ابحث أولًا لتجنب إنشاء سجل مكرر.</p>
            </div>

            <div x-show="mode !== 'new' && !selectedGuardian">
                <label for="guardian-search" class="mb-1 block text-sm font-medium text-text">ابحث باسم ولي الأمر أو رقم الهاتف</label>
                <div class="relative">
                    <input id="guardian-search" x-ref="guardianSearch" x-model="query" @input.debounce.350ms="searchGuardians" type="search" autocomplete="off" class="clinic-field w-full ps-11" placeholder="اكتب حرفين على الأقل" autofocus>
                    <svg class="absolute right-3 top-3 h-5 w-5 text-text-subtle" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" /></svg>
                </div>
                <p x-show="loading" x-cloak class="mt-3 text-sm text-text-muted" role="status">جارٍ البحث...</p>
                <p x-show="searchError" x-cloak class="mt-3 rounded-lg border border-danger/30 bg-danger-soft px-3 py-2 text-sm text-danger" x-text="searchError"></p>

                <div x-show="!loading && results.length" x-cloak class="mt-3 divide-y divide-surface-border overflow-hidden rounded-lg border border-surface-border">
                    <template x-for="guardianResult in results" :key="guardianResult.id">
                        <button type="button" @click="selectGuardian(guardianResult)" class="flex w-full items-center justify-between gap-4 px-4 py-3 text-right transition hover:bg-primary-soft focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary">
                            <span class="min-w-0"><strong class="block truncate text-sm text-text" x-text="guardianResult.name"></strong><span class="mt-0.5 block text-sm text-text-muted" dir="ltr" x-text="guardianResult.phone"></span></span>
                            <span class="shrink-0 text-xs text-text-subtle" x-text="`${guardianResult.patients_count} حالة مرتبطة`"></span>
                        </button>
                    </template>
                </div>
                <p x-show="searched && !loading && !results.length && !searchError" x-cloak class="mt-3 rounded-lg border border-surface-border bg-surface-muted px-4 py-4 text-sm text-text-muted">لم يتم العثور على ولي أمر مطابق.</p>
                <button type="button" @click="startNewGuardian" class="clinic-btn-secondary mt-4 w-full sm:w-auto">+ تسجيل ولي أمر جديد</button>
            </div>

            <div x-show="mode === 'existing' && selectedGuardian" x-cloak class="rounded-lg border border-success/30 bg-success-soft p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div><p class="text-xs font-semibold text-success">ولي الأمر المختار</p><p class="mt-1 font-bold text-text" x-text="selectedGuardian?.name"></p><p class="text-sm text-text-muted" dir="ltr" x-text="selectedGuardian?.phone"></p></div>
                    <button type="button" @click="changeGuardian" class="text-sm font-semibold text-primary hover:text-primary-hover">تغيير ولي الأمر</button>
                </div>
            </div>

            <fieldset x-show="mode === 'new'" x-cloak x-ref="guardianFields" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <legend class="mb-4 w-full text-sm font-semibold text-primary">تسجيل ولي أمر جديد داخل الحالة</legend>
                <div><label class="mb-1 block text-sm font-medium text-text">الاسم الكامل *</label><input x-ref="guardianName" x-model="guardian.name" name="guardian[name]" type="text" maxlength="255" :required="mode === 'new'" :disabled="mode !== 'new'" class="clinic-field w-full @error('guardian.name') !border-danger @enderror">@error('guardian.name')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror</div>
                <div><label class="mb-1 block text-sm font-medium text-text">رقم الهاتف *</label><input x-model="guardian.phone" name="guardian[phone]" type="tel" maxlength="20" dir="ltr" :required="mode === 'new'" :disabled="mode !== 'new'" class="clinic-field w-full @error('guardian.phone') !border-danger @enderror">@error('guardian.phone')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror</div>
                <div x-show="duplicateGuardian" x-cloak class="rounded-lg border border-warning/30 bg-warning-soft p-4 md:col-span-2">
                    <p class="text-xs font-semibold text-warning">تم العثور على ولي أمر بهذا الهاتف</p>
                    <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
                        <div><p class="font-bold text-text" x-text="duplicateGuardian?.name"></p><p class="text-sm text-text-muted"><span dir="ltr" x-text="duplicateGuardian?.phone"></span> · <span x-text="`${duplicateGuardian?.patients_count || 0} حالة مرتبطة`"></span></p></div>
                        <button type="button" @click="useDuplicateGuardian" class="clinic-btn-primary">استخدام ولي الأمر الموجود</button>
                    </div>
                </div>
                <div><label class="mb-1 block text-sm font-medium text-text">رقم هاتف بديل</label><input x-model="guardian.phone2" name="guardian[phone2]" type="tel" maxlength="20" dir="ltr" :disabled="mode !== 'new'" class="clinic-field w-full"></div>
                <div><label class="mb-1 block text-sm font-medium text-text">البريد الإلكتروني</label><input x-model="guardian.email" name="guardian[email]" type="email" maxlength="255" dir="ltr" :disabled="mode !== 'new'" class="clinic-field w-full @error('guardian.email') !border-danger @enderror">@error('guardian.email')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror</div>
                <div><label class="mb-1 block text-sm font-medium text-text">الرقم الوطني</label><input x-model="guardian.national_id" name="guardian[national_id]" type="text" :disabled="mode !== 'new'" class="clinic-field w-full @error('guardian.national_id') !border-danger @enderror">@error('guardian.national_id')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror</div>
                <div><label class="mb-1 block text-sm font-medium text-text">العنوان</label><input x-model="guardian.address" name="guardian[address]" type="text" maxlength="255" :disabled="mode !== 'new'" class="clinic-field w-full"></div>
                <div class="md:col-span-2"><label class="mb-1 block text-sm font-medium text-text">ملاحظات ولي الأمر</label><textarea x-model="guardian.notes" name="guardian[notes]" rows="2" :disabled="mode !== 'new'" class="clinic-field w-full"></textarea></div>
                <button type="button" @click="changeGuardian" class="text-right text-sm font-semibold text-primary hover:text-primary-hover md:col-span-2">العودة إلى البحث</button>
            </fieldset>

            @error('guardian_id')<p class="mt-3 text-sm text-danger">{{ $message }}</p>@enderror
            <p x-show="selectionError" x-cloak class="mt-3 text-sm text-danger" x-text="selectionError"></p>
            <div class="mt-6 flex flex-wrap justify-between gap-3 border-t border-surface-border pt-4"><a href="{{ route('patients.index') }}" class="clinic-btn-secondary">إلغاء</a><button type="button" @click="continueToChild" class="clinic-btn-primary">التالي: بيانات الطفل</button></div>
        </section>

        <section x-show="step === 2" x-cloak aria-labelledby="patient-step-title">
            <div class="mb-5 flex flex-wrap items-start justify-between gap-3 rounded-lg border border-surface-border bg-surface-muted p-4">
                <div><p class="text-xs font-semibold text-text-muted">ولي الأمر</p><p class="mt-1 font-bold text-text" x-text="guardianLabel()?.name"></p><p class="text-sm text-text-muted" dir="ltr" x-text="guardianLabel()?.phone"></p></div>
                <button type="button" @click="changeGuardian" class="text-sm font-semibold text-primary hover:text-primary-hover">تغيير ولي الأمر</button>
            </div>
            <fieldset x-ref="patientFields" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <legend id="patient-step-title" class="mb-4 w-full text-lg font-bold text-text">بيانات الطفل</legend>
                <div><label class="mb-1 block text-sm font-medium text-text">اسم الطفل *</label><input x-ref="patientName" x-model="patient.name" name="name" type="text" maxlength="255" required class="clinic-field w-full @error('name') !border-danger @enderror">@error('name')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror</div>
                <div><label class="mb-1 block text-sm font-medium text-text">تاريخ الميلاد *</label><input x-model="patient.birth_date" name="birth_date" type="date" required class="clinic-field w-full @error('birth_date') !border-danger @enderror">@error('birth_date')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror</div>
                <div><label class="mb-1 block text-sm font-medium text-text">العمر (تلقائي)</label><input :value="age()" type="text" readonly disabled class="clinic-field w-full bg-surface-muted text-text-muted" placeholder="يظهر بعد إدخال تاريخ الميلاد"></div>
                <div><label class="mb-1 block text-sm font-medium text-text">الجنس *</label><select x-model="patient.gender" name="gender" required class="clinic-field w-full"><option value="male">ذكر</option><option value="female">أنثى</option></select></div>
                <div><label class="mb-1 block text-sm font-medium text-text">مصدر الإحالة</label><input x-model="patient.referral_source" name="referral_source" type="text" maxlength="255" class="clinic-field w-full"></div>
                <div><label class="mb-1 block text-sm font-medium text-text">التشخيص</label><input x-model="patient.diagnosis" name="diagnosis" type="text" maxlength="255" class="clinic-field w-full"></div>
                <label class="flex items-center gap-2 md:col-span-2"><input x-model="isActive" name="is_active" value="1" type="checkbox" class="rounded border-surface-border text-primary focus:ring-primary"><span class="text-sm font-medium text-text">الحالة نشطة</span></label>
                <div class="md:col-span-2"><label class="mb-1 block text-sm font-medium text-text">ملاحظات</label><textarea x-model="patient.notes" name="notes" rows="3" class="clinic-field w-full"></textarea></div>
            </fieldset>
            <div class="mt-6 flex flex-wrap justify-between gap-3 border-t border-surface-border pt-4"><button type="button" @click="step = 1" class="clinic-btn-secondary">رجوع</button><button type="button" @click="continueToReview" class="clinic-btn-primary">التالي: المراجعة</button></div>
        </section>

        <section x-show="step === 3" x-cloak aria-labelledby="review-step-title">
            <h2 id="review-step-title" class="text-lg font-bold text-text">مراجعة الحالة قبل الحفظ</h2>
            <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                <article class="rounded-lg border border-surface-border bg-surface-muted p-4"><h3 class="text-sm font-bold text-primary">ولي الأمر</h3><dl class="mt-3 space-y-2 text-sm"><div><dt class="text-text-muted">الاسم</dt><dd class="font-semibold text-text" x-text="guardianLabel()?.name"></dd></div><div><dt class="text-text-muted">الهاتف</dt><dd class="font-semibold text-text" dir="ltr" x-text="guardianLabel()?.phone"></dd></div></dl></article>
                <article class="rounded-lg border border-surface-border bg-surface-muted p-4"><h3 class="text-sm font-bold text-primary">الطفل</h3><dl class="mt-3 grid grid-cols-2 gap-3 text-sm"><div class="col-span-2"><dt class="text-text-muted">الاسم</dt><dd class="font-semibold text-text" x-text="patient.name"></dd></div><div><dt class="text-text-muted">تاريخ الميلاد</dt><dd class="text-text" x-text="patient.birth_date"></dd></div><div><dt class="text-text-muted">العمر</dt><dd class="text-text" x-text="age()"></dd></div><div><dt class="text-text-muted">الجنس</dt><dd class="text-text" x-text="genderLabel()"></dd></div><div><dt class="text-text-muted">الحالة</dt><dd class="text-text" x-text="isActive ? 'نشطة' : 'غير نشطة'"></dd></div><div x-show="patient.diagnosis" class="col-span-2"><dt class="text-text-muted">التشخيص</dt><dd class="text-text" x-text="patient.diagnosis"></dd></div></dl></article>
            </div>
            <div class="mt-6 flex flex-wrap justify-between gap-3 border-t border-surface-border pt-4"><button type="button" @click="step = 2" class="clinic-btn-secondary">رجوع للتعديل</button><button x-ref="submitButton" type="submit" class="clinic-btn-primary">حفظ الحالة وتوليد الباركود</button></div>
        </section>
    </form>
</div>
