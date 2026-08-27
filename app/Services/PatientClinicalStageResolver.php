<?php

namespace App\Services;

use App\Models\PatientClinicalEvaluation;
use App\Models\PatientClinicalEvaluationAssignment;
use App\Models\PatientServicePlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class PatientClinicalStageResolver
{
    public const FILTERS = [
        'awaiting_assignment' => 'بانتظار إسناد التقييم',
        'awaiting_evaluation' => 'بانتظار التقييم',
        'evaluation_draft' => 'التقييم قيد الاستكمال',
        'plan_preparation' => 'الخطة قيد الإعداد',
        'handoff_ready' => 'جاهزة للاستقبال',
        'operational' => 'قيد التنفيذ',
    ];

    public function resolve(
        User $user,
        ?PatientClinicalEvaluationAssignment $assignment,
        ?PatientClinicalEvaluation $evaluation,
        ?PatientServicePlan $clinicalPlan,
        ?PatientServicePlan $approvedPlan,
        ?PatientServicePlan $activePlan,
        ?PatientServicePlan $legacyDraftPlan = null
    ): array {
        $canManageEvaluation = $user->can('manage clinical evaluations');
        $ownsAssignment = $assignment?->assigned_to === $user->id;
        $canEditEvaluation = $canManageEvaluation
            && ((! $evaluation && $assignment?->isPending() && $ownsAssignment)
                || ($evaluation?->isDraft()
                    && ($evaluation->evaluated_by === null || $evaluation->evaluated_by === $user->id)
                    && (! $assignment || $ownsAssignment)));

        $secondary = $this->secondary($assignment, $evaluation, $clinicalPlan, $approvedPlan, $activePlan);

        if ($activePlan) {
            return $this->stage(
                'operational',
                'الخطة العلاجية قيد التنفيذ',
                'تستمر الخطة النشطة وفق دورتها التشغيلية والمالية الحالية.',
                $evaluation,
                $secondary ? ($approvedPlan ?: $clinicalPlan) : $activePlan,
                $canEditEvaluation,
                $assignment,
                $secondary
            );
        }

        if ($assignment?->isPending() && ! $evaluation) {
            return $this->stage('awaiting_evaluation', 'بانتظار التقييم', 'تم إسناد الحالة وتنتظر بدء التقييم بواسطة المختص المكلّف.', null, null, $canEditEvaluation, $assignment);
        }

        if ($evaluation?->isDraft() || $assignment?->isInProgress()) {
            return $this->stage('evaluation_draft', 'التقييم قيد الاستكمال', 'بدأ المختص المكلّف التقييم ويمكنه استكمال المسودة.', $evaluation, $clinicalPlan, $canEditEvaluation, $assignment);
        }

        if ($approvedPlan) {
            return $this->stage('handoff_ready', 'الخطة جاهزة للاستقبال', 'تم اعتماد الخطة سريريًا وهي جاهزة لاستكمال إجراءات الاستقبال.', $evaluation, $approvedPlan, false, $assignment);
        }

        if ($evaluation?->isCompleted()) {
            return $this->stage('plan_preparation', 'الخطة العلاجية قيد الإعداد', 'اكتمل التقييم السريري، والخطوة التالية تحديد الخدمات والكميات العلاجية.', $evaluation, $clinicalPlan, false, $assignment);
        }

        if ($legacyDraftPlan) {
            return $this->stage(
                'legacy_plan',
                'خطة الخدمات قيد الإعداد',
                'تستمر هذه الخطة القديمة عبر دورة التشغيل المعتادة دون فرض مسار التقييم السريري عليها.',
                null,
                $legacyDraftPlan,
                false,
                null
            );
        }

        return $this->stage('awaiting_assignment', 'بانتظار إسناد التقييم', 'تم تسجيل الحالة وتنتظر إسنادها إلى مختص سريري.', null, null, false, $assignment);
    }

    public function applyFilter(Builder $query, string $filter): void
    {
        match ($filter) {
            'awaiting_assignment' => $query
                ->whereDoesntHave('clinicalEvaluationAssignments', fn (Builder $q) => $q->whereIn('status', PatientClinicalEvaluationAssignment::OPEN_STATUSES))
                ->whereDoesntHave('clinicalEvaluations')
                ->whereDoesntHave('servicePlans', fn (Builder $q) => $q
                    ->whereIn('status', [PatientServicePlan::STATUS_ACTIVE, PatientServicePlan::STATUS_DRAFT])
                    ->where(function (Builder $plan) {
                        $plan->where('status', PatientServicePlan::STATUS_ACTIVE)
                            ->orWhereNull('clinical_evaluation_id');
                    })),
            'awaiting_evaluation' => $query->whereHas('clinicalEvaluationAssignments', fn (Builder $q) => $q->where('status', PatientClinicalEvaluationAssignment::STATUS_PENDING)),
            'evaluation_draft' => $query->where(function (Builder $q) {
                $q->whereHas('clinicalEvaluationAssignments', fn (Builder $assignment) => $assignment->where('status', PatientClinicalEvaluationAssignment::STATUS_IN_PROGRESS))
                    ->orWhereHas('clinicalEvaluations', fn (Builder $evaluation) => $evaluation->where('status', PatientClinicalEvaluation::STATUS_DRAFT));
            }),
            'plan_preparation' => $query->where(function (Builder $patient) {
                $patient->whereHas('clinicalEvaluations', function (Builder $evaluation) {
                    $evaluation->where('status', PatientClinicalEvaluation::STATUS_COMPLETED)
                        ->whereDoesntHave('servicePlans', fn (Builder $plan) => $plan->whereNotNull('clinical_approved_at'));
                })->orWhereHas('servicePlans', fn (Builder $plan) => $plan
                    ->where('status', PatientServicePlan::STATUS_DRAFT)
                    ->whereNull('clinical_evaluation_id'));
            }),
            'handoff_ready' => $query->whereHas('servicePlans', fn (Builder $q) => $q
                ->where('status', PatientServicePlan::STATUS_DRAFT)
                ->whereNotNull('clinical_evaluation_id')
                ->whereNotNull('clinical_approved_at')),
            'operational' => $query->whereHas('servicePlans', fn (Builder $q) => $q->where('status', PatientServicePlan::STATUS_ACTIVE)),
            default => null,
        };
    }

    private function secondary($assignment, $evaluation, $clinicalPlan, $approvedPlan, $activePlan): ?array
    {
        if (! $activePlan) {
            return null;
        }

        if ($assignment?->isPending() && ! $evaluation) {
            return ['key' => 'awaiting_evaluation', 'title' => 'بانتظار تقييم جديد', 'description' => 'يستمر العلاج الحالي أثناء انتظار بدء إعادة التقييم.'];
        }

        if ($evaluation?->isDraft() || $assignment?->isInProgress()) {
            return ['key' => 'evaluation_draft', 'title' => 'يوجد تقييم جديد قيد الاستكمال', 'description' => 'يستمر العلاج الحالي كالمعتاد أثناء استكمال إعادة التقييم.'];
        }

        if ($approvedPlan && $approvedPlan->id !== $activePlan->id) {
            return ['key' => 'handoff_ready', 'title' => 'خطة دورة علاجية جديدة جاهزة للاستقبال', 'description' => 'الخطة الحالية مستمرة، والخطة الجديدة معتمدة وتنتظر إجراءات الاستقبال.'];
        }

        if ($evaluation?->isCompleted() && $clinicalPlan?->id !== $activePlan->id) {
            return ['key' => 'plan_preparation', 'title' => 'إعادة التقييم مكتملة', 'description' => 'يمكن إعداد خطة جديدة للدورة العلاجية التالية دون تعديل الخطة النشطة.'];
        }

        return null;
    }

    private function stage(string $key, string $title, string $description, $evaluation, $plan, bool $canEdit, $assignment, ?array $secondary = null): array
    {
        return [
            'key' => $key,
            'title' => $title,
            'description' => $description,
            'evaluation' => $evaluation,
            'plan' => $plan,
            'assignment' => $assignment,
            'can_edit_evaluation' => $canEdit,
            'secondary_key' => $secondary['key'] ?? null,
            'secondary_title' => $secondary['title'] ?? null,
            'secondary_description' => $secondary['description'] ?? null,
        ];
    }
}
