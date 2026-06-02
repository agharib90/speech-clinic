<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payroll_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('therapist_id')->constrained('therapists')->cascadeOnDelete();
            $table->enum('type', ['إضافة', 'خصم']);
            $table->decimal('amount', 10, 2);
            $table->string('description');
            $table->integer('month');
            $table->integer('year');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('payroll_records'); }
};
