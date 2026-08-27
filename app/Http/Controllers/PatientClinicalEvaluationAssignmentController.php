<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\User;
use App\Services\PatientClinicalEvaluationAssignmentService;
use App\Support\PatientAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PatientClinicalEvaluationAssignmentController extends Controller
{
    public function store(
        Request $request,
        Patient $patient,
        PatientClinicalEvaluationAssignmentService $assignmentService
    ): RedirectResponse {
        PatientAccess::authorize($patient, $request->user());

        $data = $request->validate([
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
        ]);

        $assignmentService->assign(
            $patient,
            User::query()->findOrFail($data['assigned_to']),
            $request->user()
        );

        return redirect()->route('patients.workspace', ['patient' => $patient, 'section' => 'clinical'])
            ->with('success', 'تم إسناد التقييم السريري بنجاح.');
    }
}
