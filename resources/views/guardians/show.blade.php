<x-app-layout>
    <x-slot name="title">ملف ولي الأمر</x-slot>

    <div class="max-w-4xl mx-auto">
        <!-- بطاقة ولي الأمر -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow mb-6">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ $guardian->name }}</h2>
                    <p class="text-gray-600 dark:text-gray-400 mt-2"><span class="font-bold">الهاتف:</span> {{ $guardian->phone }}</p>
                    <p class="text-gray-600 dark:text-gray-400"><span class="font-bold">الرقم الوطني:</span> {{ $guardian->national_id }}</p>
                </div>
                <a href="{{ route('guardians.edit', $guardian) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg">تعديل البيانات</a>
            </div>
        </div>

        <!-- قسم الأبناء (المرضى) -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200">الأبناء المسجلين</h3>
                <!-- هنا هنحط لينك إضافة مريض جديد لولي الأمر في الخطوة الجاية -->
            </div>

            @if($guardian->patients->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($guardian->patients as $patient)
                        <div class="border dark:border-gray-700 rounded-lg p-4 flex items-center justify-between">
                            <div>
                                <h4 class="font-bold text-gray-900 dark:text-white">{{ $patient->name }}</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400">العمر: {{ $patient->birth_date->age }} سنة - {{ $patient->diagnosis }}</p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $patient->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $patient->is_active ? 'نشط' : 'غير نشط' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">لا يوجد أبناء مسجلين لهذا ولي الأمر بعد.</p>
            @endif
        </div>
    </div>
</x-app-layout>
