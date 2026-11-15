<?php

namespace Tests\Feature;

use App\Models\DailySiteCensus;
use App\Models\DietSchedule;
use App\Models\Hospital;
use App\Models\MealType;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\Treasury;
use App\Models\User;
use App\Models\VehicleDispatchTrip;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostingAndSiteOperationsLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $siteManager;

    protected User $costingManager;

    protected User $storeManager;

    protected Hospital $hospital1;

    protected Hospital $hospital2;

    protected Hospital $hospital3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->admin = User::where('role', User::ROLE_ADMIN)->first();
        $this->costingManager = User::where('role', User::ROLE_COSTING_MANAGER)->first();
        $this->storeManager = User::where('role', User::ROLE_STORE_MANAGER)->first();

        $this->hospital1 = Hospital::first();
        $this->hospital2 = Hospital::where('id', '!=', $this->hospital1->id)->first() ?? Hospital::create([
            'code' => 'HOSP-TEST-2',
            'name' => 'مستشفى صيدناوي',
            'contract_number' => 'CONTR-002',
            'contact_person' => 'مدير التغذية',
            'daily_patient_capacity' => 300,
        ]);
        $this->hospital3 = Hospital::create([
            'code' => 'HOSP-TEST-3',
            'name' => 'مستشفى أطفال مصر',
            'contract_number' => 'CONTR-003',
            'contact_person' => 'مدير المستشفى',
            'daily_patient_capacity' => 200,
        ]);

        $this->siteManager = User::updateOrCreate(
            ['email' => 'site_mgr_test@kayan.com'],
            [
                'name' => 'مدير موقع إمبابة',
                'username' => 'site_mgr_test',
                'password' => 'password',
                'role' => User::ROLE_SITE_MANAGER,
                'assigned_hospital_id' => $this->hospital1->id,
                'is_active' => true,
            ]
        );
    }

    public function test_site_manager_census_recording_and_cooking_choice(): void
    {
        $this->actingAs($this->siteManager);

        // 1. Visit Site Census Screen
        $response = $this->get(route('site.census.index'));
        $response->assertStatus(200);
        $response->assertSee($this->hospital1->name);

        // 2. Submit Daily Census with Meat + Rice with vermicelli choices
        $schedule = DietSchedule::first();
        $today = Carbon::today()->toDateString();

        $response = $this->post(route('site.census.store'), [
            'hospital_id' => $this->hospital1->id,
            'census_date' => $today,
            'protein_choice' => 'meat',
            'carb_choice' => 'rice_vermicelli',
            'veg_choice' => 'cooked_veg',
            'items' => [
                $schedule->id => 120,
            ],
            'notes' => 'تشغيل غداء اليوم لحم بلدي مع أرز بالشعرية',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('daily_site_censuses', [
            'hospital_id' => $this->hospital1->id,
            'protein_choice' => 'meat',
            'total_patient_count' => 120,
        ]);
    }

    public function test_warehouse_vehicle_trip_registration_and_fuel_cost_allocation(): void
    {
        $this->actingAs($this->storeManager);

        // 1. Visit Trips List & Create Form
        $response = $this->get(route('warehouse.trips.index'));
        $response->assertStatus(200);

        $response = $this->get(route('warehouse.trips.create'));
        $response->assertStatus(200);

        // 2. Submit Trip with 3000 EGP Fuel allocated to 3 hospitals
        $treasury = Treasury::first();
        $response = $this->post(route('warehouse.trips.store'), [
            'vehicle_plate' => 'د ف ج 9632',
            'trip_date' => now()->toDateString(),
            'fuel_expense' => 3000.00,
            'toll_and_other_expense' => 0.00,
            'treasury_id' => $treasury?->id,
            'hospital_ids' => [
                $this->hospital1->id,
                $this->hospital2->id,
                $this->hospital3->id,
            ],
            'notes' => 'تفويل سيارة وتوزيع خامات لـ 3 مواقع',
        ]);

        $response->assertRedirect(route('warehouse.trips.index'));

        // Check trip created
        $this->assertDatabaseHas('vehicle_dispatch_trips', [
            'vehicle_plate' => 'د ف ج 9632',
            'fuel_expense' => 3000.00,
        ]);

        // Check 1000 EGP allocated per hospital
        $trip = VehicleDispatchTrip::where('vehicle_plate', 'د ف ج 9632')->first();
        $this->assertCount(3, $trip->destinations);
        $this->assertEquals(1000.00, (float) $trip->destinations->first()->allocated_amount);
    }

    public function test_site_kitchen_issue_and_costing_reconciliation_variance_report(): void
    {
        $today = Carbon::today()->toDateString();

        // 1. Record Site Census: 100 patients, Meat chosen (150 gm per patient -> 15 kg standard)
        $schedule = DietSchedule::first();
        $census = DailySiteCensus::create([
            'hospital_id' => $this->hospital1->id,
            'site_manager_id' => $this->siteManager->id,
            'census_date' => $today,
            'protein_choice' => 'meat',
            'carb_choice' => 'rice_vermicelli',
            'veg_choice' => 'cooked_veg',
            'total_patient_count' => 100,
            'status' => 'submitted',
        ]);
        $census->items()->create([
            'diet_schedule_id' => $schedule->id,
            'patient_count' => 100,
        ]);

        // 2. Site Warehouse issues 17.5 kg of Meat to kitchen (2.5 kg over-consumption waste!)
        $beefProduct = Product::where('sku', 'RAW-BEEF-01')->first() ?? Product::first();
        $this->actingAs($this->siteManager);

        $response = $this->post(route('site.kitchen-issues.store'), [
            'hospital_id' => $this->hospital1->id,
            'issue_date' => $today,
            'items' => [
                [
                    'product_id' => $beefProduct->id,
                    'quantity_issued' => 17.500,
                    'unit' => 'kg',
                ],
            ],
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('site_kitchen_issues', [
            'hospital_id' => $this->hospital1->id,
            'product_id' => $beefProduct->id,
            'quantity_issued' => 17.500,
        ]);

        // 3. Costing Manager visits Reconciliation Report
        $this->actingAs($this->costingManager);
        $response = $this->get(route('costing.reconciliation.index', [
            'hospital_id' => $this->hospital1->id,
            'date' => $today,
        ]));

        $response->assertStatus(200);
        // Expect over-consumption alert detected
        $response->assertSee('هدر وزيادة استهلاك');
        $response->assertSee('17.500');
    }

    public function test_recipe_creation_with_gram_unit_converts_correctly_to_kg_and_calculates_proper_cost(): void
    {
        $this->actingAs($this->costingManager);

        $riceProduct = Product::where('name', 'LIKE', '%أرز%')->first() ?? Product::create([
            'code' => 'TEST-RICE-01',
            'name' => 'أرز مصري معبأ درجة أولى',
            'unit' => 'kg',
            'cost_price' => 32.00,
        ]);

        $mealType = MealType::first();

        // Submit recipe with 100 grams of rice at 32 EGP/kg
        $response = $this->post(route('costing.recipes.store'), [
            'name' => 'وجبة تجريبية أرز 100 جرام',
            'meal_type_id' => $mealType->id,
            'hospital_id' => $this->hospital1->id,
            'ingredients' => [
                [
                    'product_id' => $riceProduct->id,
                    'raw_quantity_per_meal' => 100, // 100 grams
                    'unit' => 'gm',
                    'standard_unit_cost' => 32.00,
                ],
            ],
        ]);

        $response->assertRedirect(route('costing.recipes.index'));

        // Assert recipe created with normalized standard cost = (100/1000) * 32 = 3.20 EGP (NOT 3200 EGP)
        $recipe = Recipe::where('name', 'وجبة تجريبية أرز 100 جرام')->first();
        $this->assertNotNull($recipe);
        $this->assertEquals(3.20, (float) $recipe->standard_cost_per_meal);

        $ingredient = $recipe->ingredients->first();
        $this->assertNotNull($ingredient);
        $this->assertEquals(0.100, (float) $ingredient->raw_quantity_per_meal);
        $this->assertEquals('kg', $ingredient->unit);
    }
}
