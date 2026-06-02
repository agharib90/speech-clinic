<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SessionPackage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id', 'name', 'total_sessions', 'used_sessions',
        'price_paid', 'start_date', 'expiry_date', 'status'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function usages()
    {
        return $this->hasMany(PackageUsage::class);
    }
}
