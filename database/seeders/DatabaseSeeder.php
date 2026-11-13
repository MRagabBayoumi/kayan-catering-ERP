<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CompanySetting;
use App\Models\Hospital;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Company Settings
        CompanySetting::updateOrCreate(
            ['id' => 1],
            [
                'company_name' => 'Kayan Catering ERP',
                'company_name_ar' => 'شركة كيان لتوريد الأغذية وإعاشة المستشفيات',
                'phone' => '01000000000',
                'email' => 'info@kayan-catering.com',
                'tax_number' => '123-456-789',
                'commercial_register' => '987654',
                'address' => 'مصر - إدارة التوريدات الغذائية المركزية',
                'currency' => 'EGP',
                'settings' => [
                    'theme' => 'light',
                    'default_safety_buffer' => 5,
                    'strict_fifo' => true,
                ],
            ]
        );

        // 2. Users & Roles
        $admin = User::updateOrCreate(
            ['email' => 'admin@kayan.com'],
            [
                'name' => 'مدير النظام',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'phone' => '01011111111',
                'is_active' => true,
            ]
        );

        $costing = User::updateOrCreate(
            ['email' => 'costing@kayan.com'],
            [
                'name' => 'مسؤول التكاليف والعقود',
                'username' => 'costing',
                'password' => Hash::make('password'),
                'role' => User::ROLE_COSTING_MANAGER,
                'phone' => '01022222222',
                'is_active' => true,
            ]
        );

        $storeManager = User::updateOrCreate(
            ['email' => 'store_mgr@kayan.com'],
            [
                'name' => 'مدير المخازن',
                'username' => 'store_mgr',
                'password' => Hash::make('password'),
                'role' => User::ROLE_STORE_MANAGER,
                'phone' => '01033333333',
                'is_active' => true,
            ]
        );

        $storekeeper = User::updateOrCreate(
            ['email' => 'storekeeper@kayan.com'],
            [
                'name' => 'أمين المخزن الرئيسي',
                'username' => 'storekeeper',
                'password' => Hash::make('password'),
                'role' => User::ROLE_STOREKEEPER,
                'phone' => '01044444444',
                'is_active' => true,
            ]
        );

        $driver1 = User::updateOrCreate(
            ['email' => 'driver1@kayan.com'],
            [
                'name' => 'أحمد السائق (سيارة تبريد 1)',
                'username' => 'driver1',
                'password' => Hash::make('password'),
                'role' => User::ROLE_DRIVER,
                'phone' => '01055555551',
                'is_active' => true,
            ]
        );

        $driver2 = User::updateOrCreate(
            ['email' => 'driver2@kayan.com'],
            [
                'name' => 'محمود السائق (سيارة جافات 2)',
                'username' => 'driver2',
                'password' => Hash::make('password'),
                'role' => User::ROLE_DRIVER,
                'phone' => '01055555552',
                'is_active' => true,
            ]
        );

        $accountant = User::updateOrCreate(
            ['email' => 'accountant@kayan.com'],
            [
                'name' => 'محاسب المخزن والتسويات',
                'username' => 'accountant',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ACCOUNTANT,
                'phone' => '01066666666',
                'is_active' => true,
            ]
        );

        // 3. Warehouses
        $mainWh = Warehouse::updateOrCreate(
            ['code' => 'WH-MAIN'],
            [
                'name' => 'المخزن الرئيسي العام',
                'type' => Warehouse::TYPE_MAIN,
                'location' => 'مقر الإدارة المركزي - مجمع المخازن',
                'is_active' => true,
                'is_default' => true,
            ]
        );

        $chilledWh = Warehouse::updateOrCreate(
            ['code' => 'WH-CHILLED'],
            [
                'name' => 'مخزن التبريد (خضار وألبان +4C)',
                'type' => Warehouse::TYPE_CHILLED,
                'location' => 'عنبر التبريد المركزي',
                'is_active' => true,
                'is_default' => false,
            ]
        );

        $frozenWh = Warehouse::updateOrCreate(
            ['code' => 'WH-FROZEN'],
            [
                'name' => 'مخزن التجميد (لحوم ودواجن -18C)',
                'type' => Warehouse::TYPE_FROZEN,
                'location' => 'ثلاجة التجميد الكبرى',
                'is_active' => true,
                'is_default' => false,
            ]
        );

        // Mobile Transit Warehouses (Van Custody)
        $vanWh1 = Warehouse::updateOrCreate(
            ['code' => 'VAN-01'],
            [
                'name' => 'عهدة سيارة مبردة 01 (أحمد السائق)',
                'type' => Warehouse::TYPE_VAN_TRANSIT,
                'driver_id' => $driver1->id,
                'location' => 'سيارة نقل مبردة ر ق م 1245',
                'is_active' => true,
                'is_default' => false,
            ]
        );

        $vanWh2 = Warehouse::updateOrCreate(
            ['code' => 'VAN-02'],
            [
                'name' => 'عهدة سيارة صندوق 02 (محمود السائق)',
                'type' => Warehouse::TYPE_VAN_TRANSIT,
                'driver_id' => $driver2->id,
                'location' => 'سيارة نقل بضائع ر ق م 8963',
                'is_active' => true,
                'is_default' => false,
            ]
        );

        // 4. Hospitals (Clients)
        $hosp1 = Hospital::updateOrCreate(
            ['code' => 'HOSP-01'],
            [
                'name' => 'مستشفى السلام التخصصي الدولي',
                'contract_number' => 'CONTR-2026-088',
                'contact_person' => 'د. خالد عبد الرحمن (مدير التغذية)',
                'phone' => '01234567890',
                'address' => 'القاهرة - المعادي',
                'daily_patient_capacity' => 250,
                'default_buffer_percentage' => 5.00,
                'is_active' => true,
                'notes' => 'مواعيد الاستلام الصباحية من 6:00 ص إلى 8:00 ص',
            ]
        );

        $hosp2 = Hospital::updateOrCreate(
            ['code' => 'HOSP-02'],
            [
                'name' => 'مستشفى الأورام الجامعي',
                'contract_number' => 'CONTR-2026-104',
                'contact_person' => 'أ. منى صادق (مشرفة الإعاشة)',
                'phone' => '01298765432',
                'address' => 'كفر الشيخ - الحي الطبي',
                'daily_patient_capacity' => 180,
                'default_buffer_percentage' => 8.00,
                'is_active' => true,
                'notes' => 'يتطلب فحص دقيق لشهادات الصلاحية وتاريخ الذبح',
            ]
        );

        // 5. Categories
        $catMeat = Category::updateOrCreate(
            ['code' => 'CAT-MEAT'],
            ['name' => 'لحوم ودواجن وأسماك', 'storage_type' => 'frozen', 'is_active' => true]
        );

        $catVeg = Category::updateOrCreate(
            ['code' => 'CAT-VEG'],
            ['name' => 'خضروات وفواكه طازجة', 'storage_type' => 'chilled', 'is_active' => true]
        );

        $catDairy = Category::updateOrCreate(
            ['code' => 'CAT-DAIRY'],
            ['name' => 'ألبان ومنتجاتها', 'storage_type' => 'chilled', 'is_active' => true]
        );

        $catDry = Category::updateOrCreate(
            ['code' => 'CAT-DRY'],
            ['name' => 'بقوليات ومواد جافة وبقالة', 'storage_type' => 'dry', 'is_active' => true]
        );

        // 6. Products & Initial Batches (FIFO Demo Data)
        $now = Carbon::now();

        $prod1 = Product::updateOrCreate(
            ['sku' => 'PROD-BEEF-01'],
            [
                'name' => 'لحم بقري بلدي مكعبات تبريد',
                'category_id' => $catMeat->id,
                'cost_price' => 380.00,
                'unit' => 'kg',
                'storage_type' => 'chilled',
                'shelf_life_days' => 5,
                'acceptable_waste_pct' => 3.00,
                'min_stock_alert' => 50,
                'is_active' => true,
            ]
        );

        $prod2 = Product::updateOrCreate(
            ['sku' => 'PROD-CHICK-01'],
            [
                'name' => 'صدور دجاج فيليه طازجة',
                'category_id' => $catMeat->id,
                'cost_price' => 210.00,
                'unit' => 'kg',
                'storage_type' => 'chilled',
                'shelf_life_days' => 4,
                'acceptable_waste_pct' => 2.50,
                'min_stock_alert' => 60,
                'is_active' => true,
            ]
        );

        $prod3 = Product::updateOrCreate(
            ['sku' => 'PROD-RICE-01'],
            [
                'name' => 'أرز مصري ممتاز عريض الحبة',
                'category_id' => $catDry->id,
                'cost_price' => 28.50,
                'unit' => 'kg',
                'storage_type' => 'dry',
                'shelf_life_days' => 365,
                'acceptable_waste_pct' => 0.50,
                'min_stock_alert' => 500,
                'is_active' => true,
            ]
        );

        $prod4 = Product::updateOrCreate(
            ['sku' => 'PROD-OIL-01'],
            [
                'name' => 'زيت نباتي نقي عباد الشمس (كرتونة 12 لتر)',
                'category_id' => $catDry->id,
                'cost_price' => 650.00,
                'unit' => 'carton',
                'storage_type' => 'dry',
                'shelf_life_days' => 540,
                'acceptable_waste_pct' => 0.00,
                'min_stock_alert' => 40,
                'is_active' => true,
            ]
        );

        $prod5 = Product::updateOrCreate(
            ['sku' => 'PROD-TOMATO-01'],
            [
                'name' => 'طماطم طازجة صنف أول (قفص 20 كجم)',
                'category_id' => $catVeg->id,
                'cost_price' => 240.00,
                'unit' => 'cage',
                'storage_type' => 'chilled',
                'shelf_life_days' => 7,
                'acceptable_waste_pct' => 4.00,
                'min_stock_alert' => 30,
                'is_active' => true,
            ]
        );

        // Batches for FIFO (Batch 1 older, Batch 2 newer)
        // Beef: Batch 1 (370 EGP received 2 days ago), Batch 2 (385 EGP received today)
        InventoryBatch::updateOrCreate(
            ['batch_number' => 'BATCH-BEEF-261111'],
            [
                'product_id' => $prod1->id,
                'warehouse_id' => $chilledWh->id,
                'initial_quantity' => 100.00,
                'remaining_quantity' => 40.00,
                'cost_price' => 370.00,
                'production_date' => $now->copy()->subDays(2)->toDateString(),
                'expiry_date' => $now->copy()->addDays(3)->toDateString(),
                'received_at' => $now->copy()->subDays(2),
                'is_exhausted' => false,
            ]
        );

        InventoryBatch::updateOrCreate(
            ['batch_number' => 'BATCH-BEEF-261113'],
            [
                'product_id' => $prod1->id,
                'warehouse_id' => $chilledWh->id,
                'initial_quantity' => 150.00,
                'remaining_quantity' => 150.00,
                'cost_price' => 385.00,
                'production_date' => $now->toDateString(),
                'expiry_date' => $now->copy()->addDays(5)->toDateString(),
                'received_at' => $now,
                'is_exhausted' => false,
            ]
        );

        StockBalance::updateOrCreate(
            ['product_id' => $prod1->id, 'warehouse_id' => $chilledWh->id],
            ['quantity' => 190.00, 'reserved_quantity' => 0.00]
        );

        // Chicken Batches
        InventoryBatch::updateOrCreate(
            ['batch_number' => 'BATCH-CHICK-261112'],
            [
                'product_id' => $prod2->id,
                'warehouse_id' => $chilledWh->id,
                'initial_quantity' => 200.00,
                'remaining_quantity' => 200.00,
                'cost_price' => 210.00,
                'production_date' => $now->copy()->subDays(1)->toDateString(),
                'expiry_date' => $now->copy()->addDays(3)->toDateString(),
                'received_at' => $now->copy()->subDays(1),
                'is_exhausted' => false,
            ]
        );

        StockBalance::updateOrCreate(
            ['product_id' => $prod2->id, 'warehouse_id' => $chilledWh->id],
            ['quantity' => 200.00, 'reserved_quantity' => 0.00]
        );

        // Rice Batches (Dry Store)
        InventoryBatch::updateOrCreate(
            ['batch_number' => 'BATCH-RICE-261020'],
            [
                'product_id' => $prod3->id,
                'warehouse_id' => $mainWh->id,
                'initial_quantity' => 2000.00,
                'remaining_quantity' => 1650.00,
                'cost_price' => 28.50,
                'production_date' => '2026-10-01',
                'expiry_date' => '2027-10-01',
                'received_at' => '2026-10-20 09:00:00',
                'is_exhausted' => false,
            ]
        );

        StockBalance::updateOrCreate(
            ['product_id' => $prod3->id, 'warehouse_id' => $mainWh->id],
            ['quantity' => 1650.00, 'reserved_quantity' => 0.00]
        );
    }
}
