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
        // 1. Recipe Versions (إصدارات المقننات القياسية التاريخية)
        Schema::create('recipe_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->integer('version_number')->default(1);
            $table->string('version_name')->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->decimal('total_standard_cost', 10, 4)->default(0.0000);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_locked')->default(false); // Locked once consumed operationally
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('change_reason')->nullable();
            $table->timestamps();

            $table->unique(['recipe_id', 'version_number']);
        });

        Schema::create('recipe_version_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_version_id')->constrained('recipe_versions')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('raw_quantity_per_meal', 12, 4);
            $table->decimal('cooked_yield_quantity', 12, 4);
            $table->decimal('shrinkage_cooking_loss_pct', 5, 2)->default(0.00);
            $table->decimal('butchery_yield_pct', 5, 2)->default(100.00);
            $table->string('unit', 30)->default('kg');
            $table->decimal('standard_unit_cost', 12, 4)->default(0.0000);
            $table->decimal('total_ingredient_cost', 12, 4)->default(0.0000);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. Waste Records (محاضر الهدر والتلف والتشفية مصنفة محاسبياً)
        Schema::create('waste_records', function (Blueprint $table) {
            $table->id();
            $table->string('waste_number', 50)->unique();
            $table->date('waste_date');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('hospital_id')->nullable()->constrained('hospitals')->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('inventory_batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->decimal('quantity', 12, 4);
            $table->string('unit', 30)->default('kg');
            $table->decimal('unit_cost', 12, 4)->default(0.0000);
            $table->decimal('total_cost_value', 14, 2)->default(0.00);
            $table->string('waste_category', 40); // normal_cooking_loss, butchery_spoilage, expired_batch, transit_damage, kitchen_burn, unexplained_shortage
            $table->boolean('is_abnormal')->default(false);
            $table->text('reason');
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('pending'); // pending, approved, rejected
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. Excel Import Sessions (منطقة الحجز والفحص لشيتات الإكسل قبل التفعيل)
        Schema::create('excel_import_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_uuid', 64)->unique();
            $table->string('file_name');
            $table->string('import_type', 50); // ration_bom, census, supplier_price_list, initial_stock
            $table->integer('total_rows')->default(0);
            $table->integer('valid_rows')->default(0);
            $table->integer('invalid_rows')->default(0);
            $table->string('status', 30)->default('staged'); // staged, validated, approved, applied, rejected
            $table->json('validation_errors')->nullable();
            $table->json('staged_data')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('excel_import_sessions');
        Schema::dropIfExists('waste_records');
        Schema::dropIfExists('recipe_version_ingredients');
        Schema::dropIfExists('recipe_versions');
    }
};
