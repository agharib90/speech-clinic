<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guardian extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'phone', 'phone2', 'email', 'address', 'national_id', 'notes'
    ];

    // ولي الأمر لديه أبناء (مرضى)
    public function patients()
    {
        return $this->hasMany(Patient::class);
    }
}
