<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_clinical_evaluation_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id');
            $table->foreignId('clinical_evaluation_id')->nullable();
            $table->foreignId('assigned_to')->nullable();
            $table->foreignId('assigned_by')->nullable();
            $table->string('status', 20)->index();
            $table->dateTime('assigned_at');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique('clinical_evaluation_id', 'clinical_assignments_evaluation_unique');
            $table->index(['patient_id', 'status'], 'clinical_assignments_patient_status_idx');
            $table->foreign('patient_id', 'clinical_assignments_patient_fk')->references('id')->on('patients')->restrictOnDelete();
            $table->foreign('clinical_evaluation_id', 'clinical_assignments_evaluation_fk')->references('id')->on('patient_clinical_evaluations')->restrictOnDelete();
            $table->foreign('assigned_to', 'clinical_assignments_assignee_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('assigned_by', 'clinical_assignments_assigner_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_clinical_evaluation_assignments');
    }
};
