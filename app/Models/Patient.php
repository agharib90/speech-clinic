<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'guardian_id', 'name', 'birth_date', 'gender', 'diagnosis',
        'barcode', 'qr_code', 'referral_source', 'is_active', 'notes'
    ];
    protected $casts = [
        'birth_date' => 'date',
        'is_active' => 'boolean',
    ];


    // المريض يتبع ولي أمر
    public function guardian()
    {
        return $this->belongsTo(Guardian::class);
    }

    // المريض لديه مواعيد
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    // المريض لديه سجل حضور
    public function checkins()
    {
        return $this->hasMany(PatientCheckin::class);
    }

    // المريض لديه برامج علاجية
    public function therapyPrograms()
    {
        return $this->hasMany(TherapyProgram::class);
    }

    protected $appends = ['age'];

public function getAgeAttribute(): string
{
    $years = $this->birth_date->diffInYears(now());
    $months = $this->birth_date->diffInMonths(now()) % 12;

    if ($years === 0) return "{$months} شهر";
    if ($months === 0) return "{$years} سنة";
    return "{$years} سنة و {$months} شهر";
}

}
