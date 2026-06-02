<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TherapistEarning extends Model
{
    protected $fillable = [
        'therapist_id', 'therapy_session_id', 'amount', 'type'
    ];

    public function therapist()
    {
        return $this->belongsTo(Therapist::class);
    }

    public function session()
    {
        return $this->belongsTo(TherapySession::class, 'therapy_session_id');
    }
}
