<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientClinicalEvaluation;
use App\Services\PatientClinicalEvaluationService;
use App\Support\PatientAccess;
use Illuminate\Http\Request;

class PatientClinicalEvaluationController extends Controller
{
    private const SUMMARY_RULES = ['nullable', 'string', 'max:20000'];

    public function saveDraft(
        Request $request,
        Patient $patient,
        PatientClinicalEvaluationService $evaluationService
    ) {
        PatientAccess::authorize($patient, $request->user());
        $data = $request->validate([
            'clinical_summary' => self::SUMMARY_RULES,
        ]);

        $evaluationService->saveDraft($patient, $request->user(), $data['clinical_summary'] ?? null);

        return redirect()->route('patients.workspace', ['patient' => $patient, 'section' => 'clinical'])
            ->with('success', 'تم حفظ مسودة التقييم السريري.');
    }

    public function complete(
        Request $request,
        Patient $patient,
        PatientClinicalEvaluation $evaluation,
        PatientClinicalEvaluationService $evaluationService
    ) {
        PatientAccess::authorize($patient, $request->user());
        $data = $request->validate([
            'clinical_summary' => self::SUMMARY_RULES,
        ]);
        $evaluationService->complete(
            $patient,
            $evaluation,
            $request->user(),
            $data['clinical_summary'] ?? null
        );

        return redirect()->route('patients.workspace', ['patient' => $patient, 'section' => 'clinical'])
            ->with('success', 'تم إكمال التقييم السريري. يمكنك الآن إعداد الخطة العلاجية.');
    }
}
