<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Appointment;
use App\Models\Therapist;
use App\Models\TherapyProgram;
use Illuminate\Http\Request;

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
            'commission_rate' => 'nullable|numeric',
            'is_active' => 'nullable|boolean',
        ]);

        Therapist::create($data);

        return redirect()->route('therapists.index')->with('success', 'تم إضافة الأخصائي بنجاح');
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
