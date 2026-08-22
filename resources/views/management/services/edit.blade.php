<x-app-layout>
    <x-slot name="title">تعديل الخدمة</x-slot>
    <div class="mx-auto max-w-3xl"><div class="clinic-card overflow-hidden">
        <div class="border-b border-surface-border p-5"><h1 class="text-xl font-bold text-text">تعديل الخدمة</h1></div>
        <form method="POST" action="{{ route('services.update', $service) }}" class="space-y-5 p-5">@csrf @method('PUT')
            <label class="block"><span class="mb-1 block text-sm font-medium text-text">التخصص</span><select name="specialty_id" class="clinic-field w-full" required>@foreach($specialties as $specialty)<option value="{{ $specialty->id }}" @selected(old('specialty_id', $service->specialty_id) == $specialty->id)>{{ $specialty->name }}</option>@endforeach</select>@error('specialty_id')<span class="mt-1 block text-sm text-danger">{{ $message }}</span>@enderror</label>
            <label class="block"><span class="mb-1 block text-sm font-medium text-text">اسم الخدمة</span><input name="name" value="{{ old('name', $service->name) }}" class="clinic-field w-full" required>@error('name')<span class="mt-1 block text-sm text-danger">{{ $message }}</span>@enderror</label>
            <label class="block"><span class="mb-1 block text-sm font-medium text-text">الوصف</span><textarea name="description" rows="4" class="clinic-field w-full">{{ old('description', $service->description) }}</textarea></label>
            <label class="block"><span class="mb-1 block text-sm font-medium text-text">المدة الافتراضية بالدقائق</span><input type="number" name="default_duration_minutes" value="{{ old('default_duration_minutes', $service->default_duration_minutes) }}" min="1" class="clinic-field w-full"></label>
            <label class="block"><span class="mb-1 block text-sm font-medium text-text">سعر العميل</span><input type="number" name="customer_price" value="{{ old('customer_price', $service->customer_price) }}" min="0.01" step="0.01" class="clinic-field w-full"><span class="mt-1 block text-xs text-text-muted">يُطبق على الإضافات الجديدة فقط، ولا يغيّر أسعار الخطط المحفوظة.</span>@error('customer_price')<span class="mt-1 block text-sm text-danger">{{ $message }}</span>@enderror</label>
            <input type="hidden" name="is_active" value="0"><label class="flex items-center gap-2 text-sm text-text"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $service->is_active)) class="rounded border-surface-border text-primary focus:ring-primary">خدمة نشطة</label>
            <div class="flex justify-end gap-2"><a href="{{ route('services.index') }}" class="rounded-lg border border-surface-border px-4 py-2 text-sm text-text hover:bg-surface-muted">إلغاء</a><button class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-contrast hover:bg-primary-hover">حفظ التعديلات</button></div>
        </form>
    </div></div>
</x-app-layout>
