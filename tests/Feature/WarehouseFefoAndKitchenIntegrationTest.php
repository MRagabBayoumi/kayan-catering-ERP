<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\InventoryBatch;
use App\Models\MealType;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseFefoAndKitchenIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Warehouse $mainWarehouse;

    protected Warehouse $vanWarehouse;

    protected Hospital $hospital;

    protected Product $beef;

    protected Product $rice;

    protected Recipe $lunchRecipe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->mainWarehouse = Warehouse::create([
            'code' => 'WH-MAIN',
            'name' => 'المخزن المركزي',
            'type' => Warehouse::TYPE_MAIN,
            'is_active' => true,
        ]);

        $this->vanWarehouse = Warehouse::create([
            'code' => 'WH-VAN-1',
            'name' => 'سيارة مندوب 1',
            'type' => Warehouse::TYPE_VAN_TRANSIT,
            'is_active' => true,
        ]);

        $this->hospital = Hospital::create([
            'name' => 'مستشفى السلام التخصصي',
            'code' => 'HOSP-SALAM',
            'contract_status' => Hospital::CONTRACT_ACTIVE,
            'daily_patient_count' => 120,
            'daily_escort_count' => 30,
            'daily_staff_count' => 50,
            'is_active' => true,
        ]);

        $this->beef = Product::create([
            'sku' => 'PROD-BEEF-TEST',
            'name' => 'لحم بقري بلدي مكعبات',
            'cost_price' => 380.00,
            'unit' => 'kg',
            'storage_type' => 'chilled',
            'shelf_life_days' => 5,
            'acceptable_waste_pct' => 5.0, // 5% acceptable waste
            'min_stock_alert' => 20,
            'is_active' => true,
        ]);

        $this->rice = Product::create([
            'sku' => 'PROD-RICE-TEST',
            'name' => 'أرز مصري ممتاز',
            'cost_price' => 30.00,
            'unit' => 'kg',
            'storage_type' => 'dry',
            'shelf_life_days' => 365,
            'acceptable_waste_pct' => 1.0, // 1% acceptable waste
            'min_stock_alert' => 50,
            'is_active' => true,
        ]);

        $mealType = MealType::create([
            'code' => 'LUNCH',
            'name' => 'غداء مخصص',
            'category' => 'main',
            'is_active' => true,
        ]);

        $this->lunchRecipe = Recipe::create([
            'recipe_code' => 'REC-BEEF-RICE',
            'name' => 'وجبة لحم وأرز',
            'meal_type_id' => $mealType->id,
            'hospital_id' => $this->hospital->id,
            'standard_cost_per_meal' => 70.00,
            'is_approved_by_costing' => true,
            'is_approved_by_site_mgr' => true,
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        RecipeIngredient::create([
            'recipe_id' => $this->lunchRecipe->id,
            'product_id' => $this->beef->id,
            'raw_quantity_per_meal' => 0.1500, // 150g
            'cooked_yield_quantity' => 0.1000,
            'unit' => 'kg',
            'standard_unit_cost' => 380.00,
        ]);

        RecipeIngredient::create([
            'recipe_id' => $this->lunchRecipe->id,
            'product_id' => $this->rice->id,
            'raw_quantity_per_meal' => 0.1200, // 120g
            'cooked_yield_quantity' => 0.2200,
            'unit' => 'kg',
            'standard_unit_cost' => 30.00,
        ]);
    }

    public function test_fefo_orders_batches_by_earliest_expiry_first(): void
    {
        // Batch 1: Expiring in 10 days
        $batchLater = InventoryBatch::create([
            'batch_number' => 'BATCH-EXP-10',
            'product_id' => $this->beef->id,
            'warehouse_id' => $this->mainWarehouse->id,
            'initial_quantity' => 50,
            'remaining_quantity' => 50,
            'cost_price' => 380,
            'received_at' => Carbon::now()->subDays(5),
            'expiry_date' => Carbon::now()->addDays(10),
            'is_exhausted' => false,
        ]);

        // Batch 2: Expiring in 2 days (Critical - FEFO first)
        $batchSooner = InventoryBatch::create([
            'batch_number' => 'BATCH-EXP-02',
            'product_id' => $this->beef->id,
            'warehouse_id' => $this->mainWarehouse->id,
            'initial_quantity' => 30,
            'remaining_quantity' => 30,
            'cost_price' => 380,
            'received_at' => Carbon::now()->subDays(1), // Received later but expires sooner!
            'expiry_date' => Carbon::now()->addDays(2),
            'is_exhausted' => false,
        ]);

        $orderedBatches = InventoryBatch::availableFEFO()->where('product_id', $this->beef->id)->get();

        $this->assertEquals($batchSooner->id, $orderedBatches->first()->id, 'FEFO must prioritize earliest expiring batch');
        $this->assertEquals('critical', $batchSooner->expiry_status);
        $this->assertLessThanOrEqual(2, $batchSooner->days_until_expiry);
    }

    public function test_expiry_dashboard_renders_with_kpi_cards_and_fefo_list(): void
    {
        InventoryBatch::create([
            'batch_number' => 'BATCH-CRITICAL-1',
            'product_id' => $this->beef->id,
            'warehouse_id' => $this->mainWarehouse->id,
            'initial_quantity' => 20,
            'remaining_quantity' => 20,
            'cost_price' => 380,
            'received_at' => Carbon::now(),
            'expiry_date' => Carbon::now()->addDays(3),
            'is_exhausted' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('warehouse.expiry'));

        $response->assertOk();
        $response->assertViewIs('warehouse.expiry');
        $response->assertSee('محرك الصلاحيات وتنبيهات FEFO الذكية');
        $response->assertSee('BATCH-CRITICAL-1');
        $response->assertSee('رأس المال المعرض لمخاطر الهدر');
    }

    public function test_suggest_batch_consumption_returns_relevant_kitchen_recipes(): void
    {
        $batch = InventoryBatch::create([
            'batch_number' => 'BATCH-SUGGEST-BEEF',
            'product_id' => $this->beef->id,
            'warehouse_id' => $this->mainWarehouse->id,
            'initial_quantity' => 15,
            'remaining_quantity' => 15,
            'cost_price' => 380,
            'received_at' => Carbon::now(),
            'expiry_date' => Carbon::now()->addDays(2),
            'is_exhausted' => false,
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('warehouse.expiry.suggest', $batch->id));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'batch' => [
                'batch_number' => 'BATCH-SUGGEST-BEEF',
            ],
        ]);

        $data = $response->json();
        $this->assertNotEmpty($data['suggestions']);
        $this->assertEquals('REC-BEEF-RICE', $data['suggestions'][0]['recipe_code']);
        // 15 kg / 0.150 kg per meal = 100 meals
        $this->assertEquals(100, $data['suggestions'][0]['meals_possible']);
    }

    public function test_calculate_recipe_bom_applies_acceptable_waste_factor(): void
    {
        $response = $this->actingAs($this->admin)->getJson(
            route('warehouse.recipes.bom', ['recipe' => $this->lunchRecipe->id, 'meals_count' => 100])
        );

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'recipe' => [
                'id' => $this->lunchRecipe->id,
                'meals_count' => 100,
            ],
        ]);

        $items = collect($response->json('items'));
        $beefItem = $items->firstWhere('product_id', $this->beef->id);

        $this->assertNotNull($beefItem);
        // 100 meals * 0.150 kg = 15.00 kg net
        $this->assertEquals(15.00, $beefItem['net_qty']);
        // 5% waste of 15 kg = 0.75 kg buffer
        $this->assertEquals(5.0, $beefItem['acceptable_waste_pct']);
        $this->assertEquals(0.75, $beefItem['waste_buffer_qty']);
        // Recommended dispatch = 15.75 kg
        $this->assertEquals(15.75, $beefItem['recommended_dispatch_qty']);
    }

    public function test_create_dispatch_stores_recipe_id_and_planned_meals(): void
    {
        $driver = User::factory()->create(['role' => User::ROLE_DRIVER]);

        // Seed stock for beef
        InventoryBatch::create([
            'batch_number' => 'BATCH-SRC-BEEF',
            'product_id' => $this->beef->id,
            'warehouse_id' => $this->mainWarehouse->id,
            'initial_quantity' => 100,
            'remaining_quantity' => 100,
            'cost_price' => 380,
            'received_at' => Carbon::now()->subDays(2),
            'expiry_date' => Carbon::now()->addDays(10),
            'is_exhausted' => false,
        ]);

        $payload = [
            'hospital_id' => $this->hospital->id,
            'recipe_id' => $this->lunchRecipe->id,
            'planned_meals_count' => 100,
            'source_warehouse_id' => $this->mainWarehouse->id,
            'transit_warehouse_id' => $this->vanWarehouse->id,
            'driver_id' => $driver->id,
            'notes' => 'صرف وجبات غداء مستشفى السلام',
            'items' => [
                [
                    'product_id' => $this->beef->id,
                    'planned_qty' => 15.00,
                    'buffer_qty' => 0.75,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('warehouse.dispatches.store'), $payload);

        $response->assertRedirect(route('warehouse.dispatches'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('dispatch_orders', [
            'hospital_id' => $this->hospital->id,
            'recipe_id' => $this->lunchRecipe->id,
            'planned_meals_count' => 100,
            'driver_id' => $driver->id,
        ]);
    }

    public function test_can_render_product_barcode_label_preview(): void
    {
        $response = $this->actingAs($this->admin)->get(route('products.print-label', $this->beef->id));

        $response->assertOk();
        $response->assertViewIs('products.print-label');
        $response->assertSee('لحم بقري بلدي مكعبات');
        $response->assertSee('PROD-BEEF-TEST');
        $response->assertSee('svg', false);
    }

    public function test_can_render_batch_barcode_label_preview(): void
    {
        $batch = InventoryBatch::create([
            'batch_number' => 'BATCH-TEST-PRINT-01',
            'product_id' => $this->beef->id,
            'warehouse_id' => $this->mainWarehouse->id,
            'initial_quantity' => 25,
            'remaining_quantity' => 25,
            'cost_price' => 380,
            'received_at' => Carbon::now(),
            'expiry_date' => Carbon::now()->addDays(5),
            'is_exhausted' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('warehouse.batches.print-label', $batch->id));

        $response->assertOk();
        $response->assertViewIs('products.print-label');
        $response->assertSee('BATCH-TEST-PRINT-01');
        $response->assertSee('الصلاحية:');
        $response->assertSee('svg', false);
    }
}
