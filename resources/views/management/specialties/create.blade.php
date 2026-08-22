<x-app-layout>
    <x-slot name="title">إضافة تخصص</x-slot>
    <div class="mx-auto max-w-3xl">
        <div class="clinic-card overflow-hidden">
            <div class="border-b border-surface-border p-5"><h1 class="text-xl font-bold text-text">إضافة تخصص جديد</h1></div>
            <form method="POST" action="{{ route('specialties.store') }}" class="space-y-5 p-5">
                @csrf
                <label class="block"><span class="mb-1 block text-sm font-medium text-text">اسم التخصص</span><input name="name" value="{{ old('name') }}" class="clinic-field w-full" required>@error('name')<span class="mt-1 block text-sm text-danger">{{ $message }}</span>@enderror</label>
                <label class="block"><span class="mb-1 block text-sm font-medium text-text">الوصف</span><textarea name="description" rows="4" class="clinic-field w-full">{{ old('description') }}</textarea>@error('description')<span class="mt-1 block text-sm text-danger">{{ $message }}</span>@enderror</label>
                <input type="hidden" name="is_active" value="0"><label class="flex items-center gap-2 text-sm text-text"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-surface-border text-primary focus:ring-primary">تخصص نشط</label>
                <div class="flex justify-end gap-2"><a href="{{ route('specialties.index') }}" class="rounded-lg border border-surface-border px-4 py-2 text-sm text-text hover:bg-surface-muted">إلغاء</a><button class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-contrast hover:bg-primary-hover">حفظ التخصص</button></div>
            </form>
        </div>
    </div>
</x-app-layout>
