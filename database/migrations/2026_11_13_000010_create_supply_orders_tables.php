<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supply_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->date('order_date');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('frequency')->default('daily'); // daily, weekly, monthly, on_demand
            $table->string('status')->default('draft'); // draft, approved, in_progress, completed, cancelled
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('supply_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_order_id')->constrained('supply_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->string('storage_type')->default('dry'); // dry, chilled, frozen
            $table->decimal('planned_quantity', 12, 4);
            $table->decimal('dispatched_quantity', 12, 4)->default(0.0000);
            $table->decimal('delivered_quantity', 12, 4)->default(0.0000);
            $table->string('unit')->default('kg');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_order_items');
        Schema::dropIfExists('supply_orders');
    }
};
