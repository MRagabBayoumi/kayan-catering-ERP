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
        // 1. Suppliers Table
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('contact_person')->nullable();
            $table->decimal('current_balance', 12, 2)->default(0.00);
            $table->decimal('credit_limit', 12, 2)->default(0.00);
            $table->integer('payment_terms_days')->default(30);
            $table->timestamps();
        });

        // 2. Supplier Payment Schedules
        Schema::create('supplier_payment_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_reference')->nullable();
            $table->date('due_date');
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending'); // pending, paid, overdue
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. Treasuries & Site Petty Cash (الخزن والبنوك وعهد المواقع)
        Schema::create('treasuries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('cashbox'); // cashbox, bank, site_custody
            $table->foreignId('hospital_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('balance', 12, 2)->default(0.00);
            $table->timestamps();
        });

        // 4. Financial Vouchers (أذون صرف وسندات قبض وإشعارات البنوك)
        Schema::create('financial_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_number')->unique();
            $table->string('type'); // payment_voucher, receipt_voucher, bank_transfer
            $table->foreignId('treasury_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('hospital_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('purpose');
            $table->string('payment_method')->default('cash'); // cash, bank_transfer, check
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->date('voucher_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 5. Daily Cash Closings (اليومية والتقفيل لصاحب العمل)
        Schema::create('daily_cash_closings', function (Blueprint $table) {
            $table->id();
            $table->date('closing_date')->unique();
            $table->decimal('opening_balance', 12, 2)->default(0.00);
            $table->decimal('total_inflow', 12, 2)->default(0.00);
            $table->decimal('total_outflow', 12, 2)->default(0.00);
            $table->decimal('closing_balance', 12, 2)->default(0.00);
            $table->decimal('site_custody_spent', 12, 2)->default(0.00);
            $table->decimal('supplier_payments', 12, 2)->default(0.00);
            $table->string('status')->default('closed'); // draft, closed, approved_by_owner
            $table->text('notes')->nullable();
            $table->foreignId('closed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_cash_closings');
        Schema::dropIfExists('financial_vouchers');
        Schema::dropIfExists('treasuries');
        Schema::dropIfExists('supplier_payment_schedules');
        Schema::dropIfExists('suppliers');
    }
};
