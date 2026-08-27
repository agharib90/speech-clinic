<?php

namespace App\Models;

use App\Services\PatientServicePlanAllocator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatientServicePlan extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'مسودة',
        self::STATUS_ACTIVE => 'نشطة',
        self::STATUS_COMPLETED => 'مكتملة',
        self::STATUS_CANCELLED => 'ملغاة',
    ];

    protected $fillable = [
        'patient_id',
        'clinical_evaluation_id',
        'status',
        'starts_at',
        'ends_at',
        'notes',
        'created_by',
        'clinical_approved_by',
        'clinical_approved_at',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'clinical_approved_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function clinicalEvaluation(): BelongsTo
    {
        return $this->belongsTo(PatientClinicalEvaluation::class, 'clinical_evaluation_id');
    }

    public function clinicalApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'clinical_approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PatientServicePlanItem::class)->orderBy('position');
    }

    public function planPayments(): HasMany
    {
        return $this->hasMany(PatientServicePlanPayment::class);
    }

    public function paidAmount(): string
    {
        return PatientServicePlanAllocator::centsToDecimal(
            PatientServicePlanAllocator::decimalToCents((string) $this->planPayments()->sum('amount_snapshot'))
        );
    }

    public function allocatedAmount(): string
    {
        $amount = PatientServicePlanAllocation::query()
            ->whereHas('planPayment', fn ($query) => $query->where('patient_service_plan_id', $this->id))
            ->sum('allocated_amount');

        return PatientServicePlanAllocator::centsToDecimal(
            PatientServicePlanAllocator::decimalToCents((string) $amount)
        );
    }

    public function availableCredit(): string
    {
        $paid = PatientServicePlanAllocator::decimalToCents($this->paidAmount());
        $allocated = PatientServicePlanAllocator::decimalToCents($this->allocatedAmount());

        return PatientServicePlanAllocator::centsToDecimal(max(0, $paid - $allocated));
    }

    public function totalAmount(): string
    {
        $total = $this->items->sum(function (PatientServicePlanItem $item) {
            return PatientServicePlanAllocator::decimalToCents($item->final_unit_price) * $item->planned_quantity;
        });

        return PatientServicePlanAllocator::centsToDecimal($total);
    }

    public function nextUnfundedItem(): ?PatientServicePlanItem
    {
        return $this->items()->whereColumn('authorized_quantity', '<', 'planned_quantity')->first();
    }

    public function hasProtectedHistory(): bool
    {
        return $this->planPayments()->exists()
            || PatientServicePlanAllocation::query()
                ->whereHas('item', fn ($query) => $query->where('patient_service_plan_id', $this->id))
                ->exists()
            || $this->items()
                ->where(function ($query) {
                    $query->where('authorized_quantity', '>', 0)
                        ->orWhere('consumed_quantity', '>', 0);
                })
                ->exists();
    }

    public function canFullyEdit(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_ACTIVE], true)
            && ! $this->isClinicallyApproved()
            && ! $this->hasProtectedHistory();
    }

    public function isClinicallyApproved(): bool
    {
        return $this->clinical_approved_at !== null;
    }

    public function canDelete(): bool
    {
        return $this->status === self::STATUS_DRAFT
            && ! $this->isClinicallyApproved()
            && ! $this->hasProtectedHistory();
    }

    public function unpaidAmount(): string
    {
        $total = PatientServicePlanAllocator::decimalToCents($this->totalAmount());
        $paid = PatientServicePlanAllocator::decimalToCents($this->paidAmount());

        return PatientServicePlanAllocator::centsToDecimal(max(0, $total - $paid));
    }
}
