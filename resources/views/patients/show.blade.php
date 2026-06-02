<x-app-layout>
    <x-slot name="title">ملف المريض</x-slot>

    <div class="max-w-4xl mx-auto">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow mb-6">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ $patient->name }}</h2>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">ولي الأمر: <span class="font-semibold">{{ $patient->guardian->name }}</span></p>
                    <p class="text-gray-600 dark:text-gray-400">العمر: {{ $patient->birth_date->age }} سنة | التشخيص: {{ $patient->diagnosis }}</p>
                </div>
                <div class="flex space-x-reverse space-x-2">
                    <a href="{{ route('patients.print-card', $patient) }}" target="_blank" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">طباعة البطاقة</a>
                    <a href="{{ route('patients.edit', $patient) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg">تعديل</a>
                </div>
            </div>
        </div>

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
    </div>
</x-app-layout>
