<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\DietSchedule;
use App\Models\DietScheduleItem;
use App\Models\Hospital;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DietScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $hosp1 = Hospital::first();

        // 1. Create a Site Manager user assigned to this hospital
        if ($hosp1) {
            User::updateOrCreate(
                ['email' => 'site_mgr@kayan.com'],
                [
                    'name' => 'م. أحمد توفيق (مدير الموقع والمطابخ)',
                    'username' => 'site_mgr',
                    'password' => Hash::make('password'),
                    'role' => User::ROLE_SITE_MANAGER,
                    'phone' => '01055555555',
                    'assigned_hospital_id' => $hosp1->id,
                    'is_active' => true,
                ]
            );
        }

        // 2. Ensure Key Ingredients Exist
        $catMeat = Category::firstOrCreate(['code' => 'CAT-MEAT'], ['name' => 'لحوم ودواجن', 'storage_type' => 'chilled']);
        $catDry = Category::firstOrCreate(['code' => 'CAT-DRY'], ['name' => 'بقوليات وبقالة', 'storage_type' => 'dry']);
        $catVeg = Category::firstOrCreate(['code' => 'CAT-VEG'], ['name' => 'خضروات وفواكه', 'storage_type' => 'chilled']);

        $beef = Product::firstOrCreate(['sku' => 'RAW-BEEF-01'], ['name' => 'لحم بقري مشفى للطهي', 'category_id' => $catMeat->id, 'unit' => 'kg', 'cost_price' => 380.00]);
        $poultry = Product::firstOrCreate(['sku' => 'RAW-CHICK-01'], ['name' => 'دواجن طازجة (ربع فرخة)', 'category_id' => $catMeat->id, 'unit' => 'kg', 'cost_price' => 180.00]);
        $rice = Product::firstOrCreate(['sku' => 'RAW-RICE-01'], ['name' => 'أرز مصري معبأ درجة أولى', 'category_id' => $catDry->id, 'unit' => 'kg', 'cost_price' => 32.00]);
        $vermicelli = Product::firstOrCreate(['sku' => 'RAW-VERM-01'], ['name' => 'شعرية معبأة فاخرة', 'category_id' => $catDry->id, 'unit' => 'kg', 'cost_price' => 28.00]);
        $pasta = Product::firstOrCreate(['sku' => 'RAW-PASTA-01'], ['name' => 'مكرونة معبأة متنوعة', 'category_id' => $catDry->id, 'unit' => 'kg', 'cost_price' => 28.00]);
        $veg = Product::firstOrCreate(['sku' => 'RAW-VEG-01'], ['name' => 'خضار مشكل طازج للطهي', 'category_id' => $catVeg->id, 'unit' => 'kg', 'cost_price' => 35.00]);
        $beans = Product::firstOrCreate(['sku' => 'RAW-BEANS-01'], ['name' => 'فاصوليا جافة معبأة', 'category_id' => $catDry->id, 'unit' => 'kg', 'cost_price' => 85.00]);
        $cowpea = Product::firstOrCreate(['sku' => 'RAW-COWPEA-01'], ['name' => 'لوبيا جافة معبأة', 'category_id' => $catDry->id, 'unit' => 'kg', 'cost_price' => 85.00]);
        $bread = Product::firstOrCreate(['sku' => 'RAW-BREAD-01'], ['name' => 'خبز بلدي مدعم 110 جم', 'category_id' => $catDry->id, 'unit' => 'pcs', 'cost_price' => 1.50]);
        $salad = Product::firstOrCreate(['sku' => 'RAW-SALAD-01'], ['name' => 'خضار سلطة خضراء طازجة', 'category_id' => $catVeg->id, 'unit' => 'kg', 'cost_price' => 25.00]);
        $fruit = Product::firstOrCreate(['sku' => 'RAW-FRUIT-01'], ['name' => 'فاكهة الموسم طازجة', 'category_id' => $catVeg->id, 'unit' => 'kg', 'cost_price' => 40.00]);
        $oil = Product::firstOrCreate(['sku' => 'RAW-OIL-01'], ['name' => 'زيت طهي نقي', 'category_id' => $catDry->id, 'unit' => 'kg', 'cost_price' => 65.00]);

        // 3. Define Standard Ministerial Diet Schedules
        $schedules = [
            [
                'code' => 'SCHED-12H',
                'name' => 'نوبتجي 12 ساعة (غذاء)',
                'meal_type' => 'غذاء',
            ],
            [
                'code' => 'SCHED-24H',
                'name' => 'عاملين 24 ساعة (غذاء وعشاء)',
                'meal_type' => 'غذاء',
            ],
            [
                'code' => 'SCHED-30H',
                'name' => 'مقيم 30 ساعة (كامل الوجبات)',
                'meal_type' => 'غذاء',
            ],
            [
                'code' => 'SCHED-CHILD-1-2',
                'name' => 'أطفال من 1 إلى 2 سنة',
                'meal_type' => 'غذاء',
            ],
            [
                'code' => 'SCHED-REGULAR',
                'name' => 'اعتيادي مرضى الباطنة والجراحة',
                'meal_type' => 'غذاء',
            ],
        ];

        foreach ($schedules as $schedData) {
            $schedule = DietSchedule::updateOrCreate(
                ['code' => $schedData['code']],
                [
                    'name' => $schedData['name'],
                    'meal_type' => $schedData['meal_type'],
                    'hospital_id' => null, // عام لكافة المستشفيات
                    'daily_bread_count' => 1.00,
                    'is_active' => true,
                ]
            );

            // Populate items with exact ministerial portions (قرار 46 لسنة 2016)
            $items = [
                // اللحم: 150 جم (3 أيام أسبوعياً)
                [
                    'product_id' => $beef->id,
                    'category_type' => 'protein_meat',
                    'portion_quantity' => 0.1500,
                    'unit' => 'kg',
                    'weekly_frequency' => 3.00,
                    'notes' => 'لحم مشفى للطهي 3 أيام أسبوعياً',
                ],
                // الدواجن: 275 جم ربع فرخة (4 أيام أسبوعياً)
                [
                    'product_id' => $poultry->id,
                    'category_type' => 'protein_poultry',
                    'portion_quantity' => 0.2750,
                    'unit' => 'kg',
                    'weekly_frequency' => 4.00,
                    'notes' => 'دواجن ربع فرخة 4 أيام أسبوعياً',
                ],
                // أرز بالشعرية: 85 جم أرز + 15 جم شعرية (يومان)
                [
                    'product_id' => $rice->id,
                    'category_type' => 'carb_rice_vermicelli',
                    'portion_quantity' => 0.0850,
                    'unit' => 'kg',
                    'weekly_frequency' => 2.00,
                    'notes' => 'أرز طهي بالشعرية يومان أسبوعياً',
                ],
                [
                    'product_id' => $vermicelli->id,
                    'category_type' => 'carb_rice_vermicelli',
                    'portion_quantity' => 0.0150,
                    'unit' => 'kg',
                    'weekly_frequency' => 2.00,
                    'notes' => 'شعرية للأرز يومان أسبوعياً',
                ],
                // أرز بدون شعرية: 100 جم (يومان)
                [
                    'product_id' => $rice->id,
                    'category_type' => 'carb_plain_rice',
                    'portion_quantity' => 0.1000,
                    'unit' => 'kg',
                    'weekly_frequency' => 2.00,
                    'notes' => 'أرز أبيض بدون شعرية يومان أسبوعياً',
                ],
                // مكرونة: 100 جم (3 أيام)
                [
                    'product_id' => $pasta->id,
                    'category_type' => 'carb_pasta',
                    'portion_quantity' => 0.1000,
                    'unit' => 'kg',
                    'weekly_frequency' => 3.00,
                    'notes' => 'مكرونة طهي 3 أيام أسبوعياً',
                ],
                // خضار طهي: 200 جم (6 أيام)
                [
                    'product_id' => $veg->id,
                    'category_type' => 'veg_cooked',
                    'portion_quantity' => 0.2000,
                    'unit' => 'kg',
                    'weekly_frequency' => 6.00,
                    'notes' => 'خضار طهي 6 أيام أسبوعياً',
                ],
                // فاصوليا/لوبيا: 80 جم (يوم أسبوعياً)
                [
                    'product_id' => $beans->id,
                    'category_type' => 'veg_dry_legume',
                    'portion_quantity' => 0.0800,
                    'unit' => 'kg',
                    'weekly_frequency' => 1.00,
                    'notes' => 'بقوليات جافة يوم واحد أسبوعياً',
                ],
                // يومي ثابت: خبز، سلطة، فاكهة، زيت
                [
                    'product_id' => $bread->id,
                    'category_type' => 'daily_fixed',
                    'portion_quantity' => 1.0000,
                    'unit' => 'pcs',
                    'weekly_frequency' => 7.00,
                    'notes' => 'رغيف خبز بلدي يومياً',
                ],
                [
                    'product_id' => $salad->id,
                    'category_type' => 'daily_fixed',
                    'portion_quantity' => 0.1000,
                    'unit' => 'kg',
                    'weekly_frequency' => 7.00,
                    'notes' => 'سلطة خضراء 100 جم يومياً',
                ],
                [
                    'product_id' => $fruit->id,
                    'category_type' => 'daily_fixed',
                    'portion_quantity' => 0.2000,
                    'unit' => 'kg',
                    'weekly_frequency' => 7.00,
                    'notes' => 'فاكهة طازجة 200 جم يومياً',
                ],
                [
                    'product_id' => $oil->id,
                    'category_type' => 'daily_fixed',
                    'portion_quantity' => 0.0250,
                    'unit' => 'kg',
                    'weekly_frequency' => 7.00,
                    'notes' => 'زيت طهي 25 جم يومياً',
                ],
            ];

            foreach ($items as $itemData) {
                DietScheduleItem::updateOrCreate(
                    [
                        'diet_schedule_id' => $schedule->id,
                        'product_id' => $itemData['product_id'],
                        'category_type' => $itemData['category_type'],
                    ],
                    $itemData
                );
            }
        }
    }
}
