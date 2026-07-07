<?php

namespace App\Http\Controllers;

use App\Models\StutteringAssessment;
use App\Models\TherapyProgram;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class StutteringAssessmentController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request, TherapyProgram $program)
    {
        $this->authorize('view', $program);
        abort_unless($request->user()->can('edit therapy'), 403);

        $data = $request->validate([
            'assessed_at' => ['nullable', 'date'],
            'sample_context' => ['nullable', 'string', 'max:255'],
            'syllables_count' => ['required', 'integer', 'min:0', 'max:100000'],
            'stuttered_syllables_count' => ['required', 'integer', 'min:0', 'lte:syllables_count'],
            'duration_score' => ['required', 'integer', 'min:0', 'max:10'],
            'physical_concomitants_score' => ['required', 'integer', 'min:0', 'max:10'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ]);

        $percentage = $data['syllables_count'] > 0
            ? round(($data['stuttered_syllables_count'] / $data['syllables_count']) * 100, 2)
            : 0;
        $frequencyScore = StutteringAssessment::frequencyScore($percentage);
        $totalScore = $frequencyScore + (int) $data['duration_score'] + (int) $data['physical_concomitants_score'];

        StutteringAssessment::create([
            'therapy_program_id' => $program->id,
            'assessed_by' => $request->user()->id,
            'assessed_at' => $data['assessed_at'] ?? now()->toDateString(),
            'sample_context' => $data['sample_context'] ?? null,
            'syllables_count' => $data['syllables_count'],
            'stuttered_syllables_count' => $data['stuttered_syllables_count'],
            'stuttering_percentage' => $percentage,
            'frequency_score' => $frequencyScore,
            'duration_score' => $data['duration_score'],
            'physical_concomitants_score' => $data['physical_concomitants_score'],
            'total_score' => $totalScore,
            'severity' => StutteringAssessment::severityForTotal($totalScore),
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'تم حفظ مقياس شدة التأتأة بنجاح');
    }
}
