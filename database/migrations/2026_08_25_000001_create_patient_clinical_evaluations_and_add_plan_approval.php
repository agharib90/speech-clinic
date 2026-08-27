<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_clinical_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('draft')->index();
            $table->text('clinical_summary')->nullable();
            $table->foreignId('evaluated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status'], 'clinical_evaluations_patient_status_idx');
        });

        Schema::table('patient_service_plans', function (Blueprint $table) {
            $table->foreignId('clinical_evaluation_id')
                ->nullable()
                ->after('patient_id')
                ->constrained('patient_clinical_evaluations')
                ->nullOnDelete();
            $table->foreignId('clinical_approved_by')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();
            $table->dateTime('clinical_approved_at')->nullable()->after('clinical_approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('patient_service_plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('clinical_evaluation_id');
            $table->dropConstrainedForeignId('clinical_approved_by');
            $table->dropColumn('clinical_approved_at');
        });

        Schema::dropIfExists('patient_clinical_evaluations');
    }
};
