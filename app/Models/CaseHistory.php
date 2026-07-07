<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseHistory extends Model
{
    protected $fillable = [
        'patient_id',
        'taken_by',
        'taken_at',
        'main_concerns',
        'prenatal_history',
        'birth_history',
        'developmental_milestones',
        'medical_history',
        'hearing_vision_notes',
        'family_history',
        'language_environment',
        'previous_interventions',
        'notes',
    ];

    protected $casts = [
        'taken_at' => 'date',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function takenBy()
    {
        return $this->belongsTo(User::class, 'taken_by');
    }
}
