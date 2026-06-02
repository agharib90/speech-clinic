<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollRecord extends Model
{
    protected $fillable = [
        'therapist_id', 'type', 'amount', 'description', 'month', 'year'
    ];

    public function therapist()
    {
        return $this->belongsTo(Therapist::class);
    }
}
