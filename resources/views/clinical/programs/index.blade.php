<x-app-layout>
    <x-slot name="title">البرامج العلاجية الجارية</x-slot>

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">البرامج العلاجية</h2>
    </div>

    @if($programs->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($programs as $program)
            <a href="{{ route('programs.show', $program) }}" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow hover:shadow-lg transition border-r-4 border-blue-500">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $program->patient->name }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">الأخصائي: {{ $program->therapist->name }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">الاضطراب: {{ $program->disorder_type }}</p>
                <div class="mt-4">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">{{ $program->status }}</span>
                </div>
            </a>
            @endforeach
        </div>
    @else
        <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-lg shadow">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900 dark:text-gray-100">لا توجد برامج علاجية جارية حالياً</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">يتم إنشاء البرنامج العلاجي تلقائياً بمجرد تحويل أي موعد إلى حالة "مكتمل" من شاشة المواعيد.</p>
        </div>
    @endif
</x-app-layout>
