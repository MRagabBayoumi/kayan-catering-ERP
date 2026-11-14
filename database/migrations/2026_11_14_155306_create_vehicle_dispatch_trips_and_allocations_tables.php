<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. حركة سيارات المخزن ومصاريف النقل والوقود
        Schema::create('vehicle_dispatch_trips', function (Blueprint $table) {
            $table->id();
            $table->string('trip_number')->unique();
            $table->string('vehicle_plate'); // رقم لوحة السيارة
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('trip_date');
            $table->decimal('fuel_expense', 10, 2)->default(0.00); // تفويل بنزين / سولار
            $table->decimal('toll_and_other_expense', 10, 2)->default(0.00); // كارتة، بوابات، زيت...
            $table->decimal('total_trip_expense', 10, 2)->default(0.00); // إجمالي المصروف
            $table->foreignId('financial_voucher_id')->nullable()->constrained('financial_vouchers')->nullOnDelete(); // الربط بسند الصرف المالي
            $table->foreignId('created_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. توزيع تكلفة النقل على المستشفيات والمواقع المستفيدة في الرحلة
        Schema::create('vehicle_trip_destinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_dispatch_trip_id')->constrained('vehicle_dispatch_trips')->cascadeOnDelete();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->decimal('allocated_amount', 10, 2); // نصيب الموقع المحمل عليه في مركز التكلفة
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_trip_destinations');
        Schema::dropIfExists('vehicle_dispatch_trips');
    }
};
