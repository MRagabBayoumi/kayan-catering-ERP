<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('warehouses', 'hospital_id')) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->foreignId('hospital_id')->nullable()->after('driver_id')->constrained('hospitals')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('warehouses', 'hospital_id')) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->dropForeign(['hospital_id']);
                $table->dropColumn('hospital_id');
            });
        }
    }
};
