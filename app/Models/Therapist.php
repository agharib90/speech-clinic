<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Therapist extends Model
{
    use SoftDeletes;
    protected $casts = [
        'hire_date' => 'date', // هذا يحول النص من DB إلى كائن Carbon أوتوماتيكياً
    ];

    protected $fillable = [
        'user_id', 'name', 'specialization', 'phone', 'email', 'license_number',
        'hire_date', 'salary_type', 'daily_salary', 'monthly_salary',
        'commission_rate', 'is_active'
    ];

    // مربوط بحساب دخول
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function earnings()
    {
        return $this->hasMany(TherapistEarning::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function payrollRecords()
    {
        return $this->hasMany(PayrollRecord::class);
    }

    public function specialties(): BelongsToMany
    {
        return $this->belongsToMany(Specialty::class)->withTimestamps();
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)->withTimestamps();
    }

    public function serviceRates(): HasMany
    {
        return $this->hasMany(TherapistServiceRate::class);
    }
}
