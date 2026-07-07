<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\PackageUsage;
use App\Models\Patient;
use App\Models\SessionPackage;
use App\Models\SessionType;
use App\Models\Therapist;
use App\Models\TherapistEarning;
use App\Models\TherapyProgram;
use App\Models\TherapySession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $dateFilter = $request->input('date', today()->format('Y-m-d'));
        $therapistFilter = $request->input('therapist_id');

        if ($user->hasRole('أخصائي تخاطب')) {
            $therapistFilter = $user->id;
        }

        $appointments = Appointment::with('patient', 'therapist', 'sessionType')
            ->whereDate('scheduled_at', $dateFilter)
            ->when($therapistFilter, function ($query) use ($therapistFilter) {
                $query->where('therapist_id', $therapistFilter);
            })
            ->orderBy('scheduled_at')
            ->get();

        $therapists = User::role('أخصائي تخاطب')
            ->when($user->hasRole('أخصائي تخاطب'), function ($query) use ($user) {
                $query->whereKey($user->id);
            })
            ->get();

        $patients = Patient::where('is_active', true)
            ->when($user->hasRole('أخصائي تخاطب'), function ($query) use ($user) {
                $query->where(function ($patientQuery) use ($user) {
                    $patientQuery->whereHas('therapyPrograms', function ($programQuery) use ($user) {
                        $programQuery->where('therapist_id', $user->id);
                    })->orWhereHas('appointments', function ($appointmentQuery) use ($user) {
                        $appointmentQuery->where('therapist_id', $user->id);
                    });
                });
            })
            ->get();
        $sessionTypes = SessionType::all();

        return view('appointments.index', compact('appointments', 'therapists', 'patients', 'sessionTypes', 'dateFilter', 'therapistFilter'));
    }

    public function store(StoreAppointmentRequest $request)
    {
        $validated = $request->validated();
        $scheduledAt = Carbon::parse($validated['scheduled_at']);
        $sessionType = SessionType::findOrFail($validated['session_type_id']);
        $endAt = $scheduledAt->copy()->addMinutes($sessionType->duration_minutes);

        $conflict = Appointment::where('therapist_id', $validated['therapist_id'])
            ->where('status', '!=', 'ملغى')
            ->where(function ($query) use ($scheduledAt, $endAt) {
                $query->where('scheduled_at', '<', $endAt)
                    ->where('end_at', '>', $scheduledAt);
            })
            ->exists();

        if ($conflict) {
            return back()
                ->withErrors(['therapist_id' => 'يوجد تعارض في المواعيد، الأخصائي لديه موعد آخر في هذا الوقت.'])
                ->withInput();
        }

        $validated['end_at'] = $endAt;
        $validated['status'] = 'مجدول';

        Appointment::create($validated);

        return redirect()->route('appointments.index', ['date' => $scheduledAt->format('Y-m-d')])
            ->with('success', 'تم حجز الموعد بنجاح');
    }

    public function updateStatus(Appointment $appointment, Request $request)
    {
        $this->authorizeAppointmentAccess($appointment);

        $request->validate(['status' => 'required|in:مكتمل,غياب,ملغى']);

        if ($request->status === 'مكتمل') {
            $this->completeAppointment($appointment);
        } else {
            $appointment->update(['status' => $request->status]);
        }

        return back()->with('success', 'تم تحديث حالة الموعد');
    }

    public function convertToSession(Appointment $appointment)
    {
        $this->authorizeAppointmentAccess($appointment);

        $this->completeAppointment($appointment);

        return back()->with('success', 'تم تحويل الموعد إلى جلسة علاجية');
    }

    protected function completeAppointment(Appointment $appointment)
    {
        return DB::transaction(function () use ($appointment) {
            $appointment = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            $appointment->load('sessionType');

            $appointment->update(['status' => 'مكتمل']);

            if ($appointment->therapySession()->exists()) {
                return $appointment->therapySession;
            }

            return $this->createSessionFromAppointment($appointment);
        });
    }

    protected function createSessionFromAppointment(Appointment $appointment)
    {
        $program = TherapyProgram::where('patient_id', $appointment->patient_id)
            ->where('therapist_id', $appointment->therapist_id)
            ->where('status', TherapyProgram::STATUS_ACTIVE)
            ->first();

        if (! $program) {
            $program = TherapyProgram::create([
                'patient_id' => $appointment->patient_id,
                'therapist_id' => $appointment->therapist_id,
                'name' => 'برنامج علاجي',
                'status' => TherapyProgram::STATUS_ACTIVE,
                'start_date' => Carbon::parse($appointment->scheduled_at)->toDateString(),
                'session_price' => $appointment->sessionType->price,
            ]);
        }

        $sessionNumber = TherapySession::where('therapy_program_id', $program->id)->max('session_number') + 1;

        $session = TherapySession::create([
            'therapy_program_id' => $program->id,
            'appointment_id' => $appointment->id,
            'session_date' => Carbon::parse($appointment->scheduled_at)->toDateString(),
            'session_number' => $sessionNumber,
            'duration_minutes' => $appointment->sessionType->duration_minutes,
            'status' => 'مكتملة',
        ]);

        $therapist = Therapist::where('user_id', $appointment->therapist_id)->first();
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
                TherapistEarning::create([
                    'therapist_id' => $therapist->id,
                    'therapy_session_id' => $session->id,
                    'amount' => $amount,
                    'type' => $type,
                ]);
            }
        }

        $activePackage = SessionPackage::where('patient_id', $appointment->patient_id)
            ->where('status', 'نشط')
            ->whereColumn('used_sessions', '<', 'total_sessions')
            ->lockForUpdate()
            ->first();

        if ($activePackage) {
            $activePackage->increment('used_sessions');

            PackageUsage::create([
                'session_package_id' => $activePackage->id,
                'therapy_session_id' => $session->id,
                'used_at' => now(),
            ]);

            if ($activePackage->fresh()->used_sessions >= $activePackage->total_sessions) {
                $activePackage->update(['status' => 'مستنفد']);
            }
        }

        return $session;
    }

    private function authorizeAppointmentAccess(Appointment $appointment): void
    {
        $user = auth()->user();

        if ($user?->hasRole('أخصائي تخاطب') && (int) $appointment->therapist_id !== (int) $user->id) {
            abort(403);
        }
    }
}
