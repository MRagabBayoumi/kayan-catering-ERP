<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Hospital;
use App\Models\InventoryBatch;
use App\Models\MealType;
use App\Models\Product;
use App\Models\Tender;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CostingService;
use App\Services\SalesTendersService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentSeparationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_department_tenders_competitors_and_contract_termination(): void
    {
        $salesUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $service = new SalesTendersService;

        // 1. Sales creates Tender
        $tender = $service->createTender([
            'title' => 'مناقصة توريد التغذية لمستشفى المنصورة الدولي',
            'entity_name' => 'مستشفى المنصورة الدولي',
            'bid_bond_amount' => 50000.00,
            'booklet_cost' => 3000.00,
            'created_by' => $salesUser->id,
        ]);

        $this->assertDatabaseHas('tenders', [
            'id' => $tender->id,
            'entity_name' => 'مستشفى المنصورة الدولي',
            'status' => Tender::STATUS_UNDER_STUDY,
        ]);

        // 2. Archive competitor prices for future tenders
        $compPrice = $service->archiveCompetitorPrice($tender->id, [
            'competitor_name' => 'شركة مصر للكيترينج',
            'item_or_meal_name' => 'وجبة غداء مريض عادي',
            'our_bid_price' => 120.00,
            'competitor_price' => 110.00,
            'tender_year' => 2026,
            'was_awarded' => true,
        ]);

        $this->assertDatabaseHas('competitor_prices', [
            'id' => $compPrice->id,
            'competitor_name' => 'شركة مصر للكيترينج',
            'competitor_price' => 110.00,
        ]);

        // 3. Award Tender -> Hospital & Site Warehouse auto-created
        $hospital = $service->awardTender($tender->id, [
            'hospital_code' => 'HOSP-MANSOURA',
            'daily_patient_capacity' => 300,
            'contact_person' => 'مدير المستشفى',
        ]);

        $this->assertEquals(Hospital::CONTRACT_ACTIVE, $hospital->contract_status);
        $this->assertDatabaseHas('warehouses', ['code' => 'WH-HOSP-MANSOURA']);

        // 4. Terminate contract from Sales Department
        $service->terminateContract($hospital->id, 'انتهاء مدة العقد السنوي وعدم التجديد');

        $hospital->refresh();
        $this->assertEquals(Hospital::CONTRACT_TERMINATED, $hospital->contract_status);
        $this->assertFalse($hospital->is_active);
        $this->assertEquals('انتهاء مدة العقد السنوي وعدم التجديد', $hospital->termination_reason);
    }

    public function test_costing_department_recipe_portion_and_consumption_variance_tracking(): void
    {
        $costingUser = User::factory()->create(['role' => User::ROLE_COSTING_MANAGER]);
        $costingService = new CostingService;

        $hospital = Hospital::create([
            'code' => 'HOSP-COST-TEST',
            'name' => 'مستشفى الأورام',
            'contract_status' => Hospital::CONTRACT_ACTIVE,
            'is_active' => true,
        ]);

        $mealType = MealType::create([
            'code' => 'LUNCH-DIAB',
            'name' => 'غداء مريض سكر',
            'category' => 'patient',
        ]);

        $category = Category::create([
            'code' => 'CAT-FOOD',
            'name' => 'أغذية',
            'slug' => 'food-test',
        ]);

        $chicken = Product::create([
            'sku' => 'CHICKEN-RAW',
            'name' => 'دجاج فيليه طازج',
            'category_id' => $category->id,
            'cost_price' => 200.00,
            'unit' => 'kg',
        ]);

        $rice = Product::create([
            'sku' => 'RICE-RAW',
            'name' => 'أرز مصري',
            'category_id' => $category->id,
            'cost_price' => 30.00,
            'unit' => 'kg',
        ]);

        $warehouse = Warehouse::create([
            'code' => 'WH-SITE-TEST',
            'name' => 'مخزن الموقع',
            'type' => Warehouse::TYPE_MAIN,
        ]);

        // Inventory Batches with FIFO prices
        InventoryBatch::create([
            'batch_number' => 'BATCH-CHICK-1',
            'product_id' => $chicken->id,
            'warehouse_id' => $warehouse->id,
            'initial_quantity' => 100.00,
            'remaining_quantity' => 100.00,
            'cost_price' => 200.00,
            'received_at' => Carbon::now()->subDays(2),
            'is_exhausted' => false,
        ]);

        InventoryBatch::create([
            'batch_number' => 'BATCH-RICE-1',
            'product_id' => $rice->id,
            'warehouse_id' => $warehouse->id,
            'initial_quantity' => 500.00,
            'remaining_quantity' => 500.00,
            'cost_price' => 30.00,
            'received_at' => Carbon::now()->subDays(5),
            'is_exhausted' => false,
        ]);

        // 1. Costing creates Standard Recipe / Portion BOM
        // Chicken: 150 grams raw (0.150 kg @ 200 EGP = 30 EGP)
        // Rice: 100 grams raw (0.100 kg @ 30 EGP = 3 EGP)
        // Standard meal cost = 33 EGP
        $recipe = $costingService->createRecipe([
            'recipe_code' => 'RCP-DIAB-LUNCH',
            'name' => 'وجبة غداء مريض سكر القياسية',
            'meal_type_id' => $mealType->id,
            'hospital_id' => $hospital->id,
            'created_by' => $costingUser->id,
        ], [
            [
                'product_id' => $chicken->id,
                'raw_quantity_per_meal' => 0.1500,
                'standard_unit_cost' => 200.00,
            ],
            [
                'product_id' => $rice->id,
                'raw_quantity_per_meal' => 0.1000,
                'standard_unit_cost' => 30.00,
            ],
        ]);

        $this->assertEquals(33.00, (float) $recipe->standard_cost_per_meal);

        // 2. Costing checks requirements for 200 patients
        // Expected Chicken: 200 * 0.150 = 30 kg
        // Expected Rice: 200 * 0.100 = 20 kg
        $reqs = $costingService->calculateRequirements($recipe->id, 200);
        $this->assertEquals(30.00, $reqs[0]['total_standard_qty']);
        $this->assertEquals(20.00, $reqs[1]['total_standard_qty']);

        // 3. Kitchen executes 200 meals, but consumes:
        // Chicken: 32 kg (2 kg over-consumption / waste!)
        // Rice: 20 kg (exact)
        $logs = $costingService->recordKitchenConsumption([
            'hospital_id' => $hospital->id,
            'recipe_id' => $recipe->id,
            'actual_served_meals_count' => 200,
            'verified_by_costing' => $costingUser->id,
        ], [
            [
                'product_id' => $chicken->id,
                'actual_consumed_qty' => 32.00,
                'discrepancy_justification' => 'تنظيف وهادر جلد إضافي بالدجاج',
            ],
            [
                'product_id' => $rice->id,
                'actual_consumed_qty' => 20.00,
            ],
        ]);

        $chickenLog = collect($logs)->firstWhere('product_id', $chicken->id);
        $this->assertEquals(30.00, (float) $chickenLog->standard_expected_qty);
        $this->assertEquals(32.00, (float) $chickenLog->actual_consumed_qty);
        $this->assertEquals(2.00, (float) $chickenLog->variance_qty);
        $this->assertEquals(400.00, (float) $chickenLog->cost_variance); // 2 kg * 200 EGP = 400 EGP variance
    }
}
