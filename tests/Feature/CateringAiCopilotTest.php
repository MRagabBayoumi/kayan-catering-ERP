<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CompetitorPrice;
use App\Models\Hospital;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\Tender;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CateringAiService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CateringAiCopilotTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_copilot_answers_all_user_questions_correctly(): void
    {
        $admin = User::factory()->create();

        // 1. Setup Product with FIFO Batch for "عندي قد ايه لحمة"
        $category = Category::create(['code' => 'CAT-MEAT', 'name' => 'لحوم', 'slug' => 'meat']);
        $warehouse = Warehouse::create(['code' => 'WH-MAIN', 'name' => 'المخزن الرئيسي', 'type' => 'main']);

        $meat = Product::create([
            'sku' => 'BEEF-001',
            'name' => 'لحم بقري بلدي مكعبات',
            'category_id' => $category->id,
            'cost_price' => 380.00,
            'unit' => 'kg',
            'storage_type' => 'chilled',
        ]);

        InventoryBatch::create([
            'batch_number' => 'BATCH-MEAT-01',
            'product_id' => $meat->id,
            'warehouse_id' => $warehouse->id,
            'initial_quantity' => 150.00,
            'remaining_quantity' => 150.00,
            'cost_price' => 380.00,
            'received_at' => Carbon::now(),
            'is_exhausted' => false,
        ]);

        StockBalance::create([
            'product_id' => $meat->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 150.00,
        ]);

        // 2. Setup Hospital & Tender with Competitor Price for "المناقصة بتاع الجيزة"
        $hospital = Hospital::create([
            'code' => 'HOSP-IMBABA',
            'name' => 'مستشفى حميات إمبابة',
            'daily_patient_capacity' => 200,
            'is_active' => true,
        ]);

        $tender = Tender::create([
            'tender_number' => 'TND-GIZA-2025',
            'title' => 'مناقصة تغذية مستشفيات الجيزة',
            'hospital_id' => $hospital->id,
            'entity_name' => 'مديرية الشؤون الصحية بالجيزة',
            'created_by' => $admin->id,
        ]);

        CompetitorPrice::create([
            'tender_id' => $tender->id,
            'competitor_name' => 'شركة النيل للوجبات',
            'item_or_meal_name' => 'وجبة غداء مريض عادي',
            'our_bid_price' => 115.00,
            'competitor_price' => 105.00,
            'tender_year' => 2025,
            'was_awarded' => true,
        ]);

        $ai = new CateringAiService;

        // Test 1: Stock Inquiry
        $ans1 = $ai->ask('عندي قد ايه لحمه');
        $this->assertEquals('stock_inquiry', $ans1['intent']);
        $this->assertStringContainsString('لحم بقري', $ans1['message']);
        $this->assertStringContainsString('150', $ans1['message']);

        // Test 2: Tender and Competitor History
        $ans2 = $ai->ask('المناقصه مثلا بتاع الجيزه وجبة الغداء كانت بكام السنه الي فاتت');
        $this->assertEquals('tender_history', $ans2['intent']);
        $this->assertStringContainsString('105', $ans2['message']);
        $this->assertStringContainsString('شركة النيل للوجبات', $ans2['message']);

        // Test 3: Hospital Cost, Waste & True Net Profit Calculation
        $ans3 = $ai->ask('تكلفة مستشفي امبابه كام و معجزه ولا مهدره في البضاعه و صافي ربحها بعد نسبة المطبخ و ضرائب و دمعات و تامينات و عمولة شريك');
        $this->assertEquals('hospital_profitability', $ans3['intent']);
        $this->assertStringContainsString('مستشفى حميات إمبابة', $ans3['headline']);
        $this->assertStringContainsString('نسبة تشغيل المطبخ والعمالة', $ans3['message']);
        $this->assertStringContainsString('ضرائب ودمغات حكومية', $ans3['message']);
        $this->assertStringContainsString('تأمينات عمالة الموقع', $ans3['message']);
        $this->assertStringContainsString('عمولة الشريك', $ans3['message']);
        $this->assertStringContainsString('صافي الربح الحقيقي', $ans3['message']);

        // Test 4: Daily Meals Count
        $ans4 = $ai->ask('انا اكلت كام وجبه النهارده');
        $this->assertEquals('daily_meals_count', $ans4['intent']);
        $this->assertStringContainsString('إجمالي ما تم تقديمه اليوم', $ans4['message']);

        // Test 5: Supplier Account & Last Payment
        $ans5 = $ai->ask('حساب مورد فلان كام اخر مره واخد دفعه امتي');
        $this->assertEquals('supplier_account', $ans5['intent']);
        $this->assertStringContainsString('الرصيد المتبقي المستحق', $ans5['message']);
        $this->assertStringContainsString('تاريخ آخر دفعة', $ans5['message']);
    }
}
