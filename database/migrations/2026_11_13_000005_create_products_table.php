<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('barcode')->nullable()->index();
            $table->decimal('cost_price', 12, 4)->default(0.0000);
            $table->string('unit')->default('kg'); // kg, gm, liter, pcs, carton, bag
            $table->string('storage_type')->default('dry'); // dry, chilled, frozen
            $table->integer('shelf_life_days')->nullable(); // Expiry shelf life in days
            $table->decimal('acceptable_waste_pct', 5, 2)->default(0.00); // Standard yield/waste %
            $table->decimal('min_stock_alert', 12, 4)->default(0.0000);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
