<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function evaluatedClinicalEvaluations(): HasMany
    {
        return $this->hasMany(PatientClinicalEvaluation::class, 'evaluated_by');
    }

    public function completedClinicalEvaluations(): HasMany
    {
        return $this->hasMany(PatientClinicalEvaluation::class, 'completed_by');
    }

    public function clinicallyApprovedServicePlans(): HasMany
    {
        return $this->hasMany(PatientServicePlan::class, 'clinical_approved_by');
    }

    public function clinicalEvaluationAssignments(): HasMany
    {
        return $this->hasMany(PatientClinicalEvaluationAssignment::class, 'assigned_to');
    }

    public function createdClinicalEvaluationAssignments(): HasMany
    {
        return $this->hasMany(PatientClinicalEvaluationAssignment::class, 'assigned_by');
    }

    public function therapist(): HasOne
    {
        return $this->hasOne(Therapist::class);
    }

    public function therapistIncludingTrashed(): HasOne
    {
        return $this->hasOne(Therapist::class)->withTrashed();
    }
}
