<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Feasibility Studies (دراسات الجدوى التقديرية للمناقصات)
        Schema::create('feasibility_studies', function (Blueprint $table) {
            $table->id();
            $table->string('study_number', 50)->unique();
            $table->foreignId('tender_id')->nullable()->constrained('tenders')->nullOnDelete();
            $table->string('hospital_name');
            $table->integer('planned_duration_months')->default(12);
            $table->integer('expected_total_meals')->default(0);
            $table->decimal('target_selling_price_per_meal', 10, 2)->default(0.00);
            $table->decimal('expected_revenue', 14, 2)->default(0.00);
            $table->decimal('estimated_food_cost', 14, 2)->default(0.00);
            $table->decimal('estimated_labor_cost', 14, 2)->default(0.00);
            $table->decimal('estimated_transport_cost', 14, 2)->default(0.00);
            $table->decimal('estimated_consumables_cost', 14, 2)->default(0.00);
            $table->decimal('estimated_operating_cost', 14, 2)->default(0.00);
            $table->decimal('estimated_total_cost', 14, 2)->default(0.00);
            $table->decimal('expected_gross_margin', 14, 2)->default(0.00);
            $table->decimal('expected_net_margin_percentage', 6, 2)->default(0.00);
            $table->integer('break_even_meals')->default(0);
            $table->string('status', 30)->default('draft'); // draft, submitted, approved, rejected
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('feasibility_study_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feasibility_study_id')->constrained('feasibility_studies')->cascadeOnDelete();
            $table->string('cost_category', 30); // food, labor, transport, consumables, overhead
            $table->string('item_name');
            $table->decimal('quantity', 12, 4)->default(1.0000);
            $table->string('unit', 30)->default('unit');
            $table->decimal('unit_cost', 12, 4)->default(0.0000);
            $table->decimal('total_cost', 14, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. Contracts (عقود المستشفيات المستقلة ذات الإصدارات والتجديدات)
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number', 50)->unique();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->foreignId('tender_id')->nullable()->constrained('tenders')->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->string('title');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 30)->default('draft'); // draft, under_review, approved, active, suspended, expired, terminated
            $table->decimal('contract_value', 14, 2)->default(0.00);
            $table->string('billing_cycle', 30)->default('monthly'); // daily, weekly, monthly
            $table->integer('payment_terms_days')->default(30);
            $table->text('penalty_terms')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('contract_meal_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('meal_type_id')->nullable()->constrained('meal_types')->nullOnDelete();
            $table->string('diet_category', 50)->default('standard'); // standard, diabetic, renal, cardiac, staff, companion
            $table->decimal('price_per_meal', 10, 2);
            $table->integer('minimum_daily_quantity')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_meal_prices');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('feasibility_study_items');
        Schema::dropIfExists('feasibility_studies');
    }
};
