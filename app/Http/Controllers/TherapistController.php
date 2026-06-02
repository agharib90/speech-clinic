<?php

namespace App\Http\Controllers;

use App\Models\Therapist;
use App\Models\Attendance;
use Illuminate\Http\Request;

class TherapistController extends Controller
{
    public function index()
    {
        $therapists = Therapist::latest()->get();
        return view('hr.therapists.index', compact('therapists'));
    }

    public function create()
    {
        return view('hr.therapists.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'specialization' => 'nullable|string',
            'phone' => 'nullable|string',
            'salary_type' => 'required|in:monthly,daily,commission',
            'monthly_salary' => 'nullable|numeric',
            'daily_salary' => 'nullable|numeric',
            'commission_rate' => 'nullable|numeric',
        ]);

        Therapist::create($request->all());

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
