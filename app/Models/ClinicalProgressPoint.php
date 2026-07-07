<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicalProgressPoint extends Model
{
    protected $fillable = [
        'therapy_program_id',
        'recorded_by',
        'recorded_at',
        'domain',
        'score',
        'notes',
    ];

    protected $casts = [
        'recorded_at' => 'date',
        'score' => 'decimal:2',
    ];

    public function program()
    {
        return $this->belongsTo(TherapyProgram::class, 'therapy_program_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
