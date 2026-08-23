<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PatientCheckin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceptionController extends Controller
{
    public const EARLY_ARRIVAL_MINUTES = 30;

    public function index(Request $request)
    {
        $validated = $request->validate([
            'patient' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', 'in:barcode,manual'],
        ]);

        $currentCheckins = PatientCheckin::with([
            'patient',
            'appointment.therapist',
            'appointment.sessionType',
            'appointment.patientServicePlanItem.service',
        ])
            ->whereNull('checkout_at')
            ->where('checkin_at', '>=', $this->todayStart())
            ->where('checkin_at', '<', $this->tomorrowStart())
            ->latest('checkin_at')
            ->get();

        $selectedPatient = null;
        $todayAppointments = collect();

        if (! empty($validated['patient'])) {
            $selectedPatient = Patient::with('guardian')->findOrFail($validated['patient']);
            $todayAppointments = $selectedPatient->appointments()
                ->with([
                    'therapist',
                    'sessionType',
                    'patientServicePlanItem.service',
                    'checkin',
                ])
                ->where('scheduled_at', '>=', $this->todayStart())
                ->where('scheduled_at', '<', $this->tomorrowStart())
                ->orderBy('scheduled_at')
                ->get();
        }

        $search = trim($validated['search'] ?? '');
        $searchResults = collect();

        if ($search !== '') {
            $term = addcslashes($search, '\\%_');
            $searchResults = Patient::with('guardian')
                ->where(function ($query) use ($term) {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('barcode', 'like', "%{$term}%")
                        ->orWhere('qr_code', 'like', "%{$term}%")
                        ->orWhereHas('guardian', function ($guardianQuery) use ($term) {
                            $guardianQuery->where('name', 'like', "%{$term}%")
                                ->orWhere('phone', 'like', "%{$term}%")
                                ->orWhere('phone2', 'like', "%{$term}%");
                        });
                })
                ->orderBy('name')
                ->limit(10)
                ->get();
        }

        return view('reception.index', [
            'currentCheckins' => $currentCheckins,
            'selectedPatient' => $selectedPatient,
            'todayAppointments' => $todayAppointments,
            'searchResults' => $searchResults,
            'identificationMethod' => $validated['source'] ?? 'manual',
            'attendanceEarlyArrivalMinutes' => self::EARLY_ARRIVAL_MINUTES,
        ]);
    }

    public function processScan(Request $request)
    {
        $validated = $request->validate([
            'barcode' => ['required', 'string', 'max:255'],
        ]);

        $code = trim($validated['barcode']);
        $patient = Patient::query()
            ->where('barcode', $code)
            ->orWhere('qr_code', $code)
            ->first();

        if (! $patient) {
            return redirect()->route('reception.index')
                ->with('error', 'لا يوجد مريض مسجل بهذا الباركود أو رمز QR.');
        }

        return redirect()->route('reception.index', [
            'patient' => $patient->id,
            'source' => 'barcode',
        ]);
    }

    public function confirmAttendance(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'integer'],
            'method' => ['required', 'in:barcode,manual'],
        ]);

        $result = DB::transaction(function () use ($appointment, $validated) {
            $lockedAppointment = Appointment::query()
                ->lockForUpdate()
                ->findOrFail($appointment->id);

            if ($lockedAppointment->patient_id !== (int) $validated['patient_id']) {
                return 'wrong_patient';
            }

            if (! $lockedAppointment->scheduled_at->isSameDay(now())) {
                return 'wrong_day';
            }

            if ($lockedAppointment->status !== 'مجدول') {
                return 'ineligible_status';
            }

            if (now()->lt(
                $lockedAppointment->scheduled_at->copy()->subMinutes(self::EARLY_ARRIVAL_MINUTES)
            )) {
                return 'too_early';
            }

            if (PatientCheckin::where('appointment_id', $lockedAppointment->id)->exists()) {
                return 'duplicate';
            }

            PatientCheckin::create([
                'patient_id' => $lockedAppointment->patient_id,
                'appointment_id' => $lockedAppointment->id,
                'checkin_at' => now(),
                'checked_by' => auth()->id(),
                'method' => $validated['method'],
            ]);

            return 'created';
        });

        $redirect = redirect()->route('reception.index', [
            'patient' => $validated['patient_id'],
            'source' => $validated['method'],
        ]);

        return match ($result) {
            'created' => $redirect->with('success', 'تم تأكيد حضور الموعد بنجاح.'),
            'duplicate' => $redirect->with('error', 'تم تأكيد حضور هذا الموعد من قبل.'),
            'wrong_patient' => $redirect->with('error', 'الموعد المحدد لا يخص هذا المريض.'),
            'wrong_day' => $redirect->with('error', 'لا يمكن تأكيد الحضور إلا لمواعيد اليوم.'),
            'too_early' => $redirect->with(
                'error',
                'يتاح تأكيد الحضور من '.$appointment->scheduled_at
                    ->copy()
                    ->subMinutes(self::EARLY_ARRIVAL_MINUTES)
                    ->format('h:i A').'.'
            ),
            default => $redirect->with('error', 'حالة هذا الموعد لا تسمح بتأكيد الحضور.'),
        };
    }

    public function checkout(PatientCheckin $checkin)
    {
        if ($checkin->checkout_at) {
            return redirect()->route('reception.index')
                ->with('error', 'تم تسجيل انصراف هذا المريض من قبل.');
        }

        $checkin->update([
            'checkout_at' => now(),
        ]);

        return redirect()->route('reception.index')
            ->with('success', "تم تسجيل انصراف المريض: {$checkin->patient->name}");
    }

    private function todayStart()
    {
        return now()->startOfDay();
    }

    private function tomorrowStart()
    {
        return now()->addDay()->startOfDay();
    }
}
