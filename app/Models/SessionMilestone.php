<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SessionMilestone extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'therapy_program_id', 'recorded_at', 'skill_area', 'baseline_score', 'current_score', 'notes'
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'baseline_score' => 'decimal:2',
        'current_score' => 'decimal:2',
    ];

    public function program()
    {
        return $this->belongsTo(TherapyProgram::class, 'therapy_program_id');
    }
}
