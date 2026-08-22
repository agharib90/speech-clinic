<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id', 'therapist_id', 'session_type_id', 'scheduled_at',
        'patient_service_plan_item_id', 'confirmation_deposit_percentage_snapshot',
        'confirmation_deposit_amount_snapshot', 'financially_confirmed_at',
        'end_at', 'status', 'notes', 'legacy_booking_reason',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'end_at' => 'datetime',
        'confirmation_deposit_percentage_snapshot' => 'integer',
        'confirmation_deposit_amount_snapshot' => 'decimal:2',
        'financially_confirmed_at' => 'datetime',
    ];

    // الموعد لمريض واحد
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    // الموعد مع أخصائي واحد (User)
    public function therapist()
    {
        return $this->belongsTo(User::class, 'therapist_id');
    }

    // الموعد من نوع جلسة واحد
    public function sessionType()
    {
        return $this->belongsTo(SessionType::class);
    }

    public function patientServicePlanItem(): BelongsTo
    {
        return $this->belongsTo(PatientServicePlanItem::class);
    }

    // الموعد ممكن يكون له سجل حضور
    public function checkin()
    {
        return $this->hasOne(PatientCheckin::class);
    }

    // الموعد ممكن يتحول لجلسة علاجية
    public function therapySession()
    {
        return $this->hasOne(TherapySession::class);
    }
}
