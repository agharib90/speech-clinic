<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PatientServicePlanItem extends Model
{
    protected $fillable = [
        'patient_service_plan_id',
        'service_id',
        'position',
        'planned_quantity',
        'customer_unit_price',
        'discount_amount',
        'final_unit_price',
        'authorized_quantity',
        'consumed_quantity',
    ];

    protected $casts = [
        'customer_unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_unit_price' => 'decimal:2',
        'position' => 'integer',
        'planned_quantity' => 'integer',
        'authorized_quantity' => 'integer',
        'consumed_quantity' => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PatientServicePlan::class, 'patient_service_plan_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PatientServicePlanAllocation::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function remainingAuthorizedQuantity(): int
    {
        return max(0, $this->authorized_quantity - $this->consumed_quantity);
    }

    public function unpaidQuantity(): int
    {
        return max(0, $this->planned_quantity - $this->authorized_quantity);
    }

    public function canConsume(int $quantity = 1): bool
    {
        return $quantity > 0 && $this->consumed_quantity + $quantity <= $this->authorized_quantity;
    }

    public function consume(int $quantity = 1): self
    {
        return DB::transaction(function () use ($quantity) {
            $item = self::query()->lockForUpdate()->findOrFail($this->id);

            if (! $item->canConsume($quantity)) {
                throw ValidationException::withMessages([
                    'quantity' => 'لا يمكن استخدام وحدات أكثر من الوحدات المدفوعة المتاحة.',
                ]);
            }

            $item->increment('consumed_quantity', $quantity);

            return $item->fresh();
        });
    }
}
