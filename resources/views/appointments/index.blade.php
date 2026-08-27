<x-app-layout>
    <x-slot name="title">جدول المواعيد</x-slot>

    <!-- رسائل النجاح والخطأ -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-lg border border-danger bg-danger-soft px-4 py-3 text-danger" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- القسم الأيمن: إضافة موعد جديد سريع -->
        <div class="lg:col-span-1 bg-white dark:bg-gray-800 p-6 rounded-lg shadow h-fit">
            <h2 class="mb-1 text-xl font-bold text-gray-800 dark:text-gray-200">حجز من خطة الخدمات</h2>
            <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">يتطلب الحجز تغطية مالية مؤكدة في خطة المريض.</p>

            @include('appointments._service-plan-form', [
                'workspaceMode' => false,
                'formAction' => route('appointments.store'),
            ])

            @if(auth()->user()->can('create appointments') && auth()->user()->can('create legacy appointments'))
            <details class="mt-5 border-t border-surface-border pt-4">
                <summary class="cursor-pointer text-sm font-medium text-text-muted">حجز استثنائي بالطريقة القديمة</summary>
                <p class="mt-2 text-xs text-text-muted">يُستخدم فقط للمواعيد القديمة أو الحالات الإدارية الاستثنائية، ولا يمر بمسار مقدم تأكيد الموعد.</p>
                <form action="{{ route('appointments.store') }}" method="POST" class="mt-4 space-y-4">@csrf
                    <select name="patient_id" class="clinic-field w-full" required><option value="">اختر المريض...</option>@foreach($patients as $patient)<option value="{{ $patient->id }}">{{ $patient->name }}</option>@endforeach</select>
                    <select name="therapist_id" class="clinic-field w-full" required><option value="">اختر الأخصائي...</option>@foreach($therapists as $therapist)<option value="{{ $therapist->id }}">{{ $therapist->name }}</option>@endforeach</select>
                    <select name="session_type_id" class="clinic-field w-full" required>@foreach($sessionTypes as $type)<option value="{{ $type->id }}">{{ $type->name }} ({{ $type->duration_minutes }} د)</option>@endforeach</select>
                    <input type="datetime-local" name="scheduled_at" class="clinic-field w-full" required>
                    <textarea name="legacy_booking_reason" rows="2" class="clinic-field w-full" placeholder="سبب الحجز الاستثنائي" required></textarea>
                    <textarea name="notes" rows="2" class="clinic-field w-full" placeholder="ملاحظات"></textarea>
                    <button type="submit" class="clinic-btn-secondary w-full">تسجيل الحجز الاستثنائي</button>
                </form>
            </details>
            @endif
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
                                        <span class="text-primary">{{ $appointment->patientServicePlanItem?->service?->name ?? $appointment->sessionType?->name ?? 'خدمة غير محددة' }}</span>
                                    </p>
                                    @if($appointment->financially_confirmed_at)
                                        <p class="mt-1 text-xs text-success">مؤكد ماليًا · مقدم {{ $appointment->confirmation_deposit_amount_snapshot }} ج.م</p>
                                    @endif
                                    @if(! $appointment->patient_service_plan_item_id && $appointment->legacy_booking_reason)
                                        <span class="mt-1 inline-flex rounded-full bg-warning-soft px-2 py-0.5 text-xs font-medium text-warning">حجز استثنائي</span>
                                    @endif
                                </div>
                            </div>

                            <!-- حالة الموعد وتغييرها -->
                            <div class="flex items-center space-x-reverse space-x-2">
                                @if($appointment->status == 'مجدول')
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">مجدول</span>

                                    @if($appointment->patient_service_plan_item_id)
                                        @php($completionState = $appointment->service_completion_state)
                                        @if($appointment->checkin)
                                            <span class="rounded-full bg-success-soft px-2 py-1 text-xs font-semibold text-success">تم تأكيد الحضور</span>
                                        @endif
                                        <span class="px-2 py-1 text-xs text-text-muted">{{ $completionState['label'] }}</span>
                                        @if($completionState['can_complete'])
                                            <form action="{{ route('appointments.complete-service', $appointment) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="clinic-btn-primary">إتمام الخدمة</button>
                                            </form>
                                        @endif
                                        <form action="{{ route('appointments.updateStatus', $appointment) }}" method="POST" class="flex gap-2">
                                            @csrf
                                            <button type="submit" name="status" value="غياب" class="rounded-lg bg-warning-soft px-2 py-1 text-xs font-semibold text-warning">غياب</button>
                                            <button type="submit" name="status" value="ملغى" class="rounded-lg bg-danger-soft px-2 py-1 text-xs font-semibold text-danger">إلغاء</button>
                                        </form>
                                    @else
                                        <form action="{{ route('appointments.updateStatus', $appointment) }}" method="POST">
                                            @csrf
                                            <button type="submit" name="status" value="مكتمل" class="text-xs bg-green-500 hover:bg-green-600 text-white px-2 py-1 rounded">مكتمل</button>
                                            <button type="submit" name="status" value="غياب" class="text-xs bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded">غياب</button>
                                            <button type="submit" name="status" value="ملغى" class="text-xs bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded">إلغاء</button>
                                        </form>
                                    @endif
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
