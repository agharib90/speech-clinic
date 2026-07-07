<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientCheckin extends Model
{
    // ملحوظة: لم نستخدم SoftDeletes هنا كما اتفقنا في المهاجرات
    protected $fillable = [
        'patient_id', 'appointment_id', 'checkin_at', 'checkout_at', 'checked_by', 'method'
    ];

    protected $casts = [
        'checkin_at' => 'datetime',
        'checkout_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    // الموظف الذي سجل الحضور
    public function checkedBy()
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
