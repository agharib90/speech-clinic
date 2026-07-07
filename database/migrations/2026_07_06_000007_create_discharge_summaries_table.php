<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('discharge_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('therapy_program_id')->unique()->constrained('therapy_programs')->cascadeOnDelete();
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('discharge_date');
            $table->string('reason')->nullable();
            $table->text('initial_status')->nullable();
            $table->text('final_status')->nullable();
            $table->text('goals_outcome')->nullable();
            $table->text('recommendations')->nullable();
            $table->text('follow_up_plan')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discharge_summaries');
    }
};
