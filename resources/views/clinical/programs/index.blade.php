<x-app-layout>
    <x-slot name="title">البرامج العلاجية</x-slot>

    @php
        $totalPrograms = $statusCounts->sum();
        $activePrograms = $statusCounts[\App\Models\TherapyProgram::STATUS_ACTIVE] ?? 0;
        $completedPrograms = $statusCounts[\App\Models\TherapyProgram::STATUS_COMPLETED] ?? 0;
        $stoppedPrograms = $statusCounts[\App\Models\TherapyProgram::STATUS_STOPPED] ?? 0;
        $statusStyles = [
            \App\Models\TherapyProgram::STATUS_ACTIVE => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            \App\Models\TherapyProgram::STATUS_COMPLETED => 'bg-blue-100 text-blue-700 border-blue-200',
            \App\Models\TherapyProgram::STATUS_STOPPED => 'bg-rose-100 text-rose-700 border-rose-200',
        ];
    @endphp

    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">البرامج العلاجية</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">متابعة البرامج الجارية والمكتملة والمتوقفة من مكان واحد.</p>
                </div>

                <form method="GET" action="{{ route('programs.index') }}" class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    <input
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="بحث باسم الطفل أو الأخصائي"
                        class="w-full sm:w-72 border rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-gray-200"
                    >
                    <select name="status" class="border rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-gray-200">
                        @foreach($statusLabels as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg">تصفية</button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ route('programs.index', ['status' => 'all']) }}" class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-transparent hover:border-blue-300 transition">
                <p class="text-sm text-gray-500 dark:text-gray-400">كل البرامج</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $totalPrograms }}</p>
            </a>
            <a href="{{ route('programs.index', ['status' => \App\Models\TherapyProgram::STATUS_ACTIVE]) }}" class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-transparent hover:border-emerald-300 transition">
                <p class="text-sm text-gray-500 dark:text-gray-400">جارية</p>
                <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $activePrograms }}</p>
            </a>
            <a href="{{ route('programs.index', ['status' => \App\Models\TherapyProgram::STATUS_COMPLETED]) }}" class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-transparent hover:border-blue-300 transition">
                <p class="text-sm text-gray-500 dark:text-gray-400">مكتملة</p>
                <p class="text-2xl font-bold text-blue-600 mt-1">{{ $completedPrograms }}</p>
            </a>
            <a href="{{ route('programs.index', ['status' => \App\Models\TherapyProgram::STATUS_STOPPED]) }}" class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-transparent hover:border-rose-300 transition">
                <p class="text-sm text-gray-500 dark:text-gray-400">متوقفة</p>
                <p class="text-2xl font-bold text-rose-600 mt-1">{{ $stoppedPrograms }}</p>
            </a>
        </div>

        @if($programs->count() > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900 text-gray-600 dark:text-gray-300">
                            <tr>
                                <th class="text-right px-5 py-3">الحالة</th>
                                <th class="text-right px-5 py-3">الطفل والبرنامج</th>
                                <th class="text-right px-5 py-3">الأخصائي</th>
                                <th class="text-right px-5 py-3">التقدم</th>
                                <th class="text-right px-5 py-3">الجلسات</th>
                                <th class="text-right px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($programs as $program)
                                @php
                                    $latestProgress = $program->progressPoints->last();
                                    $statusClass = $statusStyles[$program->status] ?? 'bg-gray-100 text-gray-700 border-gray-200';
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-5 py-4">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full border text-xs font-semibold {{ $statusClass }}">
                                            {{ $program->status }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="font-bold text-gray-900 dark:text-white">{{ $program->patient->name }}</div>
                                        <div class="text-gray-500 dark:text-gray-400">{{ $program->name }} | {{ $program->disorder_type ?: 'غير محدد' }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-gray-700 dark:text-gray-300">
                                        {{ $program->therapist->name ?? 'غير محدد' }}
                                    </td>
                                    <td class="px-5 py-4">
                                        @if($latestProgress)
                                            <div class="w-36">
                                                <div class="flex justify-between text-xs text-gray-500 mb-1">
                                                    <span>{{ $latestProgress->domain }}</span>
                                                    <span>{{ $latestProgress->score }}%</span>
                                                </div>
                                                <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                                    <div class="h-2 bg-emerald-500 rounded-full" style="width: {{ min($latestProgress->score, 100) }}%"></div>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-gray-400">لا توجد نقاط تقدم</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-gray-700 dark:text-gray-300">
                                        {{ $program->sessions_count }} جلسة
                                    </td>
                                    <td class="px-5 py-4 text-left">
                                        <a href="{{ route('programs.show', $program) }}" class="inline-flex items-center justify-center bg-gray-900 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                                            فتح
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-4 border-t dark:border-gray-700">
                    {{ $programs->links() }}
                </div>
            </div>
        @else
            <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="mx-auto h-12 w-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6M9 9h6m-7 4h8m-8 4h5M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z" />
                    </svg>
                </div>
                <h3 class="mt-3 text-lg font-semibold text-gray-900 dark:text-gray-100">لا توجد برامج مطابقة</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">جرّب تغيير البحث أو اختيار حالة مختلفة من الفلتر.</p>
            </div>
        @endif
    </div>
</x-app-layout>
