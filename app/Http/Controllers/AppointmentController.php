<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\PackageUsage;
use App\Models\Patient;
use App\Models\PatientServicePlan;
use App\Models\PatientServicePlanItem;
use App\Models\SessionPackage;
use App\Models\SessionType;
use App\Models\Therapist;
use App\Models\TherapistEarning;
use App\Models\TherapyProgram;
use App\Models\TherapySession;
use App\Models\User;
use App\Services\AppointmentAvailabilityService;
use App\Services\AppointmentServicePlanBookingService;
use App\Services\PatientServiceCompletionService;
use App\Services\PatientServicePlanAllocator;
use App\Support\PatientWorkspaceContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function index(Request $request, PatientServiceCompletionService $completionService)
    {
        $user = $request->user();
        $dateFilter = $request->input('date', today()->format('Y-m-d'));
        $therapistFilter = $request->input('therapist_id');
        $requestedPatientId = $request->integer('patient_id');

        if ($user->hasRole('أخصائي تخاطب')) {
            $therapistFilter = $user->id;
        }

        $appointments = Appointment::with(
            'patient',
            'therapist',
            'sessionType',
            'patientServicePlanItem.service.specialty',
            'patientServicePlanItem.plan',
            'checkin',
            'therapySession'
        )
            ->whereDate('scheduled_at', $dateFilter)
            ->when($therapistFilter, function ($query) use ($therapistFilter) {
                $query->where('therapist_id', $therapistFilter);
            })
            ->when($requestedPatientId, fn ($query) => $query->where('patient_id', $requestedPatientId))
            ->orderBy('scheduled_at')
            ->get()
            ->each(function (Appointment $appointment) use ($completionService, $user) {
                $appointment->setAttribute(
                    'service_completion_state',
                    $completionService->state($appointment, $user)
                );
            });

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
                    })->orWhereHas('servicePlans.items.service.therapists', function ($therapistQuery) use ($user) {
                        $therapistQuery->where('therapists.user_id', $user->id);
                    });
                });
            })
            ->get();
        $selectedPatientId = $patients->contains('id', $requestedPatientId) ? $requestedPatientId : null;
        $sessionTypes = SessionType::all();
        $bookingService = app(AppointmentServicePlanBookingService::class);
        $servicePlanItems = PatientServicePlanItem::query()
            ->with([
                'plan.patient',
                'service.specialty',
                'service.therapists' => fn ($query) => $query
                    ->where('therapists.is_active', true)
                    ->whereNotNull('therapists.user_id'),
            ])
            ->whereHas('plan', fn ($query) => $query
                ->where('status', PatientServicePlan::STATUS_ACTIVE)
                ->whereHas('patient', fn ($patientQuery) => $patientQuery->where('is_active', true)))
            ->when($selectedPatientId, fn ($query) => $query
                ->whereHas('plan', fn ($planQuery) => $planQuery->where('patient_id', $selectedPatientId)))
            ->whereColumn('consumed_quantity', '<', 'planned_quantity')
            ->get()
            ->map(function (PatientServicePlanItem $item) use ($bookingService, $user) {
                $eligibility = $bookingService->eligibility($item);
                $therapists = $item->service->therapists
                    ->when($user->hasRole('أخصائي تخاطب'), fn ($items) => $items->where('user_id', $user->id))
                    ->map(fn (Therapist $therapist) => [
                        'user_id' => $therapist->user_id,
                        'name' => $therapist->name,
                    ])
                    ->values();
                $financiallyEligible = $eligibility['can_book'];
                $hasEligibleTherapist = $therapists->isNotEmpty();
                $bookingState = match (true) {
                    ! $financiallyEligible => 'الرصيد غير كافٍ لتأكيد الموعد',
                    ! $hasEligibleTherapist => 'لا يوجد أخصائي مسند لهذه الخدمة',
                    $eligibility['funding_type'] === 'full' => 'مدفوعة بالكامل',
                    default => 'مقدم الحجز متاح',
                };

                return [
                    'id' => $item->id,
                    'patient_id' => $item->plan->patient_id,
                    'patient_name' => $item->plan->patient->name,
                    'service_name' => $item->service->name,
                    'specialty_name' => $item->service->specialty->name,
                    'duration_minutes' => $item->service->default_duration_minutes,
                    'final_unit_price' => $item->final_unit_price,
                    'required_deposit' => PatientServicePlanAllocator::centsToDecimal(
                        $eligibility['required_deposit_cents']
                    ),
                    'financially_eligible' => $financiallyEligible,
                    'has_eligible_therapist' => $hasEligibleTherapist,
                    'can_book' => $financiallyEligible && $hasEligibleTherapist,
                    'booking_state' => $bookingState,
                    'therapists' => $therapists,
                ];
            })
            ->values();

        return view('appointments.index', compact(
            'appointments',
            'therapists',
            'patients',
            'sessionTypes',
            'servicePlanItems',
            'selectedPatientId',
            'dateFilter',
            'therapistFilter'
        ));
    }

    public function store(
        StoreAppointmentRequest $request,
        AppointmentServicePlanBookingService $bookingService,
        AppointmentAvailabilityService $availability
    ) {
        $validated = $request->validated();
        $fromWorkspace = PatientWorkspaceContext::validate($request, (int) $validated['patient_id']);

        if (! empty($validated['patient_service_plan_item_id'])) {
            $appointment = $bookingService->book($validated);

            if ($fromWorkspace) {
                return redirect()->route('patients.workspace', [
                    'patient' => $appointment->patient_id,
                    'section' => 'appointments',
                ])
                    ->with('success', 'تم حجز الموعد وتأكيده ماليًا بنجاح');
            }

            return redirect()->route('appointments.index', [
                'date' => $appointment->scheduled_at->format('Y-m-d'),
            ])->with('success', 'تم حجز الموعد وتأكيده ماليًا بنجاح');
        }

        abort_unless(
            $request->user()->can('create appointments')
                && $request->user()->can('create legacy appointments'),
            403
        );

        $scheduledAt = Carbon::parse($validated['scheduled_at']);
        $sessionType = SessionType::findOrFail($validated['session_type_id']);
        $endAt = $scheduledAt->copy()->addMinutes($sessionType->duration_minutes);
        $therapist = Therapist::query()
            ->where('user_id', $validated['therapist_id'])
            ->where('is_active', true)
            ->first();

        if (! $therapist || ! $availability->fitsWithinWorkPeriod(
            $therapist,
            $scheduledAt,
            (int) $sessionType->duration_minutes
        )) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'الموعد المختار خارج فترات عمل الأخصائي أو لا يتسع لمدة الجلسة.',
            ]);
        }

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

            if ($appointment->patient_service_plan_item_id) {
                throw ValidationException::withMessages([
                    'status' => 'إتمام خدمات خطط المرضى سيتم من خلال مسار تنفيذ الخدمة المخصص، ولا يمكن إتمام هذا الموعد بالطريقة القديمة.',
                ]);
            }

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
