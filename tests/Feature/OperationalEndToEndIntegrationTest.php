<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ChartOfAccount;
use App\Models\CompanySetting;
use App\Models\Contract;
use App\Models\ContractMealPrice;
use App\Models\CostCenter;
use App\Models\DailySiteCensus;
use App\Models\DailySiteCensusItem;
use App\Models\DeliveryReceipt;
use App\Models\DietSchedule;
use App\Models\DispatchOrder;
use App\Models\DriverSettlement;
use App\Models\GoodsReceiptNote;
use App\Models\Hospital;
use App\Models\JournalEntry;
use App\Models\MealType;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WasteRecord;
use App\Services\CateringLogisticsService;
use App\Services\CostCenterService;
use App\Services\DocumentTraceabilityService;
use App\Services\ProcurementService;
use App\Services\RationEngineService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalEndToEndIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed Company Settings
        CompanySetting::updateOrCreate(
            ['id' => 1],
            [
                'company_name' => 'Kayan Catering ERP',
                'company_name_ar' => 'شركة كيان لخدمات الأغذية وإعاشة المستشفيات',
                'currency' => 'EGP',
            ]
        );

        // Seed Core Chart of Accounts
        $accounts = [
            ['code' => '101300', 'name' => 'مخزون الخامات الغذائية الرئيسي', 'type' => 'asset'],
            ['code' => '101400', 'name' => 'مخزون عهدة سيارات التوزيع المتنقلة', 'type' => 'asset'],
            ['code' => '101500', 'name' => 'مخزون المواقع الفرعية بالمستشفيات', 'type' => 'asset'],
            ['code' => '101600', 'name' => 'مدينو المستشفيات والجهات المتعاقدة (AR)', 'type' => 'asset'],
            ['code' => '201000', 'name' => 'دائنو وموردو الخامات والتوريدات (AP)', 'type' => 'liability'],
            ['code' => '401000', 'name' => 'إيرادات عقود تغذية المستشفيات المعتمدة', 'type' => 'revenue'],
            ['code' => '501000', 'name' => 'تكلفة خامات الأغذية المستهلكة (Food COGS)', 'type' => 'expense'],
            ['code' => '502000', 'name' => 'أجور ومرتبات عمالة مطابخ المستشفيات', 'type' => 'expense'],
            ['code' => '503000', 'name' => 'مصاريف وقود ولوجستيات سيارات التوزيع', 'type' => 'expense'],
            ['code' => '504000', 'name' => 'مصروفات تشغيل الموقع ومشتريات الطوارئ', 'type' => 'expense'],
            ['code' => '505000', 'name' => 'خسائر الهدر والتلف المطبخي والمخزني', 'type' => 'expense'],
        ];

        foreach ($accounts as $acc) {
            ChartOfAccount::create([
                'code' => $acc['code'],
                'name' => $acc['name'],
                'type' => $acc['type'],
                'is_active' => true,
            ]);
        }
    }

    /**
     * Complete End-to-End Enterprise Catering Lifecycle acceptance test.
     */
    public function test_complete_enterprise_catering_lifecycle_end_to_end(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $driver = User::factory()->create(['role' => User::ROLE_DRIVER, 'name' => 'أحمد السائق']);
        $storekeeper = User::factory()->create(['role' => User::ROLE_STOREKEEPER]);
        $accountant = User::factory()->create(['role' => User::ROLE_ACCOUNTANT]);

        $this->actingAs($admin);

        // 1. Create Central Warehouse and Van Transit Warehouse
        $mainWarehouse = Warehouse::create([
            'code' => 'WH-MAIN',
            'name' => 'المخزن المركزي الرئيسي',
            'type' => Warehouse::TYPE_MAIN,
            'is_active' => true,
        ]);

        $vanWarehouse = Warehouse::create([
            'code' => 'VAN-01',
            'name' => 'سيارة توزيع مبردة 01',
            'type' => Warehouse::TYPE_VAN_TRANSIT,
            'driver_id' => $driver->id,
            'is_active' => true,
        ]);

        // 2. Create Category & Products
        $cat = Category::create(['name' => 'لحوم ودواجن', 'code' => 'MEAT', 'is_active' => true]);

        $productBeef = Product::create([
            'name' => 'لحم بقري بلدي طازج',
            'sku' => 'BEEF-001',
            'barcode' => '622100000001',
            'category_id' => $cat->id,
            'unit' => 'kg',
            'cost_price' => 280.00,
            'is_active' => true,
        ]);

        $productRice = Product::create([
            'name' => 'أرز مصري حبة رفيعة',
            'sku' => 'RICE-001',
            'barcode' => '622100000002',
            'category_id' => $cat->id,
            'unit' => 'kg',
            'cost_price' => 32.00,
            'is_active' => true,
        ]);

        // 3. Create Hospital & Active Contract & Meal Prices
        $hospital = Hospital::create([
            'code' => 'HOSP-ASWAN',
            'name' => 'مستشفى أسوان التخصصي',
            'daily_patient_capacity' => 250,
            'contract_status' => Hospital::CONTRACT_ACTIVE,
            'is_active' => true,
        ]);

        $costCenter = CostCenter::create([
            'code' => 'CC-ASWAN',
            'name' => 'مركز تكلفة مستشفى أسوان التخصصي',
            'hospital_id' => $hospital->id,
            'is_active' => true,
        ]);

        $contract = Contract::create([
            'contract_number' => 'CTR-2026-ASWAN-01',
            'hospital_id' => $hospital->id,
            'cost_center_id' => $costCenter->id,
            'title' => 'عقد تقديم خدمات التغذية المطهية لمستشفى أسوان',
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addYear(),
            'contract_value' => 12500000.00,
            'status' => Contract::STATUS_ACTIVE,
            'approved_by' => $admin->id,
            'approved_at' => Carbon::now(),
        ]);

        $mealTypeLunch = MealType::create([
            'name' => 'وجبة غداء رئيسية',
            'code' => 'LUNCH',
            'is_active' => true,
        ]);

        ContractMealPrice::create([
            'contract_id' => $contract->id,
            'meal_type_id' => $mealTypeLunch->id,
            'price_per_meal' => 95.00,
        ]);

        // 4. Create Standard Recipe (BOM)
        $recipe = Recipe::create([
            'recipe_code' => 'RCP-BEEF-LUNCH',
            'name' => 'وجبة غداء لحم خضار وأرز',
            'meal_type_id' => $mealTypeLunch->id,
            'hospital_id' => $hospital->id,
            'standard_cost_per_meal' => 65.00,
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        RecipeIngredient::create([
            'recipe_id' => $recipe->id,
            'product_id' => $productBeef->id,
            'raw_quantity_per_meal' => 0.150, // 150g raw beef
            'unit' => 'kg',
            'standard_unit_cost' => 280.00,
        ]);

        RecipeIngredient::create([
            'recipe_id' => $recipe->id,
            'product_id' => $productRice->id,
            'raw_quantity_per_meal' => 0.100, // 100g rice
            'unit' => 'kg',
            'standard_unit_cost' => 32.00,
        ]);

        // 5. Daily Site Census
        $diet = DietSchedule::create([
            'hospital_id' => $hospital->id,
            'name' => 'حمية عادية',
            'code' => 'DIET-STD',
            'is_active' => true,
        ]);
        $census = DailySiteCensus::create([
            'hospital_id' => $hospital->id,
            'site_manager_id' => $admin->id,
            'census_date' => Carbon::today(),
            'total_patient_count' => 200,
            'status' => 'submitted',
        ]);
        DailySiteCensusItem::create([
            'daily_site_census_id' => $census->id,
            'diet_schedule_id' => $diet->id,
            'patient_count' => 200,
        ]);

        // 6. Calculate MRP (200 meals) & detect shortage
        $rationService = app(RationEngineService::class);
        $mrp = $rationService->calculateMaterialRequirements($recipe->id, 200);

        $this->assertEquals(200, $mrp['meals_count']);
        $this->assertCount(2, $mrp['items']);
        $this->assertEquals(30.0, (float) $mrp['items'][0]['theoretical_net_qty']); // 200 * 0.150kg = 30kg beef
        $this->assertEquals(20.0, (float) $mrp['items'][1]['theoretical_net_qty']); // 200 * 0.100kg = 20kg rice

        // 7. Create Purchase Request for Shortage
        $procurementService = app(ProcurementService::class);
        $pr = $procurementService->createPurchaseRequest([
            'warehouse_id' => $mainWarehouse->id,
            'request_date' => Carbon::today(),
            'reason' => 'عجز احتياج وجبات مستشفى أسوان MRP',
        ], [
            ['product_id' => $productBeef->id, 'quantity' => 50.0, 'unit' => 'kg', 'estimated_unit_cost' => 280.00],
            ['product_id' => $productRice->id, 'quantity' => 100.0, 'unit' => 'kg', 'estimated_unit_cost' => 32.00],
        ], $admin->id);

        $this->assertEquals(PurchaseRequest::STATUS_PENDING_APPROVAL, $pr->status);

        // 8. Approve Purchase Request
        $pr = $procurementService->approvePurchaseRequest($pr->id, $admin->id);
        $this->assertEquals(PurchaseRequest::STATUS_APPROVED, $pr->status);

        // 9. Compare Supplier Quotes & Convert to Purchase Order
        $supplierA = Supplier::create([
            'code' => 'SUP-001',
            'name' => 'شركة النيل للتوريدات الغذائية',
            'payment_terms' => 'آجل 30 يوم',
            'is_active' => true,
        ]);

        SupplierProduct::create([
            'supplier_id' => $supplierA->id,
            'product_id' => $productBeef->id,
            'supplier_price' => 275.00,
            'effective_date' => Carbon::today(),
            'is_preferred' => true,
        ]);

        SupplierProduct::create([
            'supplier_id' => $supplierA->id,
            'product_id' => $productRice->id,
            'supplier_price' => 30.00,
            'effective_date' => Carbon::today(),
            'is_preferred' => true,
        ]);

        $comparison = $procurementService->compareSupplierQuotes($pr->id);
        $this->assertNotEmpty($comparison['supplier_rankings']);

        $po = $procurementService->convertPrToPo($pr->id, $supplierA->id, $admin->id);
        $this->assertNotNull($po);
        $this->assertEquals(PurchaseOrder::STATUS_APPROVED, $po->status);
        $this->assertEquals(16750.00, (float) $po->total_amount); // (50 * 275) + (100 * 30) = 13750 + 3000 = 16750

        // 10. Goods Receipt Note (GRN) Receiving
        $grn = $procurementService->recordGoodsReceipt([
            'supplier_id' => $supplierA->id,
            'warehouse_id' => $mainWarehouse->id,
            'purchase_order_id' => $po->id,
            'supplier_invoice_reference' => 'INV-SUP-9901',
            'inspection_notes' => 'تم الفحص المخزني ومطابقة الجودة والصلاحية',
        ], [
            [
                'product_id' => $productBeef->id,
                'quantity_delivered' => 50.0,
                'quantity_accepted' => 50.0,
                'unit_cost' => 275.00,
                'batch_number' => 'B-BEEF-2601',
                'expiry_date' => Carbon::today()->addMonths(6)->toDateString(),
            ],
            [
                'product_id' => $productRice->id,
                'quantity_delivered' => 100.0,
                'quantity_accepted' => 100.0,
                'unit_cost' => 30.00,
                'batch_number' => 'B-RICE-2601',
                'expiry_date' => Carbon::today()->addMonths(12)->toDateString(),
            ],
        ], $storekeeper->id);

        $this->assertEquals(GoodsReceiptNote::STATUS_STORED, $grn->status);

        // Verify Central Warehouse stock updated
        $beefStock = StockBalance::where('product_id', $productBeef->id)->where('warehouse_id', $mainWarehouse->id)->first();
        $this->assertEquals(50.0, (float) $beefStock->quantity);

        // Verify balanced journal entry for GRN
        $grnJv = JournalEntry::where('source_type', 'goods_receipt')->where('source_id', $grn->id)->first();
        $this->assertNotNull($grnJv);
        $this->assertEquals(16750.00, (float) $grnJv->lines->sum('debit'));
        $this->assertEquals(16750.00, (float) $grnJv->lines->sum('credit'));

        // 11. Create Supply Order & Dispatch (Picking & Loading to Van)
        $logisticsService = app(CateringLogisticsService::class);
        $so = $logisticsService->createSupplyOrder([
            'hospital_id' => $hospital->id,
            'created_by' => $admin->id,
        ], [
            ['product_id' => $productBeef->id, 'planned_quantity' => 30.0, 'unit' => 'kg'],
            ['product_id' => $productRice->id, 'planned_quantity' => 20.0, 'unit' => 'kg'],
        ]);

        $dispatch = $logisticsService->createDispatchOrder([
            'supply_order_id' => $so->id,
            'hospital_id' => $hospital->id,
            'recipe_id' => $recipe->id,
            'planned_meals_count' => 200,
            'source_warehouse_id' => $mainWarehouse->id,
            'transit_warehouse_id' => $vanWarehouse->id,
            'driver_id' => $driver->id,
            'storekeeper_id' => $storekeeper->id,
        ], [
            ['product_id' => $productBeef->id, 'planned_qty' => 30.0, 'buffer_qty' => 2.0], // 32kg total
            ['product_id' => $productRice->id, 'planned_qty' => 20.0, 'buffer_qty' => 1.0], // 21kg total
        ]);

        $this->assertEquals(DispatchOrder::STATUS_IN_TRANSIT, $dispatch->status);

        // Verify transfer: Main Warehouse deducted, Van Warehouse loaded
        $beefMain = StockBalance::where('product_id', $productBeef->id)->where('warehouse_id', $mainWarehouse->id)->first();
        $this->assertEquals(18.0, (float) $beefMain->quantity); // 50 - 32 = 18

        $beefVan = StockBalance::where('product_id', $productBeef->id)->where('warehouse_id', $vanWarehouse->id)->first();
        $this->assertEquals(32.0, (float) $beefVan->quantity);

        // 12. Hospital Storekeeper Receives at Site (Mobile Receiving)
        $deliveryReceipt = $logisticsService->recordDeliveryReceipt(
            $dispatch->id,
            ['receiving_officer_name' => 'مشرف تغذية المستشفى'],
            [
                [
                    'product_id' => $productBeef->id,
                    'dispatched_qty' => 32.0,
                    'actual_delivered_qty' => 30.0,
                    'returned_qty' => 2.0,
                    'rejected_qty' => 0.0,
                ],
                [
                    'product_id' => $productRice->id,
                    'dispatched_qty' => 21.0,
                    'actual_delivered_qty' => 20.0,
                    'returned_qty' => 1.0,
                    'rejected_qty' => 0.0,
                ],
            ]
        );

        $this->assertEquals(DeliveryReceipt::STATUS_VERIFIED, $deliveryReceipt->status);

        // Verify inventory moved from Van to Hospital Site Warehouse
        $siteWarehouse = $hospital->fresh()->siteWarehouse;
        $this->assertNotNull($siteWarehouse);

        $beefSite = StockBalance::where('product_id', $productBeef->id)->where('warehouse_id', $siteWarehouse->id)->first();
        $this->assertEquals(30.0, (float) $beefSite->quantity);

        $beefVanAfterDelivery = StockBalance::where('product_id', $productBeef->id)->where('warehouse_id', $vanWarehouse->id)->first();
        $this->assertEquals(2.0, (float) $beefVanAfterDelivery->quantity); // only returned buffer remains in van

        // 13. Site Kitchen Issue (Issue raw materials from Site Warehouse to Kitchen)
        $this->post(route('site.kitchen-issues.store'), [
            'hospital_id' => $hospital->id,
            'issue_date' => Carbon::today()->toDateString(),
            'items' => [
                ['product_id' => $productBeef->id, 'quantity_issued' => 30.0, 'unit' => 'kg'],
                ['product_id' => $productRice->id, 'quantity_issued' => 20.0, 'unit' => 'kg'],
            ],
        ])->assertRedirect();

        // Verify Site Warehouse balance is deducted
        $beefSiteAfterIssue = StockBalance::where('product_id', $productBeef->id)->where('warehouse_id', $siteWarehouse->id)->first();
        $this->assertEquals(0.0, (float) $beefSiteAfterIssue->quantity);

        // Verify balanced journal entry for Food Cost posted to Hospital Cost Center
        $kitchenJv = JournalEntry::where('source_type', 'kitchen_issue')->latest()->first();
        $this->assertNotNull($kitchenJv);
        $this->assertEquals($costCenter->id, $kitchenJv->lines->where('debit', '>', 0)->first()->cost_center_id);

        // 14. Record Waste / Spoilage
        $this->post(route('site.waste.store'), [
            'hospital_id' => $hospital->id,
            'product_id' => $productBeef->id,
            'waste_date' => Carbon::today()->toDateString(),
            'quantity' => 1.5,
            'waste_category' => WasteRecord::CATEGORY_NORMAL_COOKING_LOSS,
            'reason' => 'فاقد دهون وتشذيب أثناء الطهي',
            'unit_cost' => 275.00,
        ])->assertRedirect();

        $waste = WasteRecord::latest()->first();
        $this->assertNotNull($waste);
        $this->assertEquals(412.50, (float) $waste->total_cost_value); // 1.5 * 275 = 412.50

        // 15. Settle Driver Trip Custody (reconcile returned buffer back to Central Warehouse)
        $settlement = $logisticsService->settleDriverTrip($dispatch->id, $deliveryReceipt->id, $accountant->id, 'تمت التسوية بنجاح');
        $this->assertEquals(DriverSettlement::STATUS_SETTLED, $settlement->status);

        // Verify returned buffer was transferred from Van back to Main Warehouse
        $beefMainAfterSettlement = StockBalance::where('product_id', $productBeef->id)->where('warehouse_id', $mainWarehouse->id)->first();
        $this->assertEquals(20.0, (float) $beefMainAfterSettlement->quantity); // 18 + 2 returned = 20

        // 16. Recognize Hospital Contract Revenue in Cost Center
        $costCenterService = app(CostCenterService::class);
        $revJv = $costCenterService->recognizeHospitalRevenue(
            hospitalId: $hospital->id,
            date: Carbon::today()->toDateString(),
            mealCount: 200,
            overridePricePerMeal: 95.00,
            userId: $admin->id
        );

        $this->assertNotNull($revJv);
        $this->assertEquals(19000.00, (float) $revJv->lines->where('debit', '>', 0)->first()->debit); // 200 meals * 95 = 19,000
        $this->assertEquals($costCenter->id, $revJv->lines->first()->cost_center_id);

        // 17. Generate Hospital Profitability Report & P&L Statement
        $profitability = $costCenterService->getHospitalProfitability(
            hospitalId: $hospital->id,
            fromDate: Carbon::today()->startOfMonth()->toDateString(),
            toDate: Carbon::today()->endOfMonth()->toDateString()
        );

        $this->assertEquals(19000.00, (float) $profitability['kpis']['total_revenue']);
        $this->assertGreaterThan(0, $profitability['kpis']['food_cost']);
        $this->assertGreaterThan(0, $profitability['kpis']['net_profit']);

        // 18. Document Traceability (Audit trail backwards and forwards)
        $traceService = app(DocumentTraceabilityService::class);

        $traceContract = $traceService->trace('contract', $contract->id);
        $this->assertNotEmpty($traceContract['downstream']['supply_orders']);

        $traceDispatch = $traceService->trace('dispatch_order', $dispatch->id);
        $this->assertNotNull($traceDispatch['downstream']['delivery_receipt']);
        $this->assertNotNull($traceDispatch['downstream']['settlement']);

        $tracePo = $traceService->trace('purchase_order', $po->id);
        $this->assertNotEmpty($tracePo['downstream']['goods_receipt_notes']);
    }
}
