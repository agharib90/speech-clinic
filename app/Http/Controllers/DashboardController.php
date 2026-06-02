<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\PatientCheckin;
use App\Models\InvoicePayment;
use App\Models\Invoice;

class DashboardController extends Controller
{
    public function index()
    {
        // لو الـ user أخصائي تخاطب، وجّهه للوحة الخاصة بيه
        if (auth()->user()->hasRole('أخصائي تخاطب')) {
            return redirect()->route('therapist.dashboard');
        }


        // ١. إحصائيات اليوم
        $todayAppointments = Appointment::whereDate('scheduled_at', today())->count();
        $currentCheckins = PatientCheckin::whereNull('checkout_at')->whereDate('checkin_at', today())->count();

        // ٢. إحصائيات الشهر المالي
        $monthlyRevenue = InvoicePayment::whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');

        $unpaidInvoices = Invoice::where('status', '!=', 'مدفوعة')
            ->whereMonth('issue_date', now()->month)
            ->count();

        // ٣. مواعيد اليوم القادمة (لعرضها في الجدول)
        $upcomingAppointments = Appointment::with('patient', 'therapist', 'sessionType')
            ->whereDate('scheduled_at', today())
            ->where('status', 'مجدول')
            ->orderBy('scheduled_at')
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'todayAppointments', 'currentCheckins', 'monthlyRevenue',
            'unpaidInvoices', 'upcomingAppointments'
        ));
    }
}
