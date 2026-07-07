<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('articulation_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('therapy_program_id')->constrained('therapy_programs')->cascadeOnDelete();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('assessed_at');
            $table->string('title')->default('اختبار النطق العربي');
            $table->json('responses');
            $table->unsignedSmallInteger('total_items')->default(0);
            $table->unsignedSmallInteger('correct_count')->default(0);
            $table->unsignedSmallInteger('substitution_count')->default(0);
            $table->unsignedSmallInteger('omission_count')->default(0);
            $table->unsignedSmallInteger('distortion_count')->default(0);
            $table->decimal('accuracy_percent', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['therapy_program_id', 'assessed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articulation_assessments');
    }
};
