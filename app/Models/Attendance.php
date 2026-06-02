<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'therapist_id', 'attendance_date', 'status', 'notes'
    ];

    public function therapist()
    {
        return $this->belongsTo(Therapist::class);
    }
}
