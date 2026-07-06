<?php

namespace App\Policies;

use App\Models\TherapyProgram;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class TherapyProgramPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view therapy');
    }

    /**
     * Determine whether the user can view the model.
     */
    use HandlesAuthorization;

    public function view(User $user, TherapyProgram $program)
    {
        // المدير والمسؤول المالي وموظف الاستقبال يمكنهم رؤية كل البرامج
        if ($user->hasRole(['مدير النظام', 'مسؤول مالي', 'موظف استقبال'])) {
            return true;
        }

        if ($user->hasRole('أخصائي تخاطب')) {
            return $program->therapist_id === $user->id;
        }

        return false;
    }


    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TherapyProgram $therapyProgram): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TherapyProgram $therapyProgram): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TherapyProgram $therapyProgram): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TherapyProgram $therapyProgram): bool
    {
        return false;
    }
}
