<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('session_type_id')->nullable()->change();
            $table->foreignId('patient_service_plan_item_id')
                ->nullable()
                ->after('session_type_id')
                ->constrained(indexName: 'appt_plan_item_fk')
                ->restrictOnDelete();
            $table->unsignedTinyInteger('confirmation_deposit_percentage_snapshot')->nullable();
            $table->decimal('confirmation_deposit_amount_snapshot', 12, 2)->nullable();
            $table->timestamp('financially_confirmed_at')->nullable();

            $table->index(
                ['patient_service_plan_item_id', 'status', 'financially_confirmed_at'],
                'appt_plan_booking_idx'
            );
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('appointment_confirmation_deposit_percentage')
                ->default(50)
                ->after('default_therapist_commission_rate');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('appt_plan_booking_idx');
            $table->dropForeign('appt_plan_item_fk');
            $table->dropColumn([
                'patient_service_plan_item_id',
                'confirmation_deposit_percentage_snapshot',
                'confirmation_deposit_amount_snapshot',
                'financially_confirmed_at',
            ]);
        });

        if (! DB::table('appointments')->whereNull('session_type_id')->exists()) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->foreignId('session_type_id')->nullable(false)->change();
            });
        }

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('appointment_confirmation_deposit_percentage');
        });
    }
};
