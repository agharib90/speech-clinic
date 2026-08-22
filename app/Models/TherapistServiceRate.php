<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TherapistServiceRate extends Model
{
    protected $fillable = [
        'therapist_id',
        'service_id',
        'amount',
        'effective_from',
        'effective_to',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $rate) {
            if ((float) $rate->amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'سعر استحقاق الأخصائي يجب أن يكون أكبر من صفر.',
                ]);
            }

            $overlapExists = self::query()
                ->where('therapist_id', $rate->therapist_id)
                ->where('service_id', $rate->service_id)
                ->whereDate('effective_from', '<=', $rate->effective_to ?? '9999-12-31')
                ->where(function ($query) use ($rate) {
                    $query->whereNull('effective_to')
                        ->orWhereDate('effective_to', '>=', $rate->effective_from);
                })
                ->exists();

            if ($overlapExists) {
                throw ValidationException::withMessages([
                    'effective_from' => 'تتداخل فترة سعر الاستحقاق مع فترة مسجلة بالفعل.',
                ]);
            }
        });
    }

    public function therapist(): BelongsTo
    {
        return $this->belongsTo(Therapist::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function resolveFor(
        Therapist|int $therapist,
        Service|int $service,
        DateTimeInterface|string $date
    ): ?self {
        $targetDate = CarbonImmutable::parse($date)->toDateString();

        return self::query()
            ->where('therapist_id', $therapist instanceof Therapist ? $therapist->id : $therapist)
            ->where('service_id', $service instanceof Service ? $service->id : $service)
            ->whereDate('effective_from', '<=', $targetDate)
            ->where(function ($query) use ($targetDate) {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $targetDate);
            })
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }

    public static function start(
        Therapist $therapist,
        Service $service,
        float|string $amount,
        DateTimeInterface|string $effectiveFrom,
        ?int $createdBy = null
    ): self {
        $startDate = CarbonImmutable::parse($effectiveFrom)->startOfDay();

        $offersService = $therapist->services()->whereKey($service->id)->exists();
        $hasSpecialty = $therapist->specialties()->whereKey($service->specialty_id)->exists();

        if (! $offersService || ! $hasSpecialty) {
            throw ValidationException::withMessages([
                'service_id' => 'يجب إسناد الخدمة وتخصصها إلى الأخصائي قبل تسجيل سعر الاستحقاق.',
            ]);
        }

        return DB::transaction(function () use ($therapist, $service, $amount, $startDate, $createdBy) {
            $alreadyStartsOnDate = self::query()
                ->where('therapist_id', $therapist->id)
                ->where('service_id', $service->id)
                ->whereDate('effective_from', $startDate->toDateString())
                ->lockForUpdate()
                ->first();

            if ($alreadyStartsOnDate) {
                if ((float) $alreadyStartsOnDate->amount === (float) $amount) {
                    return $alreadyStartsOnDate;
                }

                throw ValidationException::withMessages([
                    'effective_from' => 'يوجد سعر استحقاق يبدأ في هذا التاريخ بالفعل. اختر تاريخًا جديدًا للحفاظ على السجل التاريخي.',
                ]);
            }

            $previous = self::query()
                ->where('therapist_id', $therapist->id)
                ->where('service_id', $service->id)
                ->whereDate('effective_from', '<', $startDate->toDateString())
                ->orderByDesc('effective_from')
                ->lockForUpdate()
                ->first();

            $next = self::query()
                ->where('therapist_id', $therapist->id)
                ->where('service_id', $service->id)
                ->whereDate('effective_from', '>', $startDate->toDateString())
                ->orderBy('effective_from')
                ->lockForUpdate()
                ->first();

            if ($previous && (! $previous->effective_to || $previous->effective_to->gte($startDate))) {
                $previous->update(['effective_to' => $startDate->subDay()->toDateString()]);
            }

            return self::create([
                'therapist_id' => $therapist->id,
                'service_id' => $service->id,
                'amount' => $amount,
                'effective_from' => $startDate->toDateString(),
                'effective_to' => $next
                    ? CarbonImmutable::parse($next->effective_from)->subDay()->toDateString()
                    : null,
                'created_by' => $createdBy,
            ]);
        });
    }
}
