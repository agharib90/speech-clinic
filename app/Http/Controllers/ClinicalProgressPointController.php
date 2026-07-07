<?php

namespace App\Http\Controllers;

use App\Models\ClinicalProgressPoint;
use App\Models\TherapyProgram;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class ClinicalProgressPointController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request, TherapyProgram $program)
    {
        $this->authorize('view', $program);
        abort_unless($request->user()->can('edit therapy'), 403);

        $data = $request->validate([
            'recorded_at' => ['nullable', 'date'],
            'domain' => ['required', 'string', 'max:120'],
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ]);

        ClinicalProgressPoint::create([
            'therapy_program_id' => $program->id,
            'recorded_by' => $request->user()->id,
            'recorded_at' => $data['recorded_at'] ?? now()->toDateString(),
            'domain' => $data['domain'],
            'score' => $data['score'],
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'تم حفظ نقطة التقدم بنجاح');
    }
}
