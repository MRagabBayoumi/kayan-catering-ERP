<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('role')->default('admin')->after('password'); // admin, costing_manager, store_manager, storekeeper, driver, accountant
            $table->string('phone')->nullable()->after('role');
            $table->unsignedBigInteger('assigned_warehouse_id')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('assigned_warehouse_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'role', 'phone', 'assigned_warehouse_id', 'is_active']);
        });
    }
};
