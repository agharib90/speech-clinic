<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'specialty_id',
        'name',
        'description',
        'default_duration_minutes',
        'customer_price',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'customer_price' => 'decimal:2',
    ];

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function therapists(): BelongsToMany
    {
        return $this->belongsToMany(Therapist::class)->withTimestamps();
    }

    public function therapistRates(): HasMany
    {
        return $this->hasMany(TherapistServiceRate::class);
    }

    public function patientPlanItems(): HasMany
    {
        return $this->hasMany(PatientServicePlanItem::class);
    }
}
