<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->foreignId('dispatch_order_id')->constrained('dispatch_orders');
            $table->foreignId('hospital_id')->constrained('hospitals');
            $table->foreignId('driver_id')->constrained('users');
            $table->string('receiving_officer_name')->nullable();
            $table->dateTime('received_at');
            $table->string('status')->default('pending_verification'); // pending_verification, verified, settled
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_receipt_id')->constrained('delivery_receipts')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('dispatched_qty', 12, 4);
            $table->decimal('actual_delivered_qty', 12, 4);
            $table->decimal('extra_requested_qty', 12, 4)->default(0.0000); // Hospital requested extra
            $table->decimal('returned_qty', 12, 4)->default(0.0000); // Remaining with driver to return
            $table->decimal('rejected_qty', 12, 4)->default(0.0000); // Quality rejected at hospital gate
            $table->string('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_receipt_items');
        Schema::dropIfExists('delivery_receipts');
    }
};
