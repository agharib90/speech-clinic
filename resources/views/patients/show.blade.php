<x-app-layout>
    <x-slot name="title">ملف المريض</x-slot>

    <div class="max-w-4xl mx-auto">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                {{ session('success') }}
            </div>
        @endif
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow mb-6">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ $patient->name }}</h2>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">ولي الأمر: <span class="font-semibold">{{ $patient->guardian->name }}</span></p>
                    <p class="text-gray-600 dark:text-gray-400">العمر: {{ $patient->birth_date->age }} سنة | التشخيص: {{ $patient->diagnosis }}</p>
                </div>
                <div class="flex space-x-reverse space-x-2">
                    <a href="{{ route('patients.workspace', $patient) }}" class="clinic-btn-primary">ملف الحالة الذكي</a>
                    <a href="{{ route('patients.print-card', $patient) }}" target="_blank" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">طباعة البطاقة</a>
                    <a href="{{ route('patients.edit', $patient) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg">تعديل</a>
                </div>
            </div>
        </div>

        @can('manage patient service plans')
            <div class="clinic-card mb-6 flex flex-col justify-between gap-3 p-5 sm:flex-row sm:items-center">
                <div><h3 class="font-bold text-text">خطة الخدمات</h3><p class="mt-1 text-sm text-text-muted">الخدمات المرتبة والأسعار والوحدات المدفوعة والرصيد النقدي.</p></div>
                <a href="{{ route('patients.service-plans.index', $patient) }}" class="clinic-btn-primary">فتح خطط الخدمات</a>
            </div>
        @endcan

        <!-- قسم الباركود والـ QR Code -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- الباركود الخطي -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow text-center">
                <h3 class="font-bold mb-4 text-gray-700 dark:text-gray-300">الباركود (للاستقبال)</h3>
                <div class="p-4 bg-white inline-block rounded">
                    @php
                        $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
                    @endphp
                    {!! $generator->getBarcode($patient->barcode, $generator::TYPE_CODE_128, 2, 60) !!}
                </div>
                <p class="mt-2 font-mono text-sm text-gray-500">{{ $patient->barcode }}</p>
            </div>

            <!-- الـ QR Code -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow text-center">
                <h3 class="font-bold mb-4 text-gray-700 dark:text-gray-300">QR Code (للموبايل)</h3>
                <div class="p-4 bg-white inline-block rounded">
                    {!!
                        QrCode::size(150)->generate($patient->qr_code)
                    !!}
                </div>
                <p class="mt-2 font-mono text-sm text-gray-500">{{ $patient->qr_code }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow mb-6">
            <div class="flex items-center justify-between gap-4 mb-4">
                <h3 class="font-bold text-gray-800 dark:text-gray-200">التاريخ التطوري وتاريخ الحالة</h3>
                @if($patient->caseHistory?->taken_at)
                    <span class="text-xs px-3 py-1 rounded-full bg-blue-100 text-blue-700">
                        آخر تحديث: {{ $patient->caseHistory->taken_at->format('Y-m-d') }}
                    </span>
                @endif
            </div>

            @can('edit patients')
                <form action="{{ route('patients.case-history.store', $patient) }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="block">
                            <span class="text-sm text-gray-700 dark:text-gray-300">تاريخ المقابلة</span>
                            <input type="date" name="taken_at" value="{{ old('taken_at', optional($patient->caseHistory?->taken_at)->format('Y-m-d') ?? now()->toDateString()) }}" class="mt-1 w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-gray-200">
                        </label>
                        <label class="block">
                            <span class="text-sm text-gray-700 dark:text-gray-300">البيئة اللغوية في المنزل</span>
                            <textarea name="language_environment" rows="2" class="mt-1 w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-gray-200">{{ old('language_environment', $patient->caseHistory->language_environment ?? '') }}</textarea>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="block">
                            <span class="text-sm text-gray-700 dark:text-gray-300">الشكوى الرئيسية</span>
                            <textarea name="main_concerns" rows="3" class="mt-1 w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-gray-200">{{ old('main_concerns', $patient->caseHistory->main_concerns ?? '') }}</textarea>
                        </label>
                        <label class="block">
                            <span class="text-sm text-gray-700 dark:text-gray-300">الحمل والولادة</span>
                            <textarea name="prenatal_history" rows="3" class="mt-1 w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-gray-200">{{ old('prenatal_history', $patient->caseHistory->prenatal_history ?? '') }}</textarea>
                        </label>
                        <label class="block">
                            <span class="text-sm text-gray-700 dark:text-gray-300">تاريخ الولادة</span>
                            <textarea name="birth_history" rows="3" class="mt-1 w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-gray-200">{{ old('birth_history', $patient->caseHistory->birth_history ?? '') }}</textarea>
                        </label>
                        <label class="block">
                            <span class="text-sm text-gray-700 dark:text-gray-300">المراحل التطورية</span>
                            <textarea name="developmental_milestones" rows="3" class="mt-1 w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-gray-200">{{ old('developmental_milestones', $patient->caseHistory->developmental_milestones ?? '') }}</textarea>
                        </label>
                        <label class="block">
                            <span class="text-sm text-gray-700 dark:text-gray-300">التاريخ الطبي</span>
                            <textarea name="medical_history" rows="3" class="mt-1 w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-gray-200">{{ old('medical_history', $patient->caseHistory->medical_history ?? '') }}</textarea>
                        </label>
                        <label class="block">
                            <span class="text-sm text-gray-700 dark:text-gray-300">السمع والبصر</span>
                            <textarea name="hearing_vision_notes" rows="3" class="mt-1 w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-gray-200">{{ old('hearing_vision_notes', $patient->caseHistory->hearing_vision_notes ?? '') }}</textarea>
                        </label>
                        <label class="block">
                            <span class="text-sm text-gray-700 dark:text-gray-300">التاريخ الأسري</span>
                            <textarea name="family_history" rows="3" class="mt-1 w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-gray-200">{{ old('family_history', $patient->caseHistory->family_history ?? '') }}</textarea>
                        </label>
                        <label class="block">
                            <span class="text-sm text-gray-700 dark:text-gray-300">تدخلات سابقة</span>
                            <textarea name="previous_interventions" rows="3" class="mt-1 w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-gray-200">{{ old('previous_interventions', $patient->caseHistory->previous_interventions ?? '') }}</textarea>
                        </label>
                    </div>

                    <label class="block">
                        <span class="text-sm text-gray-700 dark:text-gray-300">ملاحظات عامة</span>
                        <textarea name="notes" rows="3" class="mt-1 w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-gray-200">{{ old('notes', $patient->caseHistory->notes ?? '') }}</textarea>
                    </label>

                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg">حفظ تاريخ الحالة</button>
                </form>
            @else
                @if($patient->caseHistory)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700 dark:text-gray-300">
                        <p><span class="font-bold">الشكوى الرئيسية:</span> {{ $patient->caseHistory->main_concerns }}</p>
                        <p><span class="font-bold">المراحل التطورية:</span> {{ $patient->caseHistory->developmental_milestones }}</p>
                        <p><span class="font-bold">التاريخ الطبي:</span> {{ $patient->caseHistory->medical_history }}</p>
                        <p><span class="font-bold">تدخلات سابقة:</span> {{ $patient->caseHistory->previous_interventions }}</p>
                    </div>
                @else
                    <p class="text-sm text-gray-500">لا يوجد تاريخ حالة مسجل بعد.</p>
                @endif
            @endcan
        </div>

        @if($patient->therapyPrograms->count())
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow mb-6">
                <h3 class="font-bold text-gray-800 dark:text-gray-200 mb-4">البرامج العلاجية</h3>
                <div class="divide-y dark:divide-gray-700">
                    @foreach($patient->therapyPrograms as $program)
                        <div class="py-3 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                            <div>
                                <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $program->name }}</p>
                                <p class="text-sm text-gray-500">الأخصائي: {{ $program->therapist->name ?? 'غير محدد' }} | الحالة: {{ $program->status }}</p>
                            </div>
                            @can('view therapy')
                                <a href="{{ route('programs.show', $program) }}" class="text-blue-600 hover:underline text-sm">فتح الأدوات الإكلينيكية</a>
                            @endcan
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
