<?php

namespace App\Support;

use App\Models\Patient;
use Illuminate\Http\Request;

final class PatientWorkspaceContext
{
    public static function validate(Request $request, int $patientId): bool
    {
        if (! $request->boolean('workspace')) {
            return false;
        }

        abort_unless(
            $request->hasValidSignature()
                && $request->integer('workspace_patient') === $patientId
                && $request->user()?->can('view patients'),
            403
        );

        PatientAccess::authorize(Patient::findOrFail($patientId), $request->user());

        return true;
    }
}
