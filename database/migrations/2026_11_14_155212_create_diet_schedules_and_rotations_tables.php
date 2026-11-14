<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. جداول الفئات التعاقدية للوجبات (نوبتجي 12، مقيم 30، عاملين 24، أطفال...)
        Schema::create('diet_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('meal_type')->default('غذاء'); // فطار، غذاء، عشاء
            $table->foreignId('hospital_id')->nullable()->constrained('hospitals')->nullOnDelete();
            $table->decimal('daily_bread_count', 8, 2)->default(1.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. مصفوفة المقننات وتكرار الخامات الأسبوعية (7/3 لحمة، 7/4 فراخ، أرز، مكرونة...)
        Schema::create('diet_schedule_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diet_schedule_id')->constrained('diet_schedules')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('category_type'); // protein_meat, protein_poultry, carb_rice_vermicelli, carb_plain_rice, carb_pasta, veg_cooked, veg_dry_legume, daily_fixed, packaging
            $table->decimal('portion_quantity', 10, 4); // الوزن الفردي بالجرامات/الكيلو (مثلاً 0.150 أو 0.275)
            $table->string('unit')->default('kg');
            $table->decimal('weekly_frequency', 4, 2)->default(7.00); // 3.00 للحمة، 4.00 للفراخ، 2.00 للأرز بالشعرية...
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diet_schedule_items');
        Schema::dropIfExists('diet_schedules');
    }
};
