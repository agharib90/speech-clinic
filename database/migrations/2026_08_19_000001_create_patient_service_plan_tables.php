<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_service_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('draft')->index();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('patient_service_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_service_plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('position');
            $table->unsignedInteger('planned_quantity');
            $table->decimal('customer_unit_price', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('final_unit_price', 12, 2);
            $table->unsignedInteger('authorized_quantity')->default(0);
            $table->unsignedInteger('consumed_quantity')->default(0);
            $table->timestamps();

            $table->unique(['patient_service_plan_id', 'position'], 'service_plan_item_position_unique');
            $table->index(
                ['patient_service_plan_id', 'service_id'],
                'pspi_plan_service_idx'
            );
        });

        Schema::create('patient_service_plan_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_service_plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_payment_id')->unique()->constrained()->restrictOnDelete();
            $table->decimal('amount_snapshot', 12, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('patient_service_plan_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_service_plan_payment_id')
                ->constrained(indexName: 'pspa_payment_fk')
                ->restrictOnDelete();
            $table->foreignId('patient_service_plan_item_id')
                ->constrained(indexName: 'pspa_item_fk')
                ->restrictOnDelete();
            $table->decimal('allocated_amount', 12, 2);
            $table->unsignedInteger('authorized_quantity');
            $table->timestamps();

            $table->unique(
                ['patient_service_plan_payment_id', 'patient_service_plan_item_id'],
                'service_plan_payment_item_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_service_plan_allocations');
        Schema::dropIfExists('patient_service_plan_payments');
        Schema::dropIfExists('patient_service_plan_items');
        Schema::dropIfExists('patient_service_plans');
    }
};
