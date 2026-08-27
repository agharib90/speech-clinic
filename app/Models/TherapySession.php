<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TherapySession extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'therapy_program_id', 'appointment_id', 'session_date', 'session_number',
        'duration_minutes', 'status', 'notes', 'goals_achieved', 'next_session_plan', 'internal_notes',
    ];

    public function program()
    {
        return $this->belongsTo(TherapyProgram::class, 'therapy_program_id');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function earning()
    {
        return $this->hasOne(TherapistEarning::class);
    }

    public function homeTasks()
    {
        return $this->hasMany(HomeTask::class);
    }
}
