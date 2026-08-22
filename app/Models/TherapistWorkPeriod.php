<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TherapistWorkPeriod extends Model
{
    public const WEEKDAYS = [
        6 => 'السبت',
        0 => 'الأحد',
        1 => 'الاثنين',
        2 => 'الثلاثاء',
        3 => 'الأربعاء',
        4 => 'الخميس',
        5 => 'الجمعة',
    ];

    protected $fillable = [
        'therapist_id',
        'weekday',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'weekday' => 'integer',
    ];

    public function therapist(): BelongsTo
    {
        return $this->belongsTo(Therapist::class);
    }
}
