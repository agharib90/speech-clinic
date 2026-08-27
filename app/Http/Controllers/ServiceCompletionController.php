<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\PatientServiceCompletionService;
use Illuminate\Http\Request;

class ServiceCompletionController extends Controller
{
    public function store(
        Request $request,
        Appointment $appointment,
        PatientServiceCompletionService $completionService
    ) {
        $completionService->complete($appointment, $request->user());

        if ($request->input('workspace_section') === 'appointments') {
            return redirect()->route('patients.workspace', [
                'patient' => $appointment->patient_id,
                'section' => 'appointments',
            ])->with('success', 'تم إتمام الخدمة وتسجيل استحقاق الأخصائي بنجاح.');
        }

        return back()->with('success', 'تم إتمام الخدمة وتسجيل استحقاق الأخصائي بنجاح.');
    }
}
