<x-app-layout>
    <x-slot name="title">شاشة الاستقبال والحضور</x-slot>

    <!-- رسائل النجاح والخطأ -->
    <div class="mb-6">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative text-lg" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative text-lg" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- القسم الأيمن: مربع مسح الباركود -->
        <div class="lg:col-span-1 bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h2 class="text-xl font-bold mb-4 text-gray-800 dark:text-gray-200">مسح الباركود</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-4 text-sm">وجّه الماسح الضوئي نحو الباركود، أو اكتب الرقم واضغط Enter.</p>

            <form action="{{ route('reception.scan') }}" method="POST" id="scanForm">
                @csrf
                <!-- حقل الباركود - الفوكوس التلقائي هنا هو السحر -->
                <input type="text"
                       name="barcode"
                       id="barcode-input"
                       autofocus
                       autocomplete="off"
                       placeholder="امسح الباركود هنا..."
                       class="w-full text-3xl text-center font-mono border-2 border-blue-500 rounded-lg px-4 py-6 dark:bg-gray-700 dark:border-blue-400 dark:text-gray-200 focus:ring-4 focus:ring-blue-300 focus:outline-none">
            </form>
        </div>

        <!-- القسم الأيسر: من في العيادة الآن -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h2 class="text-xl font-bold mb-4 text-gray-800 dark:text-gray-200">داخل العيادة الآن ({{ $currentCheckins->count() }})</h2>

            @if($currentCheckins->count() > 0)
                <div class="space-y-3">
                    @foreach($currentCheckins as $checkin)
                        <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 p-4 rounded-lg border-r-4 border-green-500">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $checkin->patient->name }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    وقت الدخول: {{ $checkin->checkin_at->format('h:i A') }}
                                    <span class="mx-2">|</span>
                                    مدة الانتظار: <span class="font-semibold text-yellow-600">{{ $checkin->checkin_at->diffInMinutes(now()) }} دقيقة</span>
                                </p>
                                @if($checkin->appointment)
                                    <p class="text-xs text-blue-600 mt-1">لديه موعد مع: {{ $checkin->appointment->therapist->name ?? 'أخصائي' }}</p>
                                @else
                                    <p class="text-xs text-red-500 mt-1">حضور حر (لا يوجد موعد مجدول اليوم)</p>
                                @endif
                            </div>

                            <!-- زر تسجيل انصراف يدوي -->
                            <form action="{{ route('reception.checkout', $checkin) }}" method="POST">
                                @csrf
                                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition">
                                    تسجيل انصراف
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-10 text-gray-500 dark:text-gray-400">
                    <p class="text-2xl">لا يوجد حالات داخل العيادة حالياً</p>
                </div>
            @endif
        </div>

    </div>

    <!-- سكربت صغير عشان يرجع الفوكوس لمربع الباركود بعد كل عملية -->
    <script>
        window.onload = function() {
            document.getElementById('barcode-input').focus();
        };
    </script>
</x-app-layout>
