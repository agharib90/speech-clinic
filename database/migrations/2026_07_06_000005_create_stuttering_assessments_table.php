<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stuttering_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('therapy_program_id')->constrained('therapy_programs')->cascadeOnDelete();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('assessed_at');
            $table->string('sample_context')->nullable();
            $table->unsignedInteger('syllables_count')->default(0);
            $table->unsignedInteger('stuttered_syllables_count')->default(0);
            $table->decimal('stuttering_percentage', 5, 2)->default(0);
            $table->unsignedTinyInteger('frequency_score')->default(0);
            $table->unsignedTinyInteger('duration_score')->default(0);
            $table->unsignedTinyInteger('physical_concomitants_score')->default(0);
            $table->unsignedSmallInteger('total_score')->default(0);
            $table->string('severity')->default('غير محدد');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['therapy_program_id', 'assessed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stuttering_assessments');
    }
};
