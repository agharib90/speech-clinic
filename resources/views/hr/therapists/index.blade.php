<x-app-layout>
    <x-slot name="title">الكوادر الطبية</x-slot>

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">الأخصائيون</h2>
        <a href="{{ route('therapists.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">إضافة أخصائي جديد</a>
    </div>

    <!-- فورم تسجيل الحضور السريع -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow mb-6">
        <h3 class="font-bold mb-3 dark:text-gray-200">تسجيل حضور اليوم ({{ today()->format('Y-m-d') }})</h3>
        <form action="{{ route('therapists.attendance') }}" method="POST" class="flex flex-wrap gap-3 items-end">
            @csrf
            <select name="therapist_id" class="border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200" required>
                <option value="">اختر الأخصائي...</option>
                @foreach($therapists as $therapist)
                    <option value="{{ $therapist->id }}">{{ $therapist->name }}</option>
                @endforeach
            </select>
            <select name="status" class="border rounded px-3 py-2 dark:bg-gray-700 dark:text-gray-200">
                <option value="حاضر">حاضر</option>
                <option value="إجازة">إجازة</option>
                <option value="غائب">غائب</option>
            </select>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">تسجيل</button>
        </form>
    </div>

    <!-- جدول الأخصائيين -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
        <table class="w-full text-right">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">الاسم</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">التخصص</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">نظام الراتب</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">الحالة</th>
                </tr>
            </thead>
            <tbody class="divide-y dark:divide-gray-700">
                @foreach($therapists as $therapist)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td class="px-6 py-4 font-bold dark:text-white">{{ $therapist->name }}</td>
                    <td class="px-6 py-4 dark:text-gray-300">{{ $therapist->specialization }}</td>
                    <td class="px-6 py-4 dark:text-gray-300">
                        @if($therapist->salary_type == 'monthly') شهري ({{ $therapist->monthly_salary }}) @endif
                        @if($therapist->salary_type == 'daily') يومي ({{ $therapist->daily_salary }}) @endif
                        @if($therapist->salary_type == 'commission') عمولة ({{ $therapist->commission_rate }}%) @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $therapist->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $therapist->is_active ? 'نشط' : 'غير نشط' }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
