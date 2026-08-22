<?php

namespace App\Support;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class PatientAccess
{
    public static function scope(Builder $query, ?User $user): void
    {
        if (! $user?->hasRole('أخصائي تخاطب')) {
            return;
        }

        $query->where(function (Builder $patientQuery) use ($user) {
            $patientQuery->whereHas('therapyPrograms', function (Builder $programQuery) use ($user) {
                $programQuery->where('therapist_id', $user->id);
            })->orWhereHas('appointments', function (Builder $appointmentQuery) use ($user) {
                $appointmentQuery->where('therapist_id', $user->id);
            });
        });
    }

    public static function authorize(Patient $patient, ?User $user): void
    {
        if (! $user?->hasRole('أخصائي تخاطب')) {
            return;
        }

        $hasAccess = $patient->therapyPrograms()
            ->where('therapist_id', $user->id)
            ->exists()
            || $patient->appointments()
                ->where('therapist_id', $user->id)
                ->exists();

        abort_unless($hasAccess, 403);
    }
}
