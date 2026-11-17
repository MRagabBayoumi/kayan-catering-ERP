<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Supplier Product Mapping (تكويد أصناف الموردين وأسعارهم التعاقدية)
        Schema::create('supplier_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('supplier_item_code', 50)->nullable();
            $table->string('supplier_item_name')->nullable();
            $table->decimal('supplier_price', 12, 4)->default(0.0000);
            $table->string('currency', 10)->default('EGP');
            $table->date('effective_date')->nullable();
            $table->boolean('is_preferred')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['supplier_id', 'product_id']);
        });

        // 2. Purchase Requests (طلبات الشراء والاحتياج التمويني)
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('pr_number', 50)->unique();
            $table->date('request_date');
            $table->date('required_by_date')->nullable();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('status', 30)->default('draft'); // draft, pending_approval, approved, rejected, po_created
            $table->text('reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('purchase_requests')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity_requested', 12, 4);
            $table->string('unit', 30)->default('kg');
            $table->decimal('estimated_unit_cost', 12, 4)->default(0.0000);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. Purchase Orders (أوامر الشراء الرسمية المعتمدة للموردين)
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 50)->unique();
            $table->foreignId('purchase_request_id')->nullable()->constrained('purchase_requests')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->decimal('subtotal', 14, 2)->default(0.00);
            $table->decimal('tax_amount', 14, 2)->default(0.00);
            $table->decimal('total_amount', 14, 2)->default(0.00);
            $table->string('status', 30)->default('draft'); // draft, pending_approval, approved, sent_to_supplier, partially_received, fully_received, cancelled
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity_ordered', 12, 4);
            $table->decimal('quantity_received', 12, 4)->default(0.0000);
            $table->string('unit', 30)->default('kg');
            $table->decimal('unit_price', 12, 4);
            $table->decimal('total_price', 14, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 4. Goods Receipt Notes (إذن فحص واستلام توريد مخزني GRN)
        Schema::create('goods_receipt_notes', function (Blueprint $table) {
            $table->id();
            $table->string('grn_number', 50)->unique();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->date('receipt_date');
            $table->string('supplier_invoice_reference')->nullable();
            $table->string('delivery_note_reference')->nullable();
            $table->string('status', 30)->default('stored'); // draft, inspected, stored, rejected
            $table->foreignId('received_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('inspection_notes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('goods_receipt_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_note_id')->constrained('goods_receipt_notes')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity_delivered', 12, 4);
            $table->decimal('quantity_accepted', 12, 4);
            $table->decimal('quantity_rejected', 12, 4)->default(0.0000);
            $table->text('rejection_reason')->nullable();
            $table->string('unit', 30)->default('kg');
            $table->decimal('unit_cost', 12, 4);
            $table->decimal('total_cost', 14, 2);
            $table->string('batch_number', 50)->nullable();
            $table->date('production_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('storage_temperature', 5, 2)->nullable();
            $table->foreignId('inventory_batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_note_items');
        Schema::dropIfExists('goods_receipt_notes');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('purchase_request_items');
        Schema::dropIfExists('purchase_requests');
        Schema::dropIfExists('supplier_products');
    }
};
