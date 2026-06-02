<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('program_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('therapy_program_id')->constrained('therapy_programs')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->enum('attachment_type', ['مرفق_عام', 'تسجيل_قبل', 'تسجيل_بعد'])->default('مرفق_عام');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('program_attachments'); }
};
