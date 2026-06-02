<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\SessionType;
use App\Http\Requests\StoreAppointmentRequest;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        // فلترة حسب اليوم أو الأخصائي
        $dateFilter = $request->input('date', today()->format('Y-m-d'));
        $therapistFilter = $request->input('therapist_id');

        $appointments = Appointment::with('patient', 'therapist', 'sessionType')
            ->whereDate('scheduled_at', $dateFilter)
            ->when($therapistFilter, function ($query) use ($therapistFilter) {
                $query->where('therapist_id', $therapistFilter);
            })
            ->orderBy('scheduled_at')
            ->get();

        // جلب الأخصائيين للفلتر (الأخصائي هو يوزر عنده دور أخصائي)
        $therapists = \App\Models\User::role('أخصائي تخاطب')->get();

        // جلب البيانات لعمل موعد جديد
        $patients = Patient::where('is_active', true)->get();
        $sessionTypes = SessionType::all();

        return view('appointments.index', compact('appointments', 'therapists', 'patients', 'sessionTypes', 'dateFilter', 'therapistFilter'));
    }

        public function store(StoreAppointmentRequest $request)
    {
        $validated = $request->validated();

        // ١. حساب وقت الانتهاء بناءً على مدة الجلسة
        $scheduledAt = \Carbon\Carbon::parse($validated['scheduled_at']);
        $sessionType = \App\Models\SessionType::find($validated['session_type_id']);
        $endAt = $scheduledAt->copy()->addMinutes($sessionType->duration_minutes);

        // ٢. التحقق من عدم التضارب (Overlap Prevention)
        $conflict = Appointment::where('therapist_id', $validated['therapist_id'])
            ->where('status', '!=', 'ملغى') // المواعيد الملغية لا تتعارض
            ->where(function ($query) use ($scheduledAt, $endAt) {
                $query->whereBetween('scheduled_at', [$scheduledAt, $endAt])
                      ->orWhereBetween('end_at', [$scheduledAt, $endAt])
                      ->orWhere(function ($q) use ($scheduledAt, $endAt) {
                          $q->where('scheduled_at', '<=', $scheduledAt)
                            ->where('end_at', '>=', $endAt);
                      });
            })->exists();

        if ($conflict) {
            // لو فيه تضارب، ارجع لصفحة المواعيد مع رسالة خطأ
            return back()->withErrors(['therapist_id' => 'يوجد تعارض في المواعيد! الأخصائي لديه موعد آخر في هذا الوقت.'])->withInput();
        }

        // ٣. إضافة وقت الانتهاء والحالة للبيانات وحفظها
        $validated['end_at'] = $endAt;
        $validated['status'] = 'مجدول';

        Appointment::create($validated);

        return redirect()->route('appointments.index', ['date' => $scheduledAt->format('Y-m-d')])
            ->with('success', 'تم حجز الموعد بنجاح');
    }

    public function updateStatus(Appointment $appointment, Request $request)
    {
        // تحديث حالة الموعد (مكتمل / غياب / ملغى)
        $request->validate(['status' => 'required|in:مكتمل,غياب,ملغى']);

        $appointment->update(['status' => $request->status]);

        // لو الموعد اكتمل، هنحوله لجلسة علاجية وهنسجل استحقاق الأخصائي
        if ($request->status == 'مكتمل') {
            $this->convertToSession($appointment);
        }

        return back()->with('success', 'تم تحديث حالة الموعد');
    }

    // دالة تحويل الموعد لجلسة واستحقاق مالي
    protected function convertToSession(Appointment $appointment)
    {
        // ١. إنشاء جلسة علاجية
        $program = \App\Models\TherapyProgram::firstOrCreate(
            ['patient_id' => $appointment->patient_id, 'therapist_id' => $appointment->therapist_id],
            ['name' => 'برنامج علاجي', 'session_price' => $appointment->sessionType->price]
        );

        $session = \App\Models\TherapySession::create([
            'therapy_program_id' => $program->id,
            'appointment_id' => $appointment->id,
            'session_date' => $appointment->scheduled_at,
            'duration_minutes' => $appointment->sessionType->duration_minutes,
            'status' => 'مكتملة',
        ]);

        // ٢. تسجيل استحقاق الأخصائي (Commission/Session Fee)
        $therapist = \App\Models\Therapist::where('user_id', $appointment->therapist_id)->first();
        if ($therapist) {
            $amount = 0;
            $type = '';

            if ($therapist->salary_type === 'commission') {
                $amount = ($appointment->sessionType->price * $therapist->commission_rate) / 100;
                $type = 'commission';
            } elseif ($therapist->salary_type === 'daily' || $therapist->salary_type === 'monthly') {
                $amount = $appointment->sessionType->price;
                $type = 'session_fee';
            }

            if ($amount > 0) {
                \App\Models\TherapistEarning::create([
                    'therapist_id' => $therapist->id,
                    'therapy_session_id' => $session->id,
                    'amount' => $amount,
                    'type' => $type,
                ]);
            }
        }

        // ٣. الخصم التلقائي من باقة الجلسات (الإضافة الجديدة)
        $activePackage = \App\Models\SessionPackage::where('patient_id', $appointment->patient_id)
            ->where('status', 'نشط')
            ->whereColumn('used_sessions', '<', 'total_sessions')
            ->first();

        if ($activePackage) {
            // زيادة عدد الجلسات المستخدمة
            $activePackage->increment('used_sessions');

            // تسجيل استخدام الباقة
            \App\Models\PackageUsage::create([
                'session_package_id' => $activePackage->id,
                'therapy_session_id' => $session->id,
                'used_at' => now(),
            ]);

            // لو الباقة خلصت، غير حالتها لـ "مستنفد"
            if ($activePackage->fresh()->used_sessions >= $activePackage->total_sessions) {
                $activePackage->update(['status' => 'مستنفد']);
            }
        }
    }
}
