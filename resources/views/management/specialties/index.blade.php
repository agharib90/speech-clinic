<x-app-layout>
    <x-slot name="title">إدارة التخصصات</x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="rounded-lg border border-success bg-success-soft px-4 py-3 text-sm text-success">{{ session('success') }}</div>
        @endif

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-text">التخصصات</h1>
                <p class="mt-1 text-sm text-text-muted">إدارة التصنيفات المهنية التي تندرج تحتها خدمات العيادة.</p>
            </div>
            <div class="flex gap-2">
                @can('manage services')
                    <a href="{{ route('services.index') }}" class="inline-flex items-center rounded-lg border border-surface-border px-4 py-2 text-sm font-medium text-text hover:bg-surface-muted">الخدمات</a>
                @endcan
                <a href="{{ route('specialties.create') }}" class="inline-flex items-center rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-contrast hover:bg-primary-hover">إضافة تخصص</a>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-surface-border bg-surface-elevated">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-surface-border text-sm">
                    <thead class="bg-surface-muted text-right text-xs text-text-muted">
                        <tr>
                            <th class="px-5 py-3">التخصص</th>
                            <th class="px-5 py-3">الخدمات</th>
                            <th class="px-5 py-3">الأخصائيون</th>
                            <th class="px-5 py-3">الحالة</th>
                            <th class="px-5 py-3">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border">
                        @forelse($specialties as $specialty)
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-text">{{ $specialty->name }}</p>
                                    <p class="mt-1 max-w-xl text-xs text-text-muted">{{ $specialty->description ?: 'بدون وصف' }}</p>
                                </td>
                                <td class="px-5 py-4 text-text-muted">{{ $specialty->services_count }}</td>
                                <td class="px-5 py-4 text-text-muted">{{ $specialty->therapists_count }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $specialty->is_active ? 'bg-success-soft text-success' : 'bg-surface-muted text-text-muted' }}">{{ $specialty->is_active ? 'نشط' : 'غير نشط' }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('specialties.edit', $specialty) }}" class="text-sm font-medium text-primary hover:text-primary-hover">تعديل</a>
                                        <form method="POST" action="{{ route('specialties.toggle-active', $specialty) }}">
                                            @csrf @method('PATCH')
                                            <button class="text-sm font-medium {{ $specialty->is_active ? 'text-warning' : 'text-success' }}">{{ $specialty->is_active ? 'تعطيل' : 'تفعيل' }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-text-muted">لا توجد تخصصات مسجلة.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{ $specialties->links() }}
    </div>
</x-app-layout>
