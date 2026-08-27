<x-app-layout>
    <x-slot name="title">سجل المرضى</x-slot>

    <div class="mb-6 flex items-center justify-between gap-4">
        <h2 class="text-2xl font-bold text-text">سجل المرضى</h2>
        <a href="{{ route('patients.create') }}" class="clinic-btn-primary">+ إضافة حالة جديدة</a>
    </div>

    <!-- محرك البحث -->
    <form action="{{ route('patients.index') }}" method="GET" class="mb-4 grid gap-3 sm:grid-cols-[minmax(0,1fr)_240px_auto]">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="بحث باسم الطفل أو الباركود أو ولي الأمر..." class="clinic-field w-full">
            <select name="stage" class="clinic-field w-full" aria-label="مرحلة سير الحالة">
                <option value="">كل المراحل</option>
                @foreach(\App\Services\PatientClinicalStageResolver::FILTERS as $value => $label)
                    <option value="{{ $value }}" @selected($stage === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="clinic-btn-secondary">بحث</button>
    </form>

    <!-- الجدول -->
    <div class="overflow-x-auto rounded-lg border border-surface-border bg-surface-elevated shadow-sm">
        <table class="w-full text-right">
            <thead class="bg-surface-muted">
                <tr>
                    <th class="px-6 py-3 text-xs font-medium text-text-muted">اسم الطفل</th>
                    <th class="px-6 py-3 text-xs font-medium text-text-muted">ولي الأمر</th>
                    <th class="px-6 py-3 text-xs font-medium text-text-muted">العمر</th>
                    <th class="px-6 py-3 text-xs font-medium text-text-muted">التشخيص</th>
                    <th class="px-6 py-3 text-xs font-medium text-text-muted">مرحلة العمل</th>
                    <th class="px-6 py-3 text-xs font-medium text-text-muted">حالة الملف</th>
                    <th class="px-6 py-3 text-xs font-medium text-text-muted">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-border">
                @foreach ($patients as $patient)
                <tr class="hover:bg-surface-muted">
                    <td class="px-6 py-4 font-medium text-text"><a href="{{ route('patients.workspace', $patient) }}" class="clinic-entity-link">{{ $patient->name }}</a><div class="mt-1 text-xs text-text-subtle">{{ $patient->barcode }}</div></td>
                    <td class="px-6 py-4 text-text-muted">{{ $patient->guardian->name ?? 'غير محدد' }}</td>
                    <td class="px-6 py-4 text-text-muted">{{ $patient->birth_date->age }} سنة</td>
                    <td class="px-6 py-4 text-text-muted">{{ $patient->diagnosis }}</td>
                    <td class="px-6 py-4">
                        <span class="inline-flex rounded-full border border-primary/30 bg-primary-soft px-3 py-1 text-xs font-semibold text-primary">{{ $patient->clinical_stage['title'] }}</span>
                        @if($patient->clinical_stage['secondary_key'])
                            <span class="mt-1 block text-xs font-medium text-primary">إعادة تقييم</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $patient->is_active ? 'bg-success-soft text-success' : 'bg-warning-soft text-warning' }}">
                            {{ $patient->is_active ? 'نشط' : 'غير نشط' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 space-x-reverse space-x-2">
                        <a href="{{ route('patients.edit', $patient) }}" class="text-warning hover:underline">تعديل</a>
                        <a href="{{ route('patients.print-card', $patient) }}" target="_blank" class="text-success hover:underline">بطاقة</a>
                        <form action="{{ route('patients.destroy', $patient) }}" method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا المريض؟');">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-danger hover:underline">حذف</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- الترقيم -->
    <div class="mt-4">
        {{ $patients->links() }}
    </div>
</x-app-layout>
