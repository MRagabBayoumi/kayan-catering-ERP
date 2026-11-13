<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_orders', function (Blueprint $table) {
            $table->id();
            $table->string('dispatch_number')->unique();
            $table->foreignId('supply_order_id')->nullable()->constrained('supply_orders')->nullOnDelete();
            $table->foreignId('hospital_id')->constrained('hospitals');
            $table->foreignId('source_warehouse_id')->constrained('warehouses');
            $table->foreignId('transit_warehouse_id')->constrained('warehouses'); // Van / Driver Custody Warehouse
            $table->foreignId('driver_id')->constrained('users');
            $table->foreignId('storekeeper_id')->constrained('users');
            $table->foreignId('store_manager_id')->nullable()->constrained('users');
            $table->dateTime('dispatch_date');
            $table->string('status')->default('prepared'); // prepared, in_transit, delivered, settled, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('dispatch_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_order_id')->constrained('dispatch_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('inventory_batch_id')->nullable()->constrained('inventory_batches');
            $table->decimal('planned_qty', 12, 4); // Contract order qty
            $table->decimal('buffer_qty', 12, 4)->default(0.0000); // Safety buffer for extra hospital requests
            $table->decimal('total_dispatched_qty', 12, 4); // planned_qty + buffer_qty
            $table->decimal('unit_cost', 12, 4)->default(0.0000); // FIFO Cost Price
            $table->decimal('total_cost', 12, 4)->default(0.0000);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_order_items');
        Schema::dropIfExists('dispatch_orders');
    }
};
