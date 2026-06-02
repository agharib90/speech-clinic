<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('therapist_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('therapist_id')->constrained('therapists')->cascadeOnDelete();
            $table->foreignId('therapy_session_id')->constrained('therapy_sessions')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->enum('type', ['commission', 'session_fee'])->default('session_fee');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('therapist_earnings'); }
};
