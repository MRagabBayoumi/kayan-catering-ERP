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
        // 1. Chart of Accounts (دليل الحسابات الشجري)
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->foreignId('parent_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->integer('level')->default(1);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. Cost Centers (شجرة مراكز التكلفة)
        Schema::create('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('type')->default('hospital'); // hospital, vehicle, central_warehouse, admin, other
            $table->foreignId('hospital_id')->nullable()->constrained('hospitals')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. Link journal_entry_lines to chart_of_accounts and cost_centers
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->foreignId('chart_of_account_id')->nullable()->after('journal_entry_id')->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->after('chart_of_account_id')->constrained('cost_centers')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cost_center_id');
            $table->dropConstrainedForeignId('chart_of_account_id');
        });

        Schema::dropIfExists('cost_centers');
        Schema::dropIfExists('chart_of_accounts');
    }
};
