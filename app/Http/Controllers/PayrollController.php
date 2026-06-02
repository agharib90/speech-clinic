<?php

namespace App\Http\Controllers;

use App\Models\Therapist;
use App\Models\TherapistEarning;
use App\Models\PayrollRecord;
use App\Models\Attendance;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    // عرض شاشة اختيار الأخصائي والشهر لحساب الراتب
    public function index()
    {
        $therapists = Therapist::where('is_active', true)->get();
        return view('hr.payroll.index', compact('therapists'));
    }

    // حساب وتوليد تقرير الراتب
    public function calculate(Request $request)
    {
        $request->validate([
            'therapist_id' => 'required|exists:therapists,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer',
        ]);

        $therapist = Therapist::findOrFail($request->therapist_id);
        $month = $request->month;
        $year = $request->year;

        // ١. جلب أيام الحضور في هذا الشهر
        $attendanceDays = Attendance::where('therapist_id', $therapist->id)
            ->whereMonth('attendance_date', $month)
            ->whereYear('attendance_date', $year)
            ->where('status', 'حاضر')
            ->count();

        // ٢. جلب العمولات/رسوم الجلسات المحققة في هذا الشهر (اللي اتعملت لما الموعد يكتمل)
        $earnings = TherapistEarning::where('therapist_id', $therapist->id)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->get();

        $totalEarnings = $earnings->sum('amount');

        // ٣. جلب البدلات والخصومات اليدوية
        $records = PayrollRecord::where('therapist_id', $therapist->id)
            ->whereMonth('month', $month)
            ->whereYear('year', $year)
            ->get();

        $additions = $records->where('type', 'إضافة')->sum('amount');
        $deductions = $records->where('type', 'خصم')->sum('amount');

        // ٤. حساب الراتب الأساسي حسب نوع العقد
        $baseSalary = 0;
        if ($therapist->salary_type === 'monthly') {
            $baseSalary = $therapist->monthly_salary;
        } elseif ($therapist->salary_type === 'daily') {
            $baseSalary = $therapist->daily_salary * $attendanceDays;
        } elseif ($therapist->salary_type === 'commission') {
            // لو عمولة فقط، الأساسي بصفر، والعمولة هي الـ totalEarnings
            $baseSalary = 0;
        }

        // ٥. صافي الراتب
        $netSalary = $baseSalary + $totalEarnings + $additions - $deductions;

        return view('hr.payroll.result', compact(
            'therapist', 'month', 'year', 'attendanceDays', 'earnings',
            'totalEarnings', 'additions', 'deductions', 'baseSalary', 'netSalary'
        ));
    }

    // إضافة بدل أو خصم يدوي
    public function addRecord(Request $request)
    {
        $request->validate([
            'therapist_id' => 'required|exists:therapists,id',
            'type' => 'required|in:إضافة,خصم',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string',
            'month' => 'required|integer',
            'year' => 'required|integer',
        ]);

        PayrollRecord::create($request->all());

        return back()->with('success', 'تم إضافة الـ ' . $request->type . ' بنجاح');
    }
}
