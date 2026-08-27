<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Attendance;
use App\Models\Setting;
use App\Models\Specialty;
use App\Models\Therapist;
use App\Models\TherapistEarning;
use App\Models\TherapistWorkPeriod;
use App\Models\TherapyProgram;
use App\Models\TherapySession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TherapistController extends Controller
{
    public function index()
    {
        $therapists = Therapist::with('user')->latest()->get();
        $therapistUserIds = $therapists->pluck('user_id')->filter()->values();

        $activeProgramCounts = TherapyProgram::query()
            ->selectRaw('therapist_id, count(*) as aggregate')
            ->whereIn('therapist_id', $therapistUserIds)
            ->where('status', TherapyProgram::STATUS_ACTIVE)
            ->groupBy('therapist_id')
            ->pluck('aggregate', 'therapist_id');

        $todayAppointmentCounts = Appointment::query()
            ->selectRaw('therapist_id, count(*) as aggregate')
            ->whereIn('therapist_id', $therapistUserIds)
            ->whereDate('scheduled_at', today())
            ->groupBy('therapist_id')
            ->pluck('aggregate', 'therapist_id');

        $completedMonthCounts = Appointment::query()
            ->selectRaw('therapist_id, count(*) as aggregate')
            ->whereIn('therapist_id', $therapistUserIds)
            ->where('status', 'مكتمل')
            ->whereMonth('scheduled_at', now()->month)
            ->whereYear('scheduled_at', now()->year)
            ->groupBy('therapist_id')
            ->pluck('aggregate', 'therapist_id');

        $teamStats = [
            'total' => $therapists->count(),
            'active' => $therapists->where('is_active', true)->count(),
            'linked' => $therapists->whereNotNull('user_id')->count(),
            'today_appointments' => $todayAppointmentCounts->sum(),
        ];

        return view('hr.therapists.index', compact(
            'therapists',
            'activeProgramCounts',
            'todayAppointmentCounts',
            'completedMonthCounts',
            'teamStats'
        ));
    }

    public function create()
    {
        return view('hr.therapists.create');
    }

    public function show(Therapist $therapist)
    {
        $therapist->load([
            'user',
            'specialties',
            'services.specialty',
            'serviceRates.service.specialty',
            'serviceRates.creator',
            'workPeriods',
        ]);
        $rateDate = today();
        $eligibleRates = $therapist->serviceRates
            ->filter(fn ($rate) => $rate->effective_from->lte($rateDate)
                && (! $rate->effective_to || $rate->effective_to->gte($rateDate)))
            ->sort(function ($left, $right) {
                $dateComparison = $right->effective_from->getTimestamp()
                    <=> $left->effective_from->getTimestamp();

                return $dateComparison !== 0 ? $dateComparison : $right->id <=> $left->id;
            });
        $currentRates = $therapist->services->mapWithKeys(
            fn ($service) => [$service->id => $eligibleRates->firstWhere('service_id', $service->id)]
        );
        $specialties = Specialty::query()
            ->where('is_active', true)
            ->with(['services' => fn ($query) => $query->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get();
        $schedule = collect(TherapistWorkPeriod::WEEKDAYS)->map(function ($label, $weekday) use ($therapist) {
            return [
                'weekday' => $weekday,
                'label' => $label,
                'periods' => $therapist->workPeriods
                    ->where('weekday', $weekday)
                    ->map(fn (TherapistWorkPeriod $period) => [
                        'starts_at' => substr($period->starts_at, 0, 5),
                        'ends_at' => substr($period->ends_at, 0, 5),
                    ])
                    ->values(),
            ];
        })->values();
        $todayAppointments = collect();
        $upcomingAppointments = collect();
        $upcomingAppointmentCount = 0;
        $canViewAppointments = auth()->user()->can('view appointments');
        $canViewTherapyKpis = auth()->user()->can('view therapy');
        $canViewEarningKpi = auth()->user()->can('manage payroll');

        if ($therapist->user_id && $canViewAppointments) {
            $appointmentQuery = Appointment::query()
                ->with([
                    'patient',
                    'sessionType',
                    'patientServicePlanItem.service',
                    'checkin',
                    'therapySession',
                ])
                ->where('therapist_id', $therapist->user_id);

            $todayAppointments = (clone $appointmentQuery)
                ->whereDate('scheduled_at', today())
                ->orderBy('scheduled_at')
                ->get();
            $upcomingAppointments = (clone $appointmentQuery)
                ->whereBetween('scheduled_at', [
                    today()->addDay()->startOfDay(),
                    today()->addDays(7)->endOfDay(),
                ])
                ->where('status', 'مجدول')
                ->orderBy('scheduled_at')
                ->limit(10)
                ->get();
            $upcomingAppointmentCount = Appointment::query()
                ->where('therapist_id', $therapist->user_id)
                ->whereBetween('scheduled_at', [today()->addDay()->startOfDay(), today()->addDays(7)->endOfDay()])
                ->where('status', 'مجدول')
                ->count();
        }

        $completedSessionsThisMonth = null;
        $activeCases = null;
        if ($therapist->user_id && $canViewTherapyKpis) {
            $completedSessionsThisMonth = TherapySession::query()
                ->whereHas('program', fn ($query) => $query->where('therapist_id', $therapist->user_id))
                ->where('status', 'مكتملة')
                ->whereBetween('session_date', [today()->startOfMonth(), today()->endOfMonth()])
                ->count();
            $activeCases = TherapyProgram::query()
                ->where('therapist_id', $therapist->user_id)
                ->where('status', TherapyProgram::STATUS_ACTIVE)
                ->count();
        }

        $monthlyEarnings = null;
        if ($canViewEarningKpi) {
            $monthlyEarnings = TherapistEarning::query()
                ->where('therapist_id', $therapist->id)
                ->whereHas('session', fn ($query) => $query->whereBetween(
                    'session_date',
                    [today()->startOfMonth(), today()->endOfMonth()]
                ))
                ->sum('amount');
        }

        $workspaceStats = [
            'today_appointments' => $todayAppointments->count(),
            'upcoming_appointments_7_days' => $upcomingAppointmentCount,
            'assigned_services' => $therapist->services->count(),
            'completed_sessions_this_month' => $completedSessionsThisMonth,
            'active_cases' => $activeCases,
            'monthly_earnings' => $monthlyEarnings,
        ];

        return view('hr.therapists.show', compact(
            'therapist',
            'currentRates',
            'specialties',
            'schedule',
            'todayAppointments',
            'upcomingAppointments',
            'workspaceStats',
            'canViewAppointments',
            'canViewTherapyKpis',
            'canViewEarningKpi'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'specialization' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'license_number' => 'nullable|string',
            'hire_date' => 'nullable|date',
            'salary_type' => 'required|in:monthly,daily,commission',
            'monthly_salary' => 'nullable|numeric',
            'daily_salary' => 'nullable|numeric',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $data['monthly_salary'] = $data['monthly_salary'] ?? 0;
        $data['daily_salary'] = $data['daily_salary'] ?? 0;

        if (! array_key_exists('commission_rate', $data) || $data['commission_rate'] === null) {
            $data['commission_rate'] = (float) (Setting::first()?->default_therapist_commission_rate ?? 0);
        }

        Therapist::create($data);

        return redirect()->route('therapists.index')->with('success', 'تم إضافة الأخصائي بنجاح');
    }

    public function edit(Therapist $therapist)
    {
        return view('hr.therapists.edit', compact('therapist'));
    }

    public function update(Request $request, Therapist $therapist)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'license_number' => 'nullable|string|max:255',
            'hire_date' => 'nullable|date',
            'is_active' => 'required|boolean',
        ]);

        $therapist->update($data);

        return redirect()
            ->route('therapists.show', $therapist)
            ->with('success', 'تم تحديث بيانات الأخصائي بنجاح.');
    }

    public function updateSchedule(Request $request, Therapist $therapist)
    {
        $data = $request->validate([
            'periods' => ['nullable', 'array'],
            'periods.*' => ['array'],
            'periods.*.*' => ['array'],
            'periods.*.*.starts_at' => ['required', 'date_format:H:i'],
            'periods.*.*.ends_at' => ['required', 'date_format:H:i'],
        ]);
        $normalized = [];

        foreach ($data['periods'] ?? [] as $weekday => $periods) {
            if (! ctype_digit((string) $weekday)
                || ! array_key_exists((int) $weekday, TherapistWorkPeriod::WEEKDAYS)) {
                throw ValidationException::withMessages([
                    'periods' => 'يوم العمل المحدد غير صالح.',
                ]);
            }

            $dayPeriods = collect($periods)
                ->map(function (array $period, int $index) use ($weekday) {
                    if ($period['ends_at'] <= $period['starts_at']) {
                        throw ValidationException::withMessages([
                            "periods.{$weekday}.{$index}.ends_at" => 'وقت نهاية الفترة يجب أن يكون بعد وقت بدايتها.',
                        ]);
                    }

                    return $period + ['original_index' => $index];
                })
                ->sortBy('starts_at')
                ->values();

            $previousEnd = null;
            foreach ($dayPeriods as $period) {
                if ($previousEnd !== null && $period['starts_at'] < $previousEnd) {
                    throw ValidationException::withMessages([
                        "periods.{$weekday}.{$period['original_index']}.starts_at" => 'فترات العمل في اليوم نفسه لا يجوز أن تتداخل أو تتكرر.',
                    ]);
                }

                $previousEnd = $period['ends_at'];
                $normalized[] = [
                    'weekday' => (int) $weekday,
                    'starts_at' => $period['starts_at'],
                    'ends_at' => $period['ends_at'],
                ];
            }
        }

        DB::transaction(function () use ($therapist, $normalized) {
            $therapist->workPeriods()->delete();
            $therapist->workPeriods()->createMany($normalized);
        });

        return back()
            ->with('success', 'تم حفظ جدول عمل الأخصائي بنجاح.')
            ->with('workspace_tab', 'schedule');
    }

    // تسجيل حضور يومي للأخصائيين
    public function markAttendance(Request $request)
    {
        $request->validate([
            'therapist_id' => 'required|exists:therapists,id',
            'status' => 'required|in:حاضر,غائب,إجازة',
        ]);

        // التأكد إنه مسجلش اليوم مرتين
        $exists = Attendance::where('therapist_id', $request->therapist_id)
            ->whereDate('attendance_date', today())
            ->exists();

        if ($exists) {
            return back()->with('error', 'تم تسجيل الحضور لهذا الأخصائي اليوم مسبقاً!');
        }

        Attendance::create([
            'therapist_id' => $request->therapist_id,
            'attendance_date' => today(),
            'status' => $request->status,
        ]);

        return back()->with('success', 'تم تسجيل الحضور بنجاح');
    }
}
