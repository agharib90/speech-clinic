<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('patient_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments'); // ممكن يكون حضور حر بدون موعد
            $table->dateTime('checkin_at');
            $table->dateTime('checkout_at')->nullable();
            $table->foreignId('checked_by')->constrained('users'); // موظف الاستقبال
            $table->enum('method', ['barcode', 'manual'])->default('barcode');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_checkins');
    }
};
