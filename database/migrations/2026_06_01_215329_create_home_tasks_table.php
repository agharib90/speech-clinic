<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('home_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('therapy_session_id')->constrained('therapy_sessions')->cascadeOnDelete();
            $table->text('description'); // شرح الواجب
            $table->date('due_date')->nullable(); // موعد التسليم
            $table->boolean('is_completed')->default(false); // هل اكتمل؟
            $table->text('parent_feedback')->nullable(); // ملاحظات ولي الأمر
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_tasks');
    }
};
