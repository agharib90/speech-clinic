<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Attendance;
use App\Models\Setting;
use App\Models\Therapist;
use App\Models\TherapistServiceRate;
use App\Models\TherapistWorkPeriod;
use App\Models\TherapyProgram;
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
            'workPeriods',
        ]);
        $currentRates = $therapist->services->mapWithKeys(function ($service) use ($therapist) {
            $rate = TherapistServiceRate::resolveFor($therapist, $service, today());

            return [$service->id => $rate?->amount];
        });
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

        return view('hr.therapists.show', compact('therapist', 'currentRates', 'schedule'));
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

        if (! array_key_exists('commission_rate', $data) || $data['commission_rate'] === null) {
            $data['commission_rate'] = (float) (Setting::first()?->default_therapist_commission_rate ?? 0);
        }

        Therapist::create($data);

        return redirect()->route('therapists.index')->with('success', 'تم إضافة الأخصائي بنجاح');
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

        return back()->with('success', 'تم حفظ جدول عمل الأخصائي بنجاح.');
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
