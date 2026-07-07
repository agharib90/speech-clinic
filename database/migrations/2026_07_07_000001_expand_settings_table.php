<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('currency');
            $table->string('logo_original_name')->nullable()->after('logo_path');
            $table->string('address')->nullable()->after('logo_original_name');
            $table->string('official_phone')->nullable()->after('address');
            $table->string('official_email')->nullable()->after('official_phone');
            $table->string('tax_number')->nullable()->after('official_email');
            $table->string('commercial_registration')->nullable()->after('tax_number');
            $table->json('working_hours')->nullable()->after('commercial_registration');
            $table->string('currency_code', 3)->default('EGP')->after('working_hours');
            $table->string('currency_symbol', 10)->default('ج.م')->after('currency_code');
            $table->decimal('default_therapist_commission_rate', 5, 2)->default(0)->after('currency_symbol');
            $table->boolean('appointment_reminders_enabled')->default(false)->after('default_therapist_commission_rate');
            $table->unsignedSmallInteger('appointment_reminder_hours')->default(24)->after('appointment_reminders_enabled');
            $table->boolean('invoice_notifications_enabled')->default(false)->after('appointment_reminder_hours');
            $table->string('notification_sender_number')->nullable()->after('invoice_notifications_enabled');
            $table->string('notification_sender_name')->nullable()->after('notification_sender_number');
            $table->text('appointment_reminder_template')->nullable()->after('notification_sender_name');
            $table->text('invoice_notification_template')->nullable()->after('appointment_reminder_template');
            $table->string('default_locale', 10)->default('ar')->after('invoice_notification_template');
            $table->unsignedSmallInteger('trash_retention_days')->default(30)->after('default_locale');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
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
            ]);
        });
    }
};
