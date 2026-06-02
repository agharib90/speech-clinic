<?php

namespace App\Http\Controllers;

use App\Models\PatientCheckin;
use App\Models\Patient;
use App\Models\Appointment;
use Illuminate\Http\Request;

class ReceptionController extends Controller
{
    // عرض شاشة الاستقبال الرئيسية
    public function index()
    {
        // جلب الحالات الحاضرة حالياً (اللي سجلت حضور ولسا مسجلتش انصراف)
        $currentCheckins = PatientCheckin::with('patient', 'appointment.therapist')
            ->whereNull('checkout_at')
            ->whereDate('checkin_at', today())
            ->latest('checkin_at')
            ->get();

        return view('reception.index', compact('currentCheckins'));
    }

    // معالجة مسح الباركود (تسجيل حضور أو انصراف)
    public function processScan(Request $request)
    {
        $request->validate([
            'barcode' => 'required|string'
        ]);

        $barcode = $request->input('barcode');
        $patient = Patient::where('barcode', $barcode)->first();

        // ١. التأكد إن المريض موجود
        if (!$patient) {
            return redirect()->route('reception.index')
                ->with('error', 'لا يوجد مريض مسجل بهذا الباركود!');
        }

        // ٢. البحث هل المريض ده داخل العيادة دلوقتي (عشان نسجله انصراف)؟
        $activeCheckin = PatientCheckin::where('patient_id', $patient->id)
            ->whereNull('checkout_at')
            ->whereDate('checkin_at', today())
            ->first();

        if ($activeCheckin) {
            // تسجيل انصراف
            $activeCheckin->update([
                'checkout_at' => now(),
            ]);

            return redirect()->route('reception.index')
                ->with('success', "تم تسجيل انصراف المريض: {$patient->name}");
        }

        // ٣. لو مش موجود داخل العيادة، يبقى نسجل حضور (Check-in)
        // هنحاول نربطه بموعد مجدول النهارده لو لقينا
        $todayAppointment = Appointment::where('patient_id', $patient->id)
            ->whereDate('scheduled_at', today())
            ->where('status', 'مجدول')
            ->first();

        PatientCheckin::create([
            'patient_id' => $patient->id,
            'appointment_id' => $todayAppointment?->id, // لو فيه موعد هيتربط، لو مفيش هيبقى null (حضور حر)
            'checkin_at' => now(),
            'checked_by' => auth()->id(),
            'method' => 'barcode',
        ]);

        return redirect()->route('reception.index')
            ->with('success', "تم تسجيل حضور المريض: {$patient->name}");
    }

    // تسجيل انصراف يدوي من زرار في الشاشة
    public function checkout(PatientCheckin $checkin)
    {
        $checkin->update([
            'checkout_at' => now(),
        ]);

        return redirect()->route('reception.index')
            ->with('success', "تم تسجيل انصراف المريض: {$checkin->patient->name}");
    }
}
