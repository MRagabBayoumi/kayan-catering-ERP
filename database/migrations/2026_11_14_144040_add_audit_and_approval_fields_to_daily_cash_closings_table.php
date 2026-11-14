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
        Schema::table('daily_cash_closings', function (Blueprint $table) {
            $table->decimal('actual_cash_count', 12, 2)->default(0.00)->after('closing_balance');
            $table->decimal('difference', 12, 2)->default(0.00)->after('actual_cash_count');
            $table->foreignId('approved_by')->nullable()->after('closed_by')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_cash_closings', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'actual_cash_count',
                'difference',
                'approved_by',
                'approved_at',
                'rejection_reason',
            ]);
        });
    }
};
