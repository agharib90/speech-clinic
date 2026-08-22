<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PatientCheckin;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // لو الـ user أخصائي تخاطب، وجّهه للوحة الخاصة بيه
        if ($user->hasRole('أخصائي تخاطب')) {
            return redirect()->route('therapist.dashboard');
        }

        abort_unless($user->can('view reports'), 403);

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
        $upcomingAppointments = Appointment::with('patient', 'therapist', 'sessionType', 'patientServicePlanItem.service')
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
