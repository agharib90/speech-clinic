<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('package_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_package_id')->constrained('session_packages')->cascadeOnDelete();
            $table->foreignId('therapy_session_id')->constrained('therapy_sessions')->cascadeOnDelete();
            $table->dateTime('used_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_usage');
    }
};
