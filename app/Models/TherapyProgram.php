<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TherapyProgram extends Model
{
    use SoftDeletes;

    public const STATUS_ACTIVE = 'جاري';
    public const STATUS_COMPLETED = 'مكتمل';
    public const STATUS_STOPPED = 'متوقف';

    public const STATUS_LABELS = [
        self::STATUS_ACTIVE => 'جاري',
        self::STATUS_COMPLETED => 'مكتمل',
        self::STATUS_STOPPED => 'متوقف',
    ];

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

    public function articulationAssessments()
    {
        return $this->hasMany(ArticulationAssessment::class)->latest('assessed_at');
    }

    public function stutteringAssessments()
    {
        return $this->hasMany(StutteringAssessment::class)->latest('assessed_at');
    }

    public function progressPoints()
    {
        return $this->hasMany(ClinicalProgressPoint::class)->orderBy('recorded_at');
    }

    public function dischargeSummary()
    {
        return $this->hasOne(DischargeSummary::class);
    }
}
