<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgramPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'therapy_program_id', 'amount', 'payment_date', 'method', 'notes'
    ];

    public function program()
    {
        return $this->belongsTo(TherapyProgram::class, 'therapy_program_id');
    }
}
