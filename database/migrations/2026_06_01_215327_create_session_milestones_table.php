<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('session_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('therapy_program_id')->constrained('therapy_programs')->cascadeOnDelete();
            $table->dateTime('recorded_at'); // تاريخ ووقت التسجيل
            $table->string('skill_area'); // مجال المهارة (نطق/لغة/صوت/تواصل)
            $table->decimal('baseline_score', 5, 2)->default(0); // درجة الخط القاعدي
            $table->decimal('current_score', 5, 2)->default(0); // الدرجة الحالية
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_milestones');
    }
};
