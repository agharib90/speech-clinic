<x-app-layout>
    <x-slot name="title">الاستقبال والحضور</x-slot>

    <div class="space-y-6" x-data="receptionCamera()">
        <div>
            <h2 class="text-2xl font-bold text-text">الاستقبال والحضور</h2>
        </div>

        @if(session('success'))
            <div class="rounded-lg border border-success bg-success-soft px-4 py-3 text-success" role="alert">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-lg border border-danger bg-danger-soft px-4 py-3 text-danger" role="alert">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-lg border border-danger bg-danger-soft px-4 py-3 text-danger" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <section class="rounded-lg border border-surface-border bg-surface-elevated p-5">
                <h3 class="text-base font-bold text-text">الباركود أو رمز QR</h3>

                <form action="{{ route('reception.scan') }}" method="POST" id="scan-form" class="mt-4" @submit="stopCamera">
                    @csrf
                    <label for="barcode-input" class="sr-only">الباركود أو رمز QR</label>
                    <input
                        type="text"
                        name="barcode"
                        id="barcode-input"
                        autofocus
                        autocomplete="off"
                        value="{{ old('barcode') }}"
                        placeholder="امسح أو اكتب الرمز هنا"
                        class="clinic-input w-full text-center font-mono text-lg"
                    >
                </form>

                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <button type="button" class="clinic-btn-secondary" @click="startCamera" x-show="!cameraActive">
                        فتح الكاميرا
                    </button>
                    <button type="button" class="clinic-btn-danger-soft" @click="stopCamera" x-show="cameraActive" x-cloak>
                        إغلاق الكاميرا
                    </button>
                    <span class="text-sm text-text-muted" x-text="cameraMessage" aria-live="polite"></span>
                </div>

                <div class="mt-4 overflow-hidden rounded-lg border border-surface-border bg-surface-muted" x-show="cameraActive" x-cloak>
                    <video x-ref="cameraVideo" class="aspect-video w-full object-cover" muted playsinline></video>
                </div>
            </section>

            <section class="rounded-lg border border-surface-border bg-surface-elevated p-5">
                <h3 class="text-base font-bold text-text">البحث اليدوي</h3>

                <form action="{{ route('reception.index') }}" method="GET" class="mt-4 flex gap-2">
                    <label for="patient-search" class="sr-only">بحث عن حالة</label>
                    <input
                        type="search"
                        name="search"
                        id="patient-search"
                        value="{{ request('search') }}"
                        placeholder="اسم الحالة أو الهاتف أو الكود"
                        class="clinic-input min-w-0 flex-1"
                    >
                    <button type="submit" class="clinic-btn-secondary">بحث</button>
                </form>

                @if(request()->filled('search'))
                    <div class="mt-4 divide-y divide-surface-border border-y border-surface-border">
                        @forelse($searchResults as $patient)
                            <a
                                href="{{ route('reception.index', ['patient' => $patient->id, 'source' => 'manual', 'search' => request('search')]) }}"
                                class="block px-2 py-3 transition hover:bg-primary-soft focus:outline-none focus:ring-2 focus:ring-primary"
                            >
                                <span class="block font-semibold text-text">{{ $patient->name }}</span>
                                <span class="mt-1 block text-xs text-text-muted">
                                    {{ $patient->barcode }}
                                    @if($patient->guardian)
                                        · {{ $patient->guardian->name }} · {{ $patient->guardian->phone }}
                                    @endif
                                </span>
                            </a>
                        @empty
                            <p class="py-4 text-sm text-text-muted">لا توجد نتائج مطابقة.</p>
                        @endforelse
                    </div>
                @endif
            </section>
        </div>

        @if($selectedPatient)
            <section class="border-y border-surface-border py-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold text-primary">الحالة المحددة</p>
                        <h3 class="mt-1 text-xl font-bold text-text">{{ $selectedPatient->name }}</h3>
                        <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-sm text-text-muted">
                            <span>كود الحالة: <b class="font-semibold text-text">{{ $selectedPatient->barcode }}</b></span>
                            @if($selectedPatient->guardian)
                                <span>ولي الأمر: <b class="font-semibold text-text">{{ $selectedPatient->guardian->name }}</b></span>
                                <span>الهاتف: <b class="font-semibold text-text">{{ $selectedPatient->guardian->phone }}</b></span>
                            @endif
                        </div>
                    </div>
                    <span class="self-start rounded-full bg-primary-soft px-3 py-1 text-xs font-semibold text-primary">
                        {{ $todayAppointments->count() }} موعد اليوم
                    </span>
                </div>

                <h4 class="mt-6 text-base font-bold text-text">مواعيد اليوم</h4>
                <div class="mt-3 space-y-3">
                    @forelse($todayAppointments as $appointment)
                        @php
                            $serviceName = $appointment->patientServicePlanItem?->service?->name
                                ?? $appointment->sessionType?->name
                                ?? 'خدمة غير محددة';
                            $attended = (bool) $appointment->checkin;
                            $earliestConfirmAt = $appointment->scheduled_at->copy()->subMinutes($attendanceEarlyArrivalMinutes);
                            $attendanceWindowOpen = now()->greaterThanOrEqualTo($earliestConfirmAt);
                            $confirmable = $appointment->status === 'مجدول' && ! $attended && $attendanceWindowOpen;
                            $statusClass = match ($appointment->status) {
                                'مجدول' => 'bg-primary-soft text-primary',
                                'مكتمل' => 'bg-success-soft text-success',
                                'غياب' => 'bg-warning-soft text-warning',
                                default => 'bg-danger-soft text-danger',
                            };
                        @endphp
                        <article class="grid gap-4 rounded-lg border border-surface-border bg-surface-elevated p-4 md:grid-cols-[7rem_minmax(0,1fr)_auto] md:items-center">
                            <div>
                                <p class="text-lg font-bold text-text">{{ $appointment->scheduled_at->format('h:i A') }}</p>
                                <p class="text-xs text-text-muted">{{ $appointment->scheduled_at->translatedFormat('l') }}</p>
                            </div>
                            <div class="min-w-0">
                                <h5 class="font-semibold text-text">{{ $serviceName }}</h5>
                                <p class="mt-1 text-sm text-text-muted">الأخصائي: {{ $appointment->therapist?->name ?? 'غير محدد' }}</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $appointment->status }}</span>
                                    @if($attended)
                                        <span class="rounded-full bg-success-soft px-2.5 py-1 text-xs font-semibold text-success">تم تأكيد الحضور</span>
                                    @else
                                        <span class="rounded-full bg-surface-muted px-2.5 py-1 text-xs font-semibold text-text-muted">لم يؤكد الحضور</span>
                                    @endif
                                </div>
                            </div>
                            <div class="md:justify-self-end">
                                @if($confirmable)
                                    <form action="{{ route('reception.attendance.confirm', $appointment) }}" method="POST" x-data="{ submitting: false }" @submit="submitting = true">
                                        @csrf
                                        <input type="hidden" name="patient_id" value="{{ $selectedPatient->id }}">
                                        <input type="hidden" name="method" value="{{ $identificationMethod }}">
                                        <button type="submit" class="clinic-btn-primary w-full md:w-auto" :disabled="submitting" x-text="submitting ? 'جارٍ التأكيد...' : 'تأكيد الحضور'">
                                            تأكيد الحضور
                                        </button>
                                    </form>
                                @elseif($appointment->status === 'مجدول' && ! $attended && ! $attendanceWindowOpen)
                                    <span class="text-sm font-medium text-warning">
                                        يتاح تأكيد الحضور من {{ $earliestConfirmAt->format('h:i A') }}
                                    </span>
                                @elseif(! $attended)
                                    <span class="text-sm font-medium text-text-muted">غير متاح للتأكيد</span>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="rounded-lg border border-dashed border-surface-border bg-surface-muted px-4 py-8 text-center text-text-muted">
                            لا توجد مواعيد مسجلة لهذه الحالة اليوم.
                        </div>
                    @endforelse
                </div>
            </section>
        @endif

        <section>
            <div class="flex items-center justify-between gap-4">
                <h3 class="text-base font-bold text-text">داخل العيادة الآن</h3>
                <span class="text-sm font-semibold text-text-muted">{{ $currentCheckins->count() }}</span>
            </div>

            <div class="mt-3 overflow-x-auto rounded-lg border border-surface-border">
                <table class="clinic-table">
                    <thead>
                        <tr>
                            <th>الحالة</th>
                            <th>الموعد</th>
                            <th>وقت الدخول</th>
                            <th class="text-left">الإجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($currentCheckins as $checkin)
                            @php
                                $checkinService = $checkin->appointment?->patientServicePlanItem?->service?->name
                                    ?? $checkin->appointment?->sessionType?->name;
                            @endphp
                            <tr>
                                <td class="font-semibold text-text">{{ $checkin->patient->name }}</td>
                                <td class="text-text-muted">
                                    @if($checkin->appointment)
                                        {{ $checkinService ?? 'خدمة غير محددة' }} · {{ $checkin->appointment->scheduled_at->format('h:i A') }}
                                    @else
                                        حضور حر سابق
                                    @endif
                                </td>
                                <td class="text-text-muted">{{ $checkin->checkin_at->format('h:i A') }}</td>
                                <td class="text-left">
                                    <form action="{{ route('reception.checkout', $checkin) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="clinic-btn-danger-soft">تسجيل الانصراف</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-text-muted">لا توجد حالات داخل العيادة حاليًا.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('receptionCamera', () => ({
                cameraActive: false,
                cameraMessage: '',
                stream: null,
                detector: null,
                frameRequest: null,

                async startCamera() {
                    this.cameraMessage = '';

                    if (!('BarcodeDetector' in window) || !navigator.mediaDevices?.getUserMedia) {
                        this.cameraMessage = 'قراءة QR بالكاميرا غير مدعومة هنا. استخدم الماسح أو الإدخال اليدوي.';
                        return;
                    }

                    try {
                        const supportedFormats = await BarcodeDetector.getSupportedFormats();
                        const formats = ['qr_code', 'code_128'].filter((format) => supportedFormats.includes(format));

                        if (formats.length === 0) {
                            this.cameraMessage = 'قراءة QR بالكاميرا غير مدعومة هنا. استخدم الماسح أو الإدخال اليدوي.';
                            return;
                        }

                        this.detector = new BarcodeDetector({ formats });
                        this.stream = await navigator.mediaDevices.getUserMedia({
                            video: { facingMode: { ideal: 'environment' } },
                            audio: false,
                        });
                        this.$refs.cameraVideo.srcObject = this.stream;
                        await this.$refs.cameraVideo.play();
                        this.cameraActive = true;
                        this.cameraMessage = 'وجّه الكاميرا نحو الرمز.';
                        this.detectCode();
                    } catch (error) {
                        this.stopCamera();
                        this.cameraMessage = error?.name === 'NotAllowedError'
                            ? 'لم يتم السماح باستخدام الكاميرا. استخدم الماسح أو الإدخال اليدوي.'
                            : 'تعذر تشغيل الكاميرا. استخدم الماسح أو الإدخال اليدوي.';
                    }
                },

                async detectCode() {
                    if (!this.cameraActive || !this.detector) return;

                    try {
                        const codes = await this.detector.detect(this.$refs.cameraVideo);
                        if (codes.length > 0 && codes[0].rawValue) {
                            document.getElementById('barcode-input').value = codes[0].rawValue;
                            this.stopCamera();
                            document.getElementById('scan-form').requestSubmit();
                            return;
                        }
                    } catch (error) {
                        this.cameraMessage = 'تعذر قراءة الرمز. قرّب الكاميرا أو استخدم الإدخال اليدوي.';
                    }

                    this.frameRequest = requestAnimationFrame(() => this.detectCode());
                },

                stopCamera() {
                    if (this.frameRequest) cancelAnimationFrame(this.frameRequest);
                    this.stream?.getTracks().forEach((track) => track.stop());
                    this.stream = null;
                    this.detector = null;
                    this.frameRequest = null;
                    this.cameraActive = false;
                },

                init() {
                    window.addEventListener('beforeunload', () => this.stopCamera(), { once: true });
                },
            }));
        });
    </script>
</x-app-layout>
