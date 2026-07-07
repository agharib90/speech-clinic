<?php

namespace App\Http\Controllers;

use App\Models\ArticulationAssessment;
use App\Models\TherapyProgram;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class TherapyProgramController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', TherapyProgram::class);

        $status = $request->input('status', 'all');
        $search = $request->input('search');

        $baseQuery = TherapyProgram::query()
            ->when(auth()->user()->hasRole('أخصائي تخاطب'), function ($query) {
                $query->where('therapist_id', auth()->id());
            });

        $statusCounts = (clone $baseQuery)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $programs = (clone $baseQuery)
            ->with(['patient', 'therapist', 'progressPoints'])
            ->withCount(['sessions', 'progressPoints', 'articulationAssessments', 'stutteringAssessments'])
            ->when($status !== 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('disorder_type', 'like', "%{$search}%")
                        ->orWhereHas('patient', function ($patientQuery) use ($search) {
                            $patientQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('therapist', function ($therapistQuery) use ($search) {
                            $therapistQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $statusLabels = ['all' => 'كل البرامج'] + TherapyProgram::STATUS_LABELS;

        return view('clinical.programs.index', compact('programs', 'status', 'search', 'statusCounts', 'statusLabels'));
    }

    public function show(TherapyProgram $program)
    {
        $this->authorize('view', $program);

        $program->load([
            'patient.caseHistory',
            'therapist',
            'sessions.homeTasks',
            'milestones',
            'attachments',
            'articulationAssessments.assessedBy',
            'stutteringAssessments.assessedBy',
            'progressPoints.recordedBy',
            'dischargeSummary.preparedBy',
        ]);

        $soundBank = ArticulationAssessment::soundBank();
        $articulationStatusLabels = ArticulationAssessment::STATUS_LABELS;

        return view('clinical.programs.show', compact('program', 'soundBank', 'articulationStatusLabels'));
    }

    public function progressReport(TherapyProgram $program)
    {
        $this->authorize('view', $program);

        $program->load([
            'milestones',
            'sessions',
            'patient.guardian',
            'therapist',
        ]);

        $pdf = Pdf::loadView('clinical.programs.progress-pdf', compact('program'))
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        $fileName = "تقرير_تطور_{$program->patient->name}_" . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($fileName);
    }
}