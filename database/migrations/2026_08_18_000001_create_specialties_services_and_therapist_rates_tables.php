<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specialties', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specialty_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('default_duration_minutes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['specialty_id', 'name']);
        });

        Schema::create('specialty_therapist', function (Blueprint $table) {
            $table->foreignId('therapist_id')->constrained()->restrictOnDelete();
            $table->foreignId('specialty_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->primary(['therapist_id', 'specialty_id']);
        });

        Schema::create('service_therapist', function (Blueprint $table) {
            $table->foreignId('therapist_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->primary(['therapist_id', 'service_id']);
        });

        Schema::create('therapist_service_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('therapist_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['therapist_id', 'service_id', 'effective_from'], 'therapist_service_rate_start_unique');
            $table->index(['therapist_id', 'service_id', 'effective_from', 'effective_to'], 'therapist_service_rate_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('therapist_service_rates');
        Schema::dropIfExists('service_therapist');
        Schema::dropIfExists('specialty_therapist');
        Schema::dropIfExists('services');
        Schema::dropIfExists('specialties');
    }
};
