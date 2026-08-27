<?php

namespace App\Http\Controllers;

use App\Models\PatientClinicalEvaluationAssignment;
use Illuminate\Http\Request;

class ClinicalEvaluationQueueController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->string('status')->toString();
        $allowedStatuses = [
            PatientClinicalEvaluationAssignment::STATUS_PENDING,
            PatientClinicalEvaluationAssignment::STATUS_IN_PROGRESS,
            PatientClinicalEvaluationAssignment::STATUS_COMPLETED,
        ];

        if (! in_array($status, $allowedStatuses, true)) {
            $status = '';
        }

        $assignments = PatientClinicalEvaluationAssignment::query()
            ->with(['patient.guardian', 'clinicalEvaluation'])
            ->where('assigned_to', $request->user()->id)
            ->whereIn('status', $allowedStatuses)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest('assigned_at')
            ->paginate(15)
            ->withQueryString();

        return view('clinical.evaluation-queue', compact('assignments', 'status'));
    }
}
