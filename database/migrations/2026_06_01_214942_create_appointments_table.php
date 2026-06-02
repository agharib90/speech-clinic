<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('therapist_id')->constrained('users'); // الأخصائي هو User
            $table->foreignId('session_type_id')->constrained('session_types');
            $table->dateTime('scheduled_at');
            $table->dateTime('end_at');
            $table->enum('status', ['مجدول', 'مكتمل', 'غياب', 'ملغى'])->default('مجدول');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
