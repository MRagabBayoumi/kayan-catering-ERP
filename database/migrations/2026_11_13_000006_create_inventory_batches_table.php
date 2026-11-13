<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number')->index();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->decimal('initial_quantity', 12, 4);
            $table->decimal('remaining_quantity', 12, 4);
            $table->decimal('cost_price', 12, 4);
            $table->date('production_date')->nullable();
            $table->date('expiry_date')->nullable()->index();
            $table->timestamp('received_at')->nullable();
            $table->boolean('is_exhausted')->default(false)->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'is_exhausted', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_batches');
    }
};
