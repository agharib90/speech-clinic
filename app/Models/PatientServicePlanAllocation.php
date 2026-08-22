<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientServicePlanAllocation extends Model
{
    protected $fillable = [
        'patient_service_plan_payment_id',
        'patient_service_plan_item_id',
        'allocated_amount',
        'authorized_quantity',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
        'authorized_quantity' => 'integer',
    ];

    public function planPayment(): BelongsTo
    {
        return $this->belongsTo(PatientServicePlanPayment::class, 'patient_service_plan_payment_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(PatientServicePlanItem::class, 'patient_service_plan_item_id');
    }
}
