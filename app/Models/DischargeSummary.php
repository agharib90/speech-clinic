<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DischargeSummary extends Model
{
    protected $fillable = [
        'therapy_program_id',
        'prepared_by',
        'discharge_date',
        'reason',
        'initial_status',
        'final_status',
        'goals_outcome',
        'recommendations',
        'follow_up_plan',
        'notes',
    ];

    protected $casts = [
        'discharge_date' => 'date',
    ];

    public function program()
    {
        return $this->belongsTo(TherapyProgram::class, 'therapy_program_id');
    }

    public function preparedBy()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }
}
