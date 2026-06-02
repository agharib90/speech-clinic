<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TherapyProgram extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'patient_id', 'therapist_id', 'disorder_type', 'goals',
        'status', 'start_date', 'end_date', 'sessions_per_week', 'session_price', 'notes'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function therapist()
    {
        return $this->belongsTo(User::class, 'therapist_id');
    }

    public function sessions()
    {
        return $this->hasMany(TherapySession::class);
    }

        public function milestones()
    {
        return $this->hasMany(SessionMilestone::class);
    }

    public function attachments()
    {
        return $this->hasMany(ProgramAttachment::class, 'therapy_program_id');
    }
}
