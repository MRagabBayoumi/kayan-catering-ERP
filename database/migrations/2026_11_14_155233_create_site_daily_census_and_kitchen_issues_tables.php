<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. تشغيل وأعداد مدير الموقع اليومية (لحمة ولا فراخ، ونوع النشويات، والأعداد)
        Schema::create('daily_site_censuses', function (Blueprint $table) {
            $table->id();
            $table->date('census_date');
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->foreignId('site_manager_id')->constrained('users')->cascadeOnDelete();
            $table->string('protein_choice')->default('meat'); // meat, poultry
            $table->string('carb_choice')->default('rice_vermicelli'); // rice_vermicelli, plain_rice, pasta
            $table->string('veg_choice')->default('cooked_veg'); // cooked_veg, beans, cowpea
            $table->integer('total_patient_count')->default(0);
            $table->string('status')->default('submitted'); // submitted, verified
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['hospital_id', 'census_date']);
        });

        // 2. تفصيل الأعداد لكل جدول تعاقدي (نوبتجي 12 = 80، مقيم 30 = 40...)
        Schema::create('daily_site_census_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_site_census_id')->constrained('daily_site_censuses')->cascadeOnDelete();
            $table->foreignId('diet_schedule_id')->constrained('diet_schedules')->cascadeOnDelete();
            $table->integer('patient_count')->default(0);
            $table->timestamps();
        });

        // 3. الصرف الفعلي من المخزن الفرعي بالموقع إلى المطبخ
        Schema::create('site_kitchen_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->date('issue_date');
            $table->string('issue_number')->nullable();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity_issued', 10, 4); // الكمية المصروفة فعلياً بالكيلو أو العدد
            $table->string('unit')->default('kg');
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_kitchen_issues');
        Schema::dropIfExists('daily_site_census_items');
        Schema::dropIfExists('daily_site_censuses');
    }
};
