<?php

namespace App\Http\Controllers;

use App\Models\ArticulationAssessment;
use App\Models\TherapyProgram;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ArticulationAssessmentController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request, TherapyProgram $program)
    {
        $this->authorize('view', $program);
        abort_unless($request->user()->can('edit therapy'), 403);

        $data = $request->validate([
            'assessed_at' => ['nullable', 'date'],
            'title' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'responses' => ['required', 'array', 'min:1'],
            'responses.*.sound' => ['required', 'string', 'max:20'],
            'responses.*.position' => ['nullable', 'string', 'max:80'],
            'responses.*.prompt_word' => ['nullable', 'string', 'max:120'],
            'responses.*.status' => ['required', Rule::in(ArticulationAssessment::allowedStatuses())],
            'responses.*.substitution' => ['nullable', 'string', 'max:80'],
            'responses.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $responses = collect($data['responses'])
            ->map(fn (array $response) => [
                'sound' => $response['sound'],
                'position' => $response['position'] ?? null,
                'prompt_word' => $response['prompt_word'] ?? null,
                'status' => $response['status'],
                'substitution' => $response['substitution'] ?? null,
                'notes' => $response['notes'] ?? null,
            ])
            ->values();

        $counts = $responses->countBy('status');
        $total = $responses->count();
        $correct = (int) $counts->get(ArticulationAssessment::STATUS_CORRECT, 0);

        ArticulationAssessment::create([
            'therapy_program_id' => $program->id,
            'assessed_by' => $request->user()->id,
            'assessed_at' => $data['assessed_at'] ?? now()->toDateString(),
            'title' => $data['title'] ?? 'اختبار النطق العربي',
            'responses' => $responses->all(),
            'total_items' => $total,
            'correct_count' => $correct,
            'substitution_count' => (int) $counts->get(ArticulationAssessment::STATUS_SUBSTITUTED, 0),
            'omission_count' => (int) $counts->get(ArticulationAssessment::STATUS_OMITTED, 0),
            'distortion_count' => (int) $counts->get(ArticulationAssessment::STATUS_DISTORTED, 0),
            'accuracy_percent' => round(($correct / $total) * 100, 2),
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'تم حفظ اختبار النطق العربي بنجاح');
    }
}
