<?php

namespace App\Http\Controllers;

use App\Models\PatientServicePlan;

class ClinicalHandoffQueueController extends Controller
{
    public function index()
    {
        $plans = PatientServicePlan::query()
            ->with(['patient.guardian', 'clinicalApprover', 'items.service'])
            ->withCount('items')
            ->where('status', PatientServicePlan::STATUS_DRAFT)
            ->whereNotNull('clinical_evaluation_id')
            ->whereNotNull('clinical_approved_at')
            ->latest('clinical_approved_at')
            ->paginate(15);

        return view('clinical.handoff-queue', compact('plans'));
    }
}
