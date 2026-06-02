<x-app-layout>
    <x-slot name="title">جدول المواعيد</x-slot>

    <!-- رسائل النجاح والخطأ -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- القسم الأيمن: إضافة موعد جديد سريع -->
        <div class="lg:col-span-1 bg-white dark:bg-gray-800 p-6 rounded-lg shadow h-fit">
            <h2 class="text-xl font-bold mb-4 text-gray-800 dark:text-gray-200">حجز موعد جديد</h2>

            <form action="{{ route('appointments.store') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">المريض *</label>
                        <select name="patient_id" class="w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 @error('patient_id') border-red-500 @enderror" required>
                            <option value="">اختر المريض...</option>
                            @foreach($patients as $patient)
                                <option value="{{ $patient->id }}">{{ $patient->name }}</option>
                            @endforeach
                        </select>
                        @error('patient_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الأخصائي *</label>
                        <select name="therapist_id" class="w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 @error('therapist_id') border-red-500 @enderror" required>
                            <option value="">اختر الأخصائي...</option>
                            @foreach($therapists as $therapist)
                                <option value="{{ $therapist->id }}">{{ $therapist->name }}</option>
                            @endforeach
                        </select>
                        @error('therapist_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">نوع الجلسة *</label>
                        <select name="session_type_id" class="w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" required>
                            @foreach($sessionTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }} ({{ $type->duration_minutes }} د)</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">موعد البدء *</label>
                        <input type="datetime-local" name="scheduled_at" class="w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" required>
                        @error('scheduled_at') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        @error('therapist_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror {{-- عشان رسالة التضارب بتظهر هنا --}}
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ملاحظات</label>
                        <textarea name="notes" rows="2" class="w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"></textarea>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg">حجز الموعد</button>
                </div>
            </form>
        </div>

        <!-- القسم الأيسر: جدول المواعيد اليومية -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 p-6 rounded-lg shadow">

            <!-- فلتر التاريخ والأخصائي -->
            <form action="{{ route('appointments.index') }}" method="GET" class="flex flex-wrap gap-4 mb-6 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">اليوم</label>
                    <input type="date" name="date" value="{{ $dateFilter }}" class="border rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-gray-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الأخصائي</label>
                    <select name="therapist_id" class="border rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-gray-200">
                        <option value="">كل الأخصائيين</option>
                        @foreach($therapists as $therapist)
                            <option value="{{ $therapist->id }}" {{ $therapistFilter == $therapist->id ? 'selected' : '' }}>{{ $therapist->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">عرض</button>
            </form>

            <h2 class="text-xl font-bold mb-4 text-gray-800 dark:text-gray-200">مواعيد يوم: {{ \Carbon\Carbon::parse($dateFilter)->format('Y-m-d') }}</h2>

            @if($appointments->count() > 0)
                <div class="space-y-3">
                    @foreach($appointments as $appointment)
                        <div class="flex items-center justify-between p-4 rounded-lg border dark:border-gray-700 {{ $appointment->status == 'ملغى' ? 'opacity-50 bg-gray-100 dark:bg-gray-900' : 'bg-gray-50 dark:bg-gray-700' }}">
                            <div class="flex items-center space-x-reverse space-x-4">
                                <!-- وقت الموعد -->
                                <div class="text-center bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 py-2 px-3 rounded-lg min-w-[80px]">
                                    <div class="text-lg font-bold">{{ \Carbon\Carbon::parse($appointment->scheduled_at)->format('h:i') }}</div>
                                    <div class="text-xs">{{ \Carbon\Carbon::parse($appointment->scheduled_at)->format('A') }}</div>
                                </div>

                                <!-- بيانات الموعد -->
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-white">{{ $appointment->patient->name }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $appointment->therapist->name }} -
                                        <span style="color: {{ $appointment->sessionType->color }}">{{ $appointment->sessionType->name }}</span>
                                    </p>
                                </div>
                            </div>

                            <!-- حالة الموعد وتغييرها -->
                            <div class="flex items-center space-x-reverse space-x-2">
                                @if($appointment->status == 'مجدول')
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">مجدول</span>

                                    <!-- أزرار تغيير الحالة -->
                                    <form action="{{ route('appointments.updateStatus', $appointment) }}" method="POST">
                                        @csrf
                                        <button type="submit" name="status" value="مكتمل" class="text-xs bg-green-500 hover:bg-green-600 text-white px-2 py-1 rounded">مكتمل</button>
                                        <button type="submit" name="status" value="غياب" class="text-xs bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded">غياب</button>
                                        <button type="submit" name="status" value="ملغى" class="text-xs bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded">إلغاء</button>
                                    </form>
                                @elseif($appointment->status == 'مكتمل')
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">مكتمل</span>
                                @elseif($appointment->status == 'غياب')
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">غياب</span>
                                @else
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">ملغى</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-10 text-gray-500 dark:text-gray-400">
                    <p class="text-xl">لا توجد مواعيد في هذا اليوم</p>
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
