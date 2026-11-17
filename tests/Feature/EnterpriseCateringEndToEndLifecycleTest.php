<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Contract;
use App\Models\DailySiteCensus;
use App\Models\Hospital;
use App\Models\JournalEntry;
use App\Models\MealType;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Recipe;
use App\Models\SiteKitchenIssue;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WasteRecord;
use App\Services\ContractService;
use App\Services\CostCenterService;
use App\Services\ProcurementService;
use App\Services\RationEngineService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseCateringEndToEndLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Warehouse $mainWarehouse;

    protected Supplier $supplier;

    protected Product $beefProduct;

    protected Hospital $hospital;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->mainWarehouse = Warehouse::create([
            'code' => 'WH-TEST-MAIN',
            'name' => 'المخزن الرئيسي المركزي',
            'type' => Warehouse::TYPE_MAIN,
            'is_active' => true,
        ]);

        $this->supplier = Supplier::create([
            'code' => 'SUP-TEST-1',
            'name' => 'شركة اللحوم الوطنية للتوريدات',
            'current_balance' => 0.00,
            'is_active' => true,
        ]);

        $this->beefProduct = Product::create([
            'sku' => 'BEEF-001',
            'name' => 'لحم بقري مبرد بالعظم',
            'cost_price' => 300.00,
            'unit' => 'kg',
            'is_active' => true,
        ]);

        $this->hospital = Hospital::create([
            'code' => 'HOSP-01',
            'name' => 'مستشفى الشفاء التخصصي',
            'daily_patient_capacity' => 400,
            'is_active' => true,
        ]);

        // Seed basic accounts
        ChartOfAccount::create([
            'code' => '101300',
            'name' => 'مخزون الخامات الرئيسي',
            'type' => ChartOfAccount::TYPE_ASSET,
            'level' => 3,
        ]);

        ChartOfAccount::create([
            'code' => '201000',
            'name' => 'دائنو وموردو الخامات (AP)',
            'type' => ChartOfAccount::TYPE_LIABILITY,
            'level' => 2,
        ]);
    }

    public function test_complete_procurement_to_inventory_and_accounting_lifecycle(): void
    {
        $procurementService = new ProcurementService;

        // 1. Create Purchase Request
        $pr = $procurementService->createPurchaseRequest([
            'warehouse_id' => $this->mainWarehouse->id,
            'reason' => 'تغطية عجز لحوم مبردة لمطبخ المستشفى',
        ], [
            [
                'product_id' => $this->beefProduct->id,
                'quantity' => 200.00,
                'unit' => 'kg',
                'estimated_unit_cost' => 300.00,
            ],
        ], $this->admin->id);

        $this->assertDatabaseHas('purchase_requests', ['id' => $pr->id, 'status' => PurchaseRequest::STATUS_PENDING_APPROVAL]);

        // 2. Approve PR & Create PO
        $procurementService->approvePurchaseRequest($pr->id, $this->admin->id);
        $this->assertEquals(PurchaseRequest::STATUS_APPROVED, $pr->fresh()->status);

        $po = $procurementService->createPurchaseOrder([
            'purchase_request_id' => $pr->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->mainWarehouse->id,
            'tax_amount' => 0.00,
        ], [
            [
                'product_id' => $this->beefProduct->id,
                'quantity' => 200.00,
                'unit_price' => 300.00,
            ],
        ], $this->admin->id);

        $this->assertEquals(60000.00, $po->total_amount);
        $this->assertEquals(PurchaseRequest::STATUS_PO_CREATED, $pr->fresh()->status);

        // 3. Receive Goods (GRN), Inspect, Create Batch, and Post Accounting Entry
        $grn = $procurementService->recordGoodsReceipt([
            'purchase_order_id' => $po->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->mainWarehouse->id,
            'supplier_invoice_reference' => 'INV-TEST-8899',
            'inspection_notes' => 'تم فحص درجات الحرارة والأختام البيطرية بنجاح',
        ], [
            [
                'product_id' => $this->beefProduct->id,
                'quantity_delivered' => 200.00,
                'quantity_accepted' => 200.00,
                'unit_cost' => 300.00,
                'storage_temperature' => 3.5,
                'batch_number' => 'B-BEEF-TEST-01',
            ],
        ], $this->admin->id);

        // Verify Inventory Batch was created
        $this->assertDatabaseHas('inventory_batches', [
            'batch_number' => 'B-BEEF-TEST-01',
            'product_id' => $this->beefProduct->id,
            'warehouse_id' => $this->mainWarehouse->id,
            'remaining_quantity' => 200.00,
            'cost_price' => 300.00,
        ]);

        // Verify Warehouse Stock Balance increased to 200
        $this->assertEquals(200.00, StockBalance::where('product_id', $this->beefProduct->id)->where('warehouse_id', $this->mainWarehouse->id)->value('quantity'));

        // Verify Supplier balance increased by 60,000 EGP
        $this->assertEquals(60000.00, $this->supplier->fresh()->current_balance);

        // Verify Balanced Journal Entry was automatically created (Dr. Inventory 60k, Cr. Supplier 60k)
        $this->assertDatabaseHas('journal_entries', [
            'source_type' => 'goods_receipt',
            'source_id' => $grn->id,
        ]);
        $entry = JournalEntry::where('source_type', 'goods_receipt')->where('source_id', $grn->id)->first();
        $this->assertEquals(60000.00, $entry->lines()->sum('debit'));
        $this->assertEquals(60000.00, $entry->lines()->sum('credit'));

        // PO status is now fully received
        $this->assertEquals(PurchaseOrder::STATUS_FULLY_RECEIVED, $po->fresh()->status);
    }

    public function test_feasibility_study_and_contract_activation_lifecycle(): void
    {
        $contractService = new ContractService;

        // 1. Feasibility Study for Hospital Tender
        $study = $contractService->createFeasibilityStudy([
            'hospital_name' => 'مستشفى الشفاء التخصصي',
            'planned_duration_months' => 12,
            'expected_total_meals' => 100000,
            'target_selling_price_per_meal' => 100.00,
        ], [
            [
                'cost_category' => 'food',
                'item_name' => 'تكلفة خامات الأغذية',
                'quantity' => 100000,
                'unit' => 'meal',
                'unit_cost' => 50.00,
            ],
            [
                'cost_category' => 'labor',
                'item_name' => 'أجور عمالة الموقع',
                'quantity' => 12,
                'unit' => 'month',
                'unit_cost' => 50000.00,
            ],
        ], $this->admin->id);

        $this->assertEquals(10000000.00, $study->expected_revenue);
        // Food: 5,000,000 + Labor: 600,000 = 5,600,000 Total Cost
        $this->assertEquals(5600000.00, $study->estimated_total_cost);
        $this->assertEquals(4400000.00, $study->expected_gross_margin);
        $this->assertEquals(44.00, $study->expected_net_margin_percentage);

        // 2. Activate Contract with Meal Pricing Matrix and Cost Center
        $mealType = MealType::create(['name' => 'وجبة غداء لحوم', 'code' => 'LUNCH-MEAT']);

        $contract = $contractService->activateContract([
            'hospital_id' => $this->hospital->id,
            'contract_number' => 'CTR-2026-TEST-99',
            'contract_value' => 10000000.00,
            'billing_cycle' => 'monthly',
            'payment_terms_days' => 30,
        ], [
            [
                'meal_type_id' => $mealType->id,
                'diet_category' => 'standard',
                'price_per_meal' => 100.00,
                'minimum_daily_quantity' => 250,
            ],
        ], $this->admin->id);

        $this->assertEquals(Contract::STATUS_ACTIVE, $contract->status);
        $this->assertNotNull($contract->costCenter);
        $this->assertEquals($this->hospital->id, $contract->costCenter->hospital_id);
        $this->assertDatabaseHas('contract_meal_prices', [
            'contract_id' => $contract->id,
            'price_per_meal' => 100.00,
        ]);
    }

    public function test_ration_versions_and_mrp_material_demand_explosion(): void
    {
        $rationService = new RationEngineService;

        $mealType = MealType::create(['name' => 'طاجن رئيسي', 'code' => 'TAGIN-01']);

        $recipe = Recipe::create([
            'recipe_code' => 'RCP-TEST-01',
            'name' => 'طاجن لحم خضار مع أرز',
            'meal_type_id' => $mealType->id,
            'hospital_id' => $this->hospital->id,
            'standard_cost_per_meal' => 45.00,
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        // Create Version 1 with 80% butchery yield (20% loss/bone)
        $version = $rationService->createRecipeVersion($recipe->id, [
            'version_name' => 'إصدار صيف 2026 القياسي',
            'change_reason' => 'اعتماد نسبة تشافي اللحم 80% وفقد الطبخ 25%',
        ], [
            [
                'product_id' => $this->beefProduct->id,
                'raw_quantity_per_meal' => 0.150, // 150 grams net
                'cooked_yield_quantity' => 0.110,
                'butchery_yield_pct' => 80.00, // Yield factor
                'shrinkage_cooking_loss_pct' => 26.60,
                'standard_unit_cost' => 300.00, // 300 EGP per kg
                'unit' => 'kg',
            ],
        ], $this->admin->id);

        $this->assertEquals(1, $version->version_number);
        $this->assertTrue($version->is_active);

        // Test MRP Calculation for 1,000 meals
        // Net theoretical requirement = 1000 * 0.150 = 150 KG
        // Gross requirement after 80% yield = 150 / 0.8 = 187.5 KG
        $mrp = $rationService->calculateMaterialRequirements($recipe->id, 1000);

        $this->assertEquals(1000, $mrp['meals_count']);
        $item = $mrp['items'][0];
        $this->assertEquals(150.00, $item['theoretical_net_qty']);
        $this->assertEquals(187.50, $item['required_procurement_qty']);
        // Total cost = 187.5 * 300 = 56,250 EGP
        $this->assertEquals(56250.00, $item['total_cost']);
    }

    public function test_hospital_cost_center_profitability_calculation(): void
    {
        $costCenterService = new CostCenterService;
        $contractService = new ContractService;

        // 1. Setup active contract
        $mealType = MealType::create(['name' => 'وجبة غداء', 'code' => 'LUNCH']);
        $contractService->activateContract([
            'hospital_id' => $this->hospital->id,
            'contract_number' => 'CTR-PROFIT-01',
        ], [
            [
                'meal_type_id' => $mealType->id,
                'diet_category' => 'standard',
                'price_per_meal' => 100.00,
            ],
        ], $this->admin->id);

        // 2. Record Census: 100 patients served today = 300 meals @ 100 EGP = 30,000 EGP Revenue
        DailySiteCensus::create([
            'census_date' => Carbon::today()->toDateString(),
            'hospital_id' => $this->hospital->id,
            'site_manager_id' => $this->admin->id,
            'total_patient_count' => 100,
            'protein_choice' => 'meat',
            'status' => 'approved',
        ]);

        // 3. Record Kitchen Issues: 40 kg beef @ 300 EGP = 12,000 EGP Food Cost
        SiteKitchenIssue::create([
            'hospital_id' => $this->hospital->id,
            'issue_date' => Carbon::today()->toDateString(),
            'issue_number' => 'KISS-001',
            'product_id' => $this->beefProduct->id,
            'quantity_issued' => 40.00,
            'unit' => 'kg',
            'issued_by' => $this->admin->id,
        ]);

        // 4. Record Waste: 2 kg spoiled beef @ 300 EGP = 600 EGP Waste Cost
        WasteRecord::create([
            'waste_number' => 'WST-001',
            'waste_date' => Carbon::today()->toDateString(),
            'hospital_id' => $this->hospital->id,
            'product_id' => $this->beefProduct->id,
            'quantity' => 2.00,
            'unit' => 'kg',
            'unit_cost' => 300.00,
            'total_cost_value' => 600.00,
            'waste_category' => WasteRecord::CATEGORY_KITCHEN_BURN,
            'reason' => 'احتراق في فرن الطهي',
            'reported_by' => $this->admin->id,
            'status' => WasteRecord::STATUS_APPROVED,
        ]);

        // 5. Calculate Profitability
        $pnl = $costCenterService->getHospitalProfitability(
            $this->hospital->id,
            Carbon::today()->toDateString(),
            Carbon::today()->toDateString()
        );

        $kpis = $pnl['kpis'];
        $this->assertEquals(300, $kpis['total_meals_count']);
        $this->assertEquals(30000.00, $kpis['total_revenue']);
        $this->assertEquals(12000.00, $kpis['food_cost']);
        $this->assertEquals(600.00, $kpis['waste_cost']);
        $this->assertGreaterThan(0, $kpis['net_profit']);
    }
}
