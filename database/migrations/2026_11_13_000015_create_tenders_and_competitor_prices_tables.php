<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tenders Table (ادارة المبيعات والمناقصات)
        Schema::create('tenders', function (Blueprint $table) {
            $table->id();
            $table->string('tender_number')->unique();
            $table->string('title');
            $table->foreignId('hospital_id')->nullable()->constrained('hospitals')->nullOnDelete();
            $table->string('entity_name'); // اسم الجهة أو المستشفى
            $table->date('announcement_date')->nullable(); // تاريخ الإعلان عن المناقصة
            $table->date('envelope_opening_date')->nullable(); // موعد فتح المظاريف
            $table->decimal('bid_bond_amount', 14, 2)->default(0.00); // قيمة التأمين الابتدائي
            $table->decimal('final_guarantee_amount', 14, 2)->default(0.00); // التأمين النهائي
            $table->decimal('booklet_cost', 10, 2)->default(0.00); // ثمن كراسة الشروط
            $table->string('status')->default('under_study'); // under_study, submitted, awarded, lost, cancelled, archived
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // 2. Competitor Prices Archive (أرشيف أسعار المنافسين للعام القادم)
        Schema::create('competitor_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained('tenders')->cascadeOnDelete();
            $table->string('competitor_name'); // اسم الشركة المنافسة
            $table->string('item_or_meal_name'); // اسم الصنف أو الوجبة
            $table->decimal('our_bid_price', 12, 2)->nullable(); // سعرنا المقدم
            $table->decimal('competitor_price', 12, 2); // السعر الذي نافس به أو فاز به المنافس
            $table->decimal('variance_percentage', 6, 2)->nullable(); // فرق النسبة المئوية
            $table->year('tender_year'); // سنة المناقصة كأرشيف
            $table->boolean('was_awarded')->default(false); // هل رسا عليه هذا البند؟
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Add contract status fields to hospitals
        Schema::table('hospitals', function (Blueprint $table) {
            $table->string('contract_status')->default('active')->after('is_active'); // active, expired, terminated, suspended
            $table->date('contract_start_date')->nullable()->after('contract_status');
            $table->date('contract_end_date')->nullable()->after('contract_start_date');
            $table->timestamp('terminated_at')->nullable()->after('contract_end_date');
            $table->text('termination_reason')->nullable()->after('terminated_at');
        });
    }

    public function down(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->dropColumn(['contract_status', 'contract_start_date', 'contract_end_date', 'terminated_at', 'termination_reason']);
        });
        Schema::dropIfExists('competitor_prices');
        Schema::dropIfExists('tenders');
    }
};
