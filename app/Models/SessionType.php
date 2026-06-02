<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SessionType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'duration_minutes', 'price', 'color'
    ];

    // نوع الجلسة يُستخدم في عدة مواعيد
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
