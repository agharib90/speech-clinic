<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('therapist_work_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('therapist_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->timestamps();

            $table->unique(
                ['therapist_id', 'weekday', 'starts_at', 'ends_at'],
                'therapist_period_unique'
            );
            $table->index(
                ['therapist_id', 'weekday', 'starts_at'],
                'therapist_period_lookup'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('therapist_work_periods');
    }
};
