<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Meal Types (إدارة التكاليف: تصنيف الوجبات الطبية)
        Schema::create('meal_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name'); // إفطار عادي، غداء مريض سكر، غداء مريض كلى، عشاء طاقم تمريض، وجبة جراحة...
            $table->string('category')->default('patient'); // patient, staff, doctors, vip
            $table->text('dietary_notes')->nullable(); // ملاحظات الحمية الطبية
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Standard Recipes / Meal Portion BOM (المقنن الغذائي القياسي المعتمد للوجبة)
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('recipe_code')->unique();
            $table->string('name');
            $table->foreignId('meal_type_id')->constrained('meal_types')->cascadeOnDelete();
            $table->foreignId('hospital_id')->nullable()->constrained('hospitals')->nullOnDelete(); // مقنن خاص بمستشفى معينة أو عام
            $table->decimal('standard_cost_per_meal', 10, 2)->default(0.00); // التكلفة المعيارية التقديرية للوجبة
            $table->boolean('is_approved_by_costing')->default(true); // معتمد من إدارة التكاليف
            $table->boolean('is_approved_by_site_mgr')->default(true); // معتمد من مدير الموقع
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // 3. Recipe Ingredients / Portion Breakdown (الخامات والمقننات بالجرامات لكل وجبة)
        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products'); // كود الخامة
            $table->decimal('raw_quantity_per_meal', 10, 4); // الوزن الخام لكل مريض (مثلاً: 0.120 كجم لحم أو 120 جم)
            $table->decimal('cooked_yield_quantity', 10, 4)->nullable(); // الوزن بعد الطهي والاستواء
            $table->string('unit')->default('kg'); // kg, gm, pcs
            $table->decimal('standard_unit_cost', 10, 4)->default(0.00); // سعر التكلفة التقديري
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 4. Kitchen Consumption Tracking & Variance Analysis (سجل الاستهلاك الفعلي للمطبخ مقابل المقنن)
        Schema::create('consumption_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_number')->unique();
            $table->foreignId('hospital_id')->constrained('hospitals');
            $table->foreignId('recipe_id')->constrained('recipes');
            $table->date('consumption_date');
            $table->integer('actual_served_meals_count'); // عدد الوجبات المنفذة فعلياً (عدد المرضى)
            $table->foreignId('product_id')->constrained('products'); // الخامة المستهلكة
            $table->decimal('standard_expected_qty', 12, 4); // الاستهلاك المعياري = عدد الوجبات × المقنن
            $table->decimal('actual_consumed_qty', 12, 4); // الاستهلاك الفعلي المنصرف من المطبخ
            $table->decimal('variance_qty', 12, 4); // الفارق = الفعلي - المعياري (+ زيادة استهلاك وهدر / - توفير)
            $table->decimal('actual_fifo_cost', 12, 4); // تكلفة الصرف الفعلية المسحوبة من الباتشات
            $table->decimal('standard_cost', 12, 4); // التكلفة المعيارية المخططة
            $table->decimal('cost_variance', 12, 4); // انحراف التكلفة المالي
            $table->string('status')->default('normal'); // normal, over_consumption_alert, saved
            $table->text('discrepancy_justification')->nullable(); // تبرير مدير المطبخ/الموقع لأي انحراف
            $table->foreignId('verified_by_costing')->nullable()->constrained('users'); // مراجع ومعتمد من إدارة التكاليف
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumption_logs');
        Schema::dropIfExists('recipe_ingredients');
        Schema::dropIfExists('recipes');
        Schema::dropIfExists('meal_types');
    }
};
