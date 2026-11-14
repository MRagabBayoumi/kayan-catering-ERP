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
        Schema::table('dispatch_orders', function (Blueprint $table) {
            $table->foreignId('recipe_id')->nullable()->after('hospital_id')->constrained('recipes')->nullOnDelete();
            $table->unsignedInteger('planned_meals_count')->nullable()->after('recipe_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dispatch_orders', function (Blueprint $table) {
            $table->dropForeign(['recipe_id']);
            $table->dropColumn(['recipe_id', 'planned_meals_count']);
        });
    }
};
