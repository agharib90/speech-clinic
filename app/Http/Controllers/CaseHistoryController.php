<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;

class CaseHistoryController extends Controller
{
    public function store(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'taken_at' => ['nullable', 'date'],
            'main_concerns' => ['nullable', 'string', 'max:10000'],
            'prenatal_history' => ['nullable', 'string', 'max:10000'],
            'birth_history' => ['nullable', 'string', 'max:10000'],
            'developmental_milestones' => ['nullable', 'string', 'max:10000'],
            'medical_history' => ['nullable', 'string', 'max:10000'],
            'hearing_vision_notes' => ['nullable', 'string', 'max:10000'],
            'family_history' => ['nullable', 'string', 'max:10000'],
            'language_environment' => ['nullable', 'string', 'max:10000'],
            'previous_interventions' => ['nullable', 'string', 'max:10000'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ]);

        $patient->caseHistory()->updateOrCreate(
            ['patient_id' => $patient->id],
            $data + [
                'taken_by' => $request->user()->id,
                'taken_at' => $data['taken_at'] ?? now()->toDateString(),
            ]
        );

        return back()->with('success', 'تم حفظ تاريخ الحالة بنجاح');
    }
}
