<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('therapy_programs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('therapist_id')->constrained('users'); // الأخصائي المسؤول
            $table->string('disorder_type')->nullable(); // نوع الاضطراب
            $table->text('goals')->nullable(); // الأهداف
            $table->enum('status', ['جاري', 'مكتمل', 'متوقف'])->default('جاري');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('sessions_per_week')->default(1);
            $table->decimal('session_price', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('therapy_programs');
    }
};
