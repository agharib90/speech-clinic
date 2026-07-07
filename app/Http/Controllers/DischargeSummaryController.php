<?php

namespace App\Http\Controllers;

use App\Models\TherapyProgram;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class DischargeSummaryController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request, TherapyProgram $program)
    {
        $this->authorize('view', $program);
        abort_unless($request->user()->can('edit therapy'), 403);

        $data = $request->validate([
            'discharge_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'initial_status' => ['nullable', 'string', 'max:10000'],
            'final_status' => ['nullable', 'string', 'max:10000'],
            'goals_outcome' => ['nullable', 'string', 'max:10000'],
            'recommendations' => ['nullable', 'string', 'max:10000'],
            'follow_up_plan' => ['nullable', 'string', 'max:10000'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'mark_completed' => ['nullable', 'boolean'],
        ]);

        $program->dischargeSummary()->updateOrCreate(
            ['therapy_program_id' => $program->id],
            [
                'prepared_by' => $request->user()->id,
                'discharge_date' => $data['discharge_date'],
                'reason' => $data['reason'] ?? null,
                'initial_status' => $data['initial_status'] ?? null,
                'final_status' => $data['final_status'] ?? null,
                'goals_outcome' => $data['goals_outcome'] ?? null,
                'recommendations' => $data['recommendations'] ?? null,
                'follow_up_plan' => $data['follow_up_plan'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]
        );

        if ($request->boolean('mark_completed')) {
            $program->update([
                'status' => 'مكتمل',
                'end_date' => $data['discharge_date'],
            ]);
        }

        return back()->with('success', 'تم حفظ ملخص الخروج بنجاح');
    }
}
