<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('session_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('name'); // اسم الباقة (مثال: باقة 8 جلسات)
            $table->integer('total_sessions');
            $table->integer('used_sessions')->default(0);
            $table->decimal('price_paid', 10, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->enum('status', ['نشط', 'منتهي', 'مستنفد'])->default('نشط');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_packages');
    }
};
