<?php

namespace App\Http\Controllers;

use App\Models\TherapyProgram;
use App\Models\SessionMilestone;
use Illuminate\Http\Request;

class MilestoneController extends Controller
{
    public function store(Request $request, TherapyProgram $program)
    {
        $request->validate([
            'skill_area' => 'required|string',
            'baseline_score' => 'required|numeric|min:0|max:100',
            'current_score' => 'required|numeric|min:0|max:100',
        ]);

        SessionMilestone::create([
            'therapy_program_id' => $program->id,
            'recorded_at' => now(),
            'skill_area' => $request->skill_area,
            'baseline_score' => $request->baseline_score,
            'current_score' => $request->current_score,
        ]);

        return back()->with('success', 'تم تسجيل التطور بنجاح');
    }
}
