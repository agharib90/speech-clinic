<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'clinic_name',
        'currency',
        'logo_path',
        'logo_original_name',
        'address',
        'official_phone',
        'official_email',
        'tax_number',
        'commercial_registration',
        'working_hours',
        'currency_code',
        'currency_symbol',
        'default_therapist_commission_rate',
        'appointment_reminders_enabled',
        'appointment_reminder_hours',
        'invoice_notifications_enabled',
        'notification_sender_number',
        'notification_sender_name',
        'appointment_reminder_template',
        'invoice_notification_template',
        'default_locale',
        'trash_retention_days',
    ];

    protected $casts = [
        'working_hours' => 'array',
        'default_therapist_commission_rate' => 'decimal:2',
        'appointment_reminders_enabled' => 'boolean',
        'appointment_reminder_hours' => 'integer',
        'invoice_notifications_enabled' => 'boolean',
        'trash_retention_days' => 'integer',
    ];

    public static function defaults(): array
    {
        return [
            'clinic_name' => 'عيادة التخاطب',
            'currency' => 'ر.س',
            'currency_code' => 'EGP',
            'currency_symbol' => 'ج.م',
            'default_therapist_commission_rate' => 0,
            'appointment_reminders_enabled' => false,
            'appointment_reminder_hours' => 24,
            'invoice_notifications_enabled' => false,
            'default_locale' => 'ar',
            'trash_retention_days' => 30,
            'working_hours' => self::defaultWorkingHours(),
            'appointment_reminder_template' => 'تذكير: موعد {patient_name} في {clinic_name} يوم {appointment_date} الساعة {appointment_time}.',
            'invoice_notification_template' => 'فاتورة {invoice_number} من {clinic_name}: الإجمالي {invoice_total} والمتبقي {invoice_due}.',
        ];
    }

    public static function defaultWorkingHours(): array
    {
        $days = [
            'saturday' => 'السبت',
            'sunday' => 'الأحد',
            'monday' => 'الاثنين',
            'tuesday' => 'الثلاثاء',
            'wednesday' => 'الأربعاء',
            'thursday' => 'الخميس',
            'friday' => 'الجمعة',
        ];

        return collect($days)->mapWithKeys(fn ($label, $key) => [
            $key => [
                'label' => $label,
                'is_open' => $key !== 'friday',
                'opens_at' => $key !== 'friday' ? '09:00' : null,
                'closes_at' => $key !== 'friday' ? '17:00' : null,
            ],
        ])->all();
    }
}
