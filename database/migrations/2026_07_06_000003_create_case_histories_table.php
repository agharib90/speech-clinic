<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('case_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->unique()->constrained('patients')->cascadeOnDelete();
            $table->foreignId('taken_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('taken_at')->nullable();
            $table->text('main_concerns')->nullable();
            $table->text('prenatal_history')->nullable();
            $table->text('birth_history')->nullable();
            $table->text('developmental_milestones')->nullable();
            $table->text('medical_history')->nullable();
            $table->text('hearing_vision_notes')->nullable();
            $table->text('family_history')->nullable();
            $table->text('language_environment')->nullable();
            $table->text('previous_interventions')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_histories');
    }
};
