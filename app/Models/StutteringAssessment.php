<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StutteringAssessment extends Model
{
    protected $fillable = [
        'therapy_program_id',
        'assessed_by',
        'assessed_at',
        'sample_context',
        'syllables_count',
        'stuttered_syllables_count',
        'stuttering_percentage',
        'frequency_score',
        'duration_score',
        'physical_concomitants_score',
        'total_score',
        'severity',
        'notes',
    ];

    protected $casts = [
        'assessed_at' => 'date',
        'syllables_count' => 'integer',
        'stuttered_syllables_count' => 'integer',
        'stuttering_percentage' => 'decimal:2',
        'frequency_score' => 'integer',
        'duration_score' => 'integer',
        'physical_concomitants_score' => 'integer',
        'total_score' => 'integer',
    ];

    public static function frequencyScore(float $percentage): int
    {
        return match (true) {
            $percentage <= 0 => 0,
            $percentage <= 2 => 2,
            $percentage <= 5 => 4,
            $percentage <= 8 => 6,
            $percentage <= 12 => 8,
            default => 10,
        };
    }

    public static function severityForTotal(int $total): string
    {
        return match (true) {
            $total <= 6 => 'بسيطة جدًا',
            $total <= 13 => 'بسيطة',
            $total <= 20 => 'متوسطة',
            $total <= 27 => 'شديدة',
            default => 'شديدة جدًا',
        };
    }

    public function program()
    {
        return $this->belongsTo(TherapyProgram::class, 'therapy_program_id');
    }

    public function assessedBy()
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }
}
