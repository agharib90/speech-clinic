<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HomeTask extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'therapy_session_id', 'description', 'due_date', 'is_completed', 'parent_feedback'
    ];

    public function session()
    {
        return $this->belongsTo(TherapySession::class, 'therapy_session_id');
    }
}
