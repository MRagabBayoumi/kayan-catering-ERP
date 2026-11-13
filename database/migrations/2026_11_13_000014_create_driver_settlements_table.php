<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_number')->unique();
            $table->foreignId('dispatch_order_id')->constrained('dispatch_orders');
            $table->foreignId('delivery_receipt_id')->nullable()->constrained('delivery_receipts');
            $table->foreignId('driver_id')->constrained('users');
            $table->foreignId('accountant_id')->constrained('users');
            $table->dateTime('settled_at');
            $table->decimal('total_dispatched_qty', 12, 4);
            $table->decimal('total_delivered_qty', 12, 4);
            $table->decimal('total_returned_qty', 12, 4);
            $table->decimal('total_extra_qty', 12, 4)->default(0.0000);
            $table->decimal('total_rejected_qty', 12, 4)->default(0.0000);
            $table->decimal('total_cogs_cost', 14, 4)->default(0.0000); // Cost of Goods Sold to Hospital (FIFO)
            $table->string('erp_journal_voucher')->nullable(); // Reference voucher to push to ERP GL
            $table->string('status')->default('settled'); // settled, disputed
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_settlements');
    }
};
