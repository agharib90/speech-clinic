<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatientServicePlanPayment extends Model
{
    protected $fillable = [
        'patient_service_plan_id',
        'invoice_payment_id',
        'amount_snapshot',
        'created_by',
    ];

    protected $casts = [
        'amount_snapshot' => 'decimal:2',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PatientServicePlan::class, 'patient_service_plan_id');
    }

    public function invoicePayment(): BelongsTo
    {
        return $this->belongsTo(InvoicePayment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PatientServicePlanAllocation::class);
    }
}
