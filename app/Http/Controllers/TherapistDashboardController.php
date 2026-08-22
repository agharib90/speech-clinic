<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\ClinicalProgressPoint;
use App\Models\HomeTask;
use App\Models\SessionPackage;
use App\Models\TherapistEarning;
use App\Models\TherapyProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TherapistDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // ── إحصائيات اليوم ────────────────────────────────────────────────
        $todayAppointments = Appointment::where('therapist_id', $user->id)
            ->whereDate('scheduled_at', today())
            ->count();

        $completedToday = Appointment::where('therapist_id', $user->id)
            ->whereDate('scheduled_at', today())
            ->where('status', 'مكتمل')
            ->count();

        $absentToday = Appointment::where('therapist_id', $user->id)
            ->whereDate('scheduled_at', today())
            ->where('status', 'غياب')
            ->count();

        // ── المرضى النشطون (لهم برامج جارية مع هذا الأخصائي) ────────────
        $activePatients = TherapyProgram::where('therapist_id', $user->id)
            ->where('status', 'جاري')
            ->with('patient')
            ->get()
            ->pluck('patient')
            ->unique('id');

        $activePatientsCount = $activePatients->count();

        // ── مواعيد اليوم (مرتبة بالوقت) ──────────────────────────────────
        $todaySchedule = Appointment::where('therapist_id', $user->id)
            ->whereDate('scheduled_at', today())
            ->with('patient', 'sessionType', 'patientServicePlanItem.service')
            ->orderBy('scheduled_at')
            ->get();

        // ── المواعيد القادمة (غير اليوم) ──────────────────────────────────
        $upcomingAppointments = Appointment::where('therapist_id', $user->id)
            ->whereDate('scheduled_at', '>', today())
            ->where('status', 'مجدول')
            ->with('patient', 'sessionType', 'patientServicePlanItem.service')
            ->orderBy('scheduled_at')
            ->take(5)
            ->get();

        // ── البرامج الجارية مع تفاصيل التقدم ────────────────────────────
        $activePrograms = TherapyProgram::where('therapist_id', $user->id)
            ->where('status', 'جاري')
            ->with([
                'patient',
                'sessions' => fn ($q) => $q->latest()->take(1),
                'milestones' => fn ($q) => $q->latest()->take(1),
            ])
            ->latest()
            ->get()
            ->map(function ($program) {
                // حساب نسبة التقدم بناءً على الجلسات المكتملة مقارنة بالمخطط
                $totalSessions = $program->sessions_per_week * 4; // شهر كمرجع
                $completedSessions = $program->sessions()->where('status', 'مكتملة')->count();
                $program->progress = $totalSessions > 0
                    ? min(100, round(($completedSessions / $totalSessions) * 100))
                    : 0;
                $program->total_sessions_done = $completedSessions;

                // آخر جلسة
                $program->last_session = $program->sessions->first();

                return $program;
            });

        // ── إحصائيات الشهر الحالي ─────────────────────────────────────────
        $monthlyStats = [
            'completed' => Appointment::where('therapist_id', $user->id)
                ->whereMonth('scheduled_at', now()->month)
                ->whereYear('scheduled_at', now()->year)
                ->where('status', 'مكتمل')
                ->count(),

            'absent' => Appointment::where('therapist_id', $user->id)
                ->whereMonth('scheduled_at', now()->month)
                ->whereYear('scheduled_at', now()->year)
                ->where('status', 'غياب')
                ->count(),

            'cancelled' => Appointment::where('therapist_id', $user->id)
                ->whereMonth('scheduled_at', now()->month)
                ->whereYear('scheduled_at', now()->year)
                ->where('status', 'ملغى')
                ->count(),

            'earnings' => TherapistEarning::whereHas('therapist', fn ($q) => $q->where('user_id', $user->id))
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
        ];

        // نسبة الحضور للشهر
        $totalMonthly = $monthlyStats['completed'] + $monthlyStats['absent'] + $monthlyStats['cancelled'];
        $monthlyStats['attendance_rate'] = $totalMonthly > 0
            ? round(($monthlyStats['completed'] / $totalMonthly) * 100)
            : 0;

        // ── آخر الواجبات المنزلية التي تحتاج متابعة ──────────────────────
        $pendingTasks = HomeTask::whereHas('session.program', function ($q) use ($user) {
            $q->where('therapist_id', $user->id);
        })
            ->where('is_completed', false)
            ->where('due_date', '<=', today())
            ->with('session.program.patient')
            ->latest('due_date')
            ->take(5)
            ->get();

        $recentProgressPoints = ClinicalProgressPoint::whereHas('program', function ($q) use ($user) {
            $q->where('therapist_id', $user->id);
        })
            ->with('program.patient')
            ->latest('recorded_at')
            ->take(5)
            ->get();

        return view('therapist.dashboard', compact(
            'todayAppointments',
            'completedToday',
            'absentToday',
            'activePatientsCount',
            'activePatients',
            'todaySchedule',
            'upcomingAppointments',
            'activePrograms',
            'monthlyStats',
            'pendingTasks',
            'recentProgressPoints'
        ));
    }

    public function cases(Request $request)
    {
        $user = Auth::user();
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');
        $statusOptions = TherapyProgram::STATUS_LABELS;

        $programs = TherapyProgram::query()
            ->where('therapist_id', $user->id)
            ->with([
                'patient.guardian',
                'sessions' => fn ($query) => $query->latest('session_date')->latest('id'),
                'progressPoints',
            ])
            ->when(in_array($status, array_keys($statusOptions), true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($request->boolean('today'), function ($query) use ($user) {
                $query->whereHas('patient.appointments', function ($appointments) use ($user) {
                    $appointments
                        ->where('therapist_id', $user->id)
                        ->whereDate('scheduled_at', today());
                });
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('disorder_type', 'like', "%{$search}%")
                        ->orWhereHas('patient', function ($patientQuery) use ($search) {
                            $patientQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('barcode', 'like', "%{$search}%");
                        })
                        ->orWhereHas('patient.guardian', function ($guardianQuery) use ($search) {
                            $guardianQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('updated_at')
            ->paginate(12)
            ->withQueryString();

        $patientIds = $programs->getCollection()
            ->pluck('patient_id')
            ->filter()
            ->unique()
            ->values();

        $nextAppointments = Appointment::query()
            ->whereIn('patient_id', $patientIds)
            ->where('therapist_id', $user->id)
            ->where('status', 'مجدول')
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy('patient_id')
            ->map(fn ($appointments) => $appointments->first());

        $activePackages = SessionPackage::query()
            ->whereIn('patient_id', $patientIds)
            ->where('status', 'نشط')
            ->latest('created_at')
            ->get()
            ->groupBy('patient_id')
            ->map(fn ($packages) => $packages->first());

        $caseStats = [
            'total' => TherapyProgram::where('therapist_id', $user->id)
                ->distinct('patient_id')
                ->count('patient_id'),
            'active' => TherapyProgram::where('therapist_id', $user->id)
                ->where('status', TherapyProgram::STATUS_ACTIVE)
                ->count(),
            'today' => Appointment::where('therapist_id', $user->id)
                ->whereDate('scheduled_at', today())
                ->distinct('patient_id')
                ->count('patient_id'),
            'upcoming' => Appointment::where('therapist_id', $user->id)
                ->where('status', 'مجدول')
                ->where('scheduled_at', '>=', now())
                ->count(),
        ];

        return view('therapist.cases', compact(
            'programs',
            'statusOptions',
            'status',
            'search',
            'nextAppointments',
            'activePackages',
            'caseStats'
        ));
    }
}
