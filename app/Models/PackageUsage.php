<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageUsage extends Model
{
    protected $fillable = [
        'session_package_id', 'therapy_session_id', 'used_at'
    ];

    public function package()
    {
        return $this->belongsTo(SessionPackage::class, 'session_package_id');
    }

    public function session()
    {
        return $this->belongsTo(TherapySession::class, 'therapy_session_id');
    }
}
