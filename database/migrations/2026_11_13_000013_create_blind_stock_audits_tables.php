<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blind_stock_audits', function (Blueprint $table) {
            $table->id();
            $table->string('audit_number')->unique();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('storekeeper_id')->constrained('users');
            $table->foreignId('accountant_id')->nullable()->constrained('users');
            $table->dateTime('audited_at');
            $table->string('status')->default('in_progress'); // in_progress, submitted, reviewed, settled
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('blind_stock_audit_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blind_stock_audit_id')->constrained('blind_stock_audits')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('batch_id')->nullable()->constrained('inventory_batches');
            $table->decimal('physical_count', 12, 4); // Counted blindly by storekeeper
            $table->decimal('system_quantity', 12, 4)->default(0.0000); // System book quantity (hidden from storekeeper)
            $table->decimal('variance', 12, 4)->default(0.0000); // physical_count - system_quantity
            $table->decimal('unit_cost', 12, 4)->default(0.0000);
            $table->decimal('variance_cost', 12, 4)->default(0.0000);
            $table->text('discrepancy_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blind_stock_audit_items');
        Schema::dropIfExists('blind_stock_audits');
    }
};
