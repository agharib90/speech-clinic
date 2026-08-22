<x-app-layout>
    <x-slot name="title">إدارة الخدمات</x-slot>
    <div class="space-y-6">
        @if(session('success'))<div class="rounded-lg border border-success bg-success-soft px-4 py-3 text-sm text-success">{{ session('success') }}</div>@endif
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div><h1 class="text-2xl font-bold text-text">الخدمات</h1><p class="mt-1 text-sm text-text-muted">التسعيرة الحالية للخدمات المهنية، وتُستخدم للإضافات الجديدة إلى خطط المرضى.</p></div>
            <div class="flex gap-2">@can('manage specialties')<a href="{{ route('specialties.index') }}" class="inline-flex items-center rounded-lg border border-surface-border px-4 py-2 text-sm font-medium text-text hover:bg-surface-muted">التخصصات</a>@endcan<a href="{{ route('services.create') }}" class="inline-flex items-center rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-contrast hover:bg-primary-hover">إضافة خدمة</a></div>
        </div>
        <div class="overflow-hidden rounded-lg border border-surface-border bg-surface-elevated"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-surface-border text-sm">
            <thead class="bg-surface-muted text-right text-xs text-text-muted"><tr><th class="px-5 py-3">الخدمة</th><th class="px-5 py-3">التخصص</th><th class="px-5 py-3">المدة</th><th class="px-5 py-3">سعر العميل</th><th class="px-5 py-3">الأخصائيون</th><th class="px-5 py-3">الحالة</th><th class="px-5 py-3">الإجراءات</th></tr></thead>
            <tbody class="divide-y divide-surface-border">@forelse($services as $service)<tr>
                <td class="px-5 py-4"><p class="font-semibold text-text">{{ $service->name }}</p><p class="mt-1 text-xs text-text-muted">{{ $service->description ?: 'بدون وصف' }}</p></td><td class="px-5 py-4 text-text-muted">{{ $service->specialty->name }}</td><td class="px-5 py-4 text-text-muted">{{ $service->default_duration_minutes ? $service->default_duration_minutes . ' دقيقة' : 'غير محددة' }}</td><td class="px-5 py-4 font-semibold {{ $service->customer_price ? 'text-text' : 'text-warning' }}">{{ $service->customer_price ? number_format((float) $service->customer_price, 2).' ج.م' : 'غير معتمد' }}</td><td class="px-5 py-4 text-text-muted">{{ $service->therapists_count }}</td>
                <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $service->is_active ? 'bg-success-soft text-success' : 'bg-surface-muted text-text-muted' }}">{{ $service->is_active ? 'نشطة' : 'غير نشطة' }}</span></td>
                <td class="px-5 py-4"><div class="flex gap-2"><a href="{{ route('services.edit', $service) }}" class="text-sm font-medium text-primary hover:text-primary-hover">تعديل</a><form method="POST" action="{{ route('services.toggle-active', $service) }}">@csrf @method('PATCH')<button class="text-sm font-medium {{ $service->is_active ? 'text-warning' : 'text-success' }}">{{ $service->is_active ? 'تعطيل' : 'تفعيل' }}</button></form></div></td>
            </tr>@empty<tr><td colspan="7" class="px-5 py-10 text-center text-text-muted">لا توجد خدمات مسجلة.</td></tr>@endforelse</tbody>
        </table></div></div>{{ $services->links() }}
    </div>
</x-app-layout>
