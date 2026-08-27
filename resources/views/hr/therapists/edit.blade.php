<x-app-layout>
    <x-slot name="title">تعديل بيانات الأخصائي</x-slot>

    <div class="mx-auto max-w-3xl space-y-6">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <p class="text-xs font-semibold text-primary">الفريق العلاجي</p>
                <h1 class="mt-1 text-2xl font-bold text-text">تعديل بيانات {{ $therapist->name }}</h1>
                <p class="mt-1 text-sm text-text-muted">تعديل البيانات الأساسية فقط دون تغيير الأجر التاريخي.</p>
            </div>

            <a href="{{ route('therapists.show', $therapist) }}" class="clinic-btn-secondary">
                إلغاء
            </a>
        </div>

        @if($errors->any())
            <div class="rounded-lg border border-danger/30 bg-danger-soft px-4 py-3 text-sm text-danger">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('therapists.update', $therapist) }}" method="POST"
              class="rounded-lg border border-surface-border bg-surface p-5 sm:p-6">
            @csrf
            @method('PUT')

            <div class="grid gap-5 sm:grid-cols-2">
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-text">الاسم الكامل *</span>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $therapist->name) }}"
                        class="clinic-field w-full"
                        required
                    >
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-text">التخصص المسجل</span>
                    <input
                        type="text"
                        name="specialization"
                        value="{{ old('specialization', $therapist->specialization) }}"
                        class="clinic-field w-full"
                    >
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-text">الهاتف</span>
                    <input
                        type="text"
                        name="phone"
                        value="{{ old('phone', $therapist->phone) }}"
                        class="clinic-field w-full"
                        dir="ltr"
                    >
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-text">البريد الإلكتروني</span>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email', $therapist->email) }}"
                        class="clinic-field w-full"
                        dir="ltr"
                    >
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-text">رقم الترخيص</span>
                    <input
                        type="text"
                        name="license_number"
                        value="{{ old('license_number', $therapist->license_number) }}"
                        class="clinic-field w-full"
                    >
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-text">تاريخ التعيين</span>
                    <input
                        type="date"
                        name="hire_date"
                        value="{{ old('hire_date', $therapist->hire_date?->format('Y-m-d')) }}"
                        class="clinic-field w-full"
                    >
                </label>
            </div>

            <div class="mt-5 rounded-lg border border-surface-border bg-surface-muted p-4">
                <input type="hidden" name="is_active" value="0">

                <label class="flex cursor-pointer items-center gap-3">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        @checked((bool) old('is_active', $therapist->is_active))
                        class="rounded border-surface-border"
                    >
                    <span>
                        <span class="block font-medium text-text">الأخصائي نشط</span>
                        <span class="block text-xs text-text-muted">إلغاء التفعيل يمنع استخدامه تشغيليًا دون حذف سجله.</span>
                    </span>
                </label>
            </div>

            <div class="mt-6 rounded-lg border border-warning/30 bg-warning-soft p-4">
                <p class="font-semibold text-warning">الأجر الحالي غير قابل للتعديل من هذه الشاشة</p>
                <p class="mt-1 text-sm text-text-muted">
                    سيتم إضافة تغيير الأجر بتاريخ سريان مستقل للحفاظ على صحة المرتبات والاستحقاقات السابقة.
                </p>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <a href="{{ route('therapists.show', $therapist) }}" class="clinic-btn-secondary">إلغاء</a>
                <button type="submit" class="clinic-btn-primary">حفظ التعديلات</button>
            </div>
        </form>
    </div>
</x-app-layout>