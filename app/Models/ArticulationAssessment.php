<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticulationAssessment extends Model
{
    public const STATUS_CORRECT = 'correct';
    public const STATUS_SUBSTITUTED = 'substituted';
    public const STATUS_OMITTED = 'omitted';
    public const STATUS_DISTORTED = 'distorted';

    public const STATUS_LABELS = [
        self::STATUS_CORRECT => 'صحيح',
        self::STATUS_SUBSTITUTED => 'مبدل',
        self::STATUS_OMITTED => 'محذوف',
        self::STATUS_DISTORTED => 'مشوه',
    ];

    protected $fillable = [
        'therapy_program_id',
        'assessed_by',
        'assessed_at',
        'title',
        'responses',
        'total_items',
        'correct_count',
        'substitution_count',
        'omission_count',
        'distortion_count',
        'accuracy_percent',
        'notes',
    ];

    protected $casts = [
        'assessed_at' => 'date',
        'responses' => 'array',
        'total_items' => 'integer',
        'correct_count' => 'integer',
        'substitution_count' => 'integer',
        'omission_count' => 'integer',
        'distortion_count' => 'integer',
        'accuracy_percent' => 'decimal:2',
    ];

    public static function soundBank(): array
    {
        return [
            ['sound' => 'أ', 'position' => 'أول الكلمة', 'prompt_word' => 'أسد'],
            ['sound' => 'ب', 'position' => 'أول الكلمة', 'prompt_word' => 'باب'],
            ['sound' => 'ت', 'position' => 'أول الكلمة', 'prompt_word' => 'تمر'],
            ['sound' => 'ث', 'position' => 'أول الكلمة', 'prompt_word' => 'ثعلب'],
            ['sound' => 'ج', 'position' => 'أول الكلمة', 'prompt_word' => 'جمل'],
            ['sound' => 'ح', 'position' => 'أول الكلمة', 'prompt_word' => 'حوت'],
            ['sound' => 'خ', 'position' => 'أول الكلمة', 'prompt_word' => 'خروف'],
            ['sound' => 'د', 'position' => 'أول الكلمة', 'prompt_word' => 'دب'],
            ['sound' => 'ذ', 'position' => 'أول الكلمة', 'prompt_word' => 'ذرة'],
            ['sound' => 'ر', 'position' => 'أول الكلمة', 'prompt_word' => 'رمان'],
            ['sound' => 'ز', 'position' => 'أول الكلمة', 'prompt_word' => 'زهرة'],
            ['sound' => 'س', 'position' => 'أول الكلمة', 'prompt_word' => 'سمكة'],
            ['sound' => 'ش', 'position' => 'أول الكلمة', 'prompt_word' => 'شمس'],
            ['sound' => 'ص', 'position' => 'أول الكلمة', 'prompt_word' => 'صاروخ'],
            ['sound' => 'ض', 'position' => 'أول الكلمة', 'prompt_word' => 'ضفدع'],
            ['sound' => 'ط', 'position' => 'أول الكلمة', 'prompt_word' => 'طائرة'],
            ['sound' => 'ظ', 'position' => 'أول الكلمة', 'prompt_word' => 'ظرف'],
            ['sound' => 'ع', 'position' => 'أول الكلمة', 'prompt_word' => 'عين'],
            ['sound' => 'غ', 'position' => 'أول الكلمة', 'prompt_word' => 'غزال'],
            ['sound' => 'ف', 'position' => 'أول الكلمة', 'prompt_word' => 'فيل'],
            ['sound' => 'ق', 'position' => 'أول الكلمة', 'prompt_word' => 'قلم'],
            ['sound' => 'ك', 'position' => 'أول الكلمة', 'prompt_word' => 'كتاب'],
            ['sound' => 'ل', 'position' => 'أول الكلمة', 'prompt_word' => 'ليمون'],
            ['sound' => 'م', 'position' => 'أول الكلمة', 'prompt_word' => 'موز'],
            ['sound' => 'ن', 'position' => 'أول الكلمة', 'prompt_word' => 'نمر'],
            ['sound' => 'هـ', 'position' => 'أول الكلمة', 'prompt_word' => 'هرم'],
            ['sound' => 'و', 'position' => 'أول الكلمة', 'prompt_word' => 'وردة'],
            ['sound' => 'ي', 'position' => 'أول الكلمة', 'prompt_word' => 'يد'],
        ];
    }

    public static function allowedStatuses(): array
    {
        return array_keys(self::STATUS_LABELS);
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
