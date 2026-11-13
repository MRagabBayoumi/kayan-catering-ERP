<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->string('movement_number')->unique();
            $table->string('type'); // in, out, transfer, adjustment, waste
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('from_warehouse_id')->nullable()->constrained('warehouses');
            $table->foreignId('to_warehouse_id')->nullable()->constrained('warehouses');
            $table->foreignId('batch_id')->nullable()->constrained('inventory_batches');
            $table->decimal('quantity', 12, 4);
            $table->decimal('unit_cost', 12, 4)->default(0.0000);
            $table->decimal('running_balance', 12, 4)->nullable();
            $table->string('reference_type')->nullable(); // SupplyOrder, DispatchOrder, DeliveryReceipt, BlindAudit
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
