<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('therapists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // مربوط بحساب الدخول
            $table->string('name');
            $table->string('specialization')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('license_number')->nullable();
            $table->date('hire_date')->nullable();
            $table->enum('salary_type', ['daily', 'monthly', 'commission'])->default('monthly');
            $table->decimal('daily_salary', 10, 2)->default(0);
            $table->decimal('monthly_salary', 10, 2)->default(0);
            $table->decimal('commission_rate', 5, 2)->default(0); // نسبة كـ % مثلاً 15.50
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('therapists'); }
};
