<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->string('symbol')->nullable();
            $table->string('category'); // weight, volume, count, packaging
            $table->decimal('conversion_factor', 16, 6)->default(1.0);
            $table->boolean('is_base_unit')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('category');
            $table->index('is_active');
        });

        // Seed initial standard UOMs
        $now = now();
        $initialUnits = [
            // Weight (Base: kg)
            [
                'name' => 'كيلوجرام',
                'code' => 'kg',
                'symbol' => 'كجم',
                'category' => 'weight',
                'conversion_factor' => 1.000000,
                'is_base_unit' => true,
                'is_active' => true,
                'description' => 'الوحدة الأساسية للأوزان واللحوم والخضار والسلع الجافة',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'جرام',
                'code' => 'g',
                'symbol' => 'جم',
                'category' => 'weight',
                'conversion_factor' => 0.001000,
                'is_base_unit' => false,
                'is_active' => true,
                'description' => '1 جرام = 0.001 كجم (يستخدم للبهارات والمقننات الدقيقة)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'مليجرام',
                'code' => 'mg',
                'symbol' => 'مجم',
                'category' => 'weight',
                'conversion_factor' => 0.000001,
                'is_base_unit' => false,
                'is_active' => true,
                'description' => '1 مليجرام = 0.000001 كجم',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'طن',
                'code' => 'ton',
                'symbol' => 'طن',
                'category' => 'weight',
                'conversion_factor' => 1000.000000,
                'is_base_unit' => false,
                'is_active' => true,
                'description' => '1 طن = 1000 كجم',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Volume (Base: liter)
            [
                'name' => 'لتر',
                'code' => 'l',
                'symbol' => 'لتر',
                'category' => 'volume',
                'conversion_factor' => 1.000000,
                'is_base_unit' => true,
                'is_active' => true,
                'description' => 'الوحدة الأساسية للسوائل والعصائر والألبان والزيوت',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'مليلتر',
                'code' => 'ml',
                'symbol' => 'مل',
                'category' => 'volume',
                'conversion_factor' => 0.001000,
                'is_base_unit' => false,
                'is_active' => true,
                'description' => '1 مليلتر = 0.001 لتر',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Count & Packaging (Base: piece)
            [
                'name' => 'قطعة',
                'code' => 'piece',
                'symbol' => 'قطعة',
                'category' => 'count',
                'conversion_factor' => 1.000000,
                'is_base_unit' => true,
                'is_active' => true,
                'description' => 'الوحدة الفردية الأساسية بالعدد (رغيف، ثمرة فاكهة، عبوة)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'دستة',
                'code' => 'dozen',
                'symbol' => 'دستة',
                'category' => 'count',
                'conversion_factor' => 12.000000,
                'is_base_unit' => false,
                'is_active' => true,
                'description' => '1 دستة = 12 قطعة',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'كرتونة',
                'code' => 'carton',
                'symbol' => 'كرتونة',
                'category' => 'count',
                'conversion_factor' => 24.000000,
                'is_base_unit' => false,
                'is_active' => true,
                'description' => 'كرتونة قياسية (قابلة للتعديل حسب صنف التوريد)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'علبة',
                'code' => 'box',
                'symbol' => 'علبة',
                'category' => 'count',
                'conversion_factor' => 1.000000,
                'is_base_unit' => false,
                'is_active' => true,
                'description' => 'علبة مفردة',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'طرد',
                'code' => 'pack',
                'symbol' => 'طرد',
                'category' => 'count',
                'conversion_factor' => 1.000000,
                'is_base_unit' => false,
                'is_active' => true,
                'description' => 'طرد تغليف',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('units')->insert($initialUnits);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
