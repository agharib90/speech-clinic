<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('quotation_number')->unique();
            $table->date('issue_date');
            $table->date('valid_until')->nullable();
            $table->enum('status', ['مسودة', 'مرسل', 'مقبول', 'مرفوض'])->default('مسودة');
            $table->decimal('total', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('quotations'); }
};
