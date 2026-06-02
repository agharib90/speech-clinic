<?php

namespace App\Http\Controllers;

use App\Models\TherapyProgram;
use Illuminate\Http\Request;

class TherapyProgramController extends Controller
{
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
}
