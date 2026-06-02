<?php

namespace App\Http\Controllers;

use App\Models\TherapyProgram;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;




class TherapyProgramController extends Controller
{
    use AuthorizesRequests;
    public function index()
    {
        // جلب البرامج النشطة فقط
        $programs = TherapyProgram::with('patient', 'therapist')->where('status', 'جاري')->latest()->get();
        return view('clinical.programs.index', compact('programs'));
    }

    public function show(TherapyProgram $program)
    {
        // تحميل كل البيانات العيادية المرتبطة بالبرنامج
        $program->load('patient', 'therapist', 'sessions.homeTasks', 'milestones', 'attachments');
        return view('clinical.programs.show', compact('program'));
    }

    public function progressReport(TherapyProgram $program)
{
    // ١. التحقق من الصلاحية (أن المستخدم يملك حق رؤية هذا البرنامج)
    // سنقوم بإنشاء Policy لاحقاً باسم TherapyProgramPolicy
    $this->authorize('view', $program);

    // ٢. تحميل العلاقات المطلوبة للتقرير بشكل كامل
     $program->load([
    'milestones',
    'sessions',
    'patient.guardian',
    'therapist' // <--- تم التعديل هنا (أزلنا .user)
]);

    // ٣. إعدادات DomPDF لدعم RTL والصور
    $pdf = Pdf::loadView('clinical.programs.progress-pdf', compact('program'))
        ->setOption('isHtml5ParserEnabled', true)
        ->setOption('isRemoteEnabled', true); // لتمكين تحميل الشعار (Logo) إن وجد

    // ٤. تجهيز اسم الملف (تجنب مشاكل الأسماء العربية قدر الإمكان بإضافة التاريخ)
    $fileName = "تقرير_تطور_{$program->patient->name}_" . now()->format('Y-m-d') . ".pdf";

    return $pdf->download($fileName);
}

}
