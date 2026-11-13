<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospitals', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('contract_number')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->integer('daily_patient_capacity')->nullable();
            $table->decimal('default_buffer_percentage', 5, 2)->default(5.00); // 5% default safety buffer
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->foreignId('hospital_id')->nullable()->after('driver_id')->constrained('hospitals')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hospital_id');
        });
        Schema::dropIfExists('hospitals');
    }
};
