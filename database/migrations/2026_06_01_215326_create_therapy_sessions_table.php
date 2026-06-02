<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('therapy_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('therapy_program_id')->constrained('therapy_programs')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete(); // مرتبط بموعد (اختياري)
            $table->date('session_date');
            $table->integer('session_number')->default(1);
            $table->integer('duration_minutes')->default(30);
            $table->enum('status', ['مجدولة', 'مكتملة', 'ملغية'])->default('مجدولة');
            $table->text('notes')->nullable(); // ملاحظات عامة
            $table->text('goals_achieved')->nullable(); // الأهداف المحققة
            $table->text('next_session_plan')->nullable(); // خطة الجلسة القادمة
            $table->text('internal_notes')->nullable(); // ملاحظات داخلية سرية (لا تظهر لولي الأمر)
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('therapy_sessions');
    }
};
