<?php

namespace Tests\Feature;

use App\Models\DailyCashClosing;
use App\Models\Hospital;
use App\Models\MealType;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\Tender;
use App\Models\Treasury;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CateringDepartmentsFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $costingManager;

    protected User $accountant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->admin = User::where('role', User::ROLE_ADMIN)->first();
        $this->costingManager = User::where('role', User::ROLE_COSTING_MANAGER)->first();
        $this->accountant = User::where('role', User::ROLE_ACCOUNTANT)->first();
    }

    public function test_costing_department_lifecycle(): void
    {
        $this->actingAs($this->costingManager);

        // 1. Recipes index & create view
        $response = $this->get(route('costing.recipes.index'));
        $response->assertStatus(200);

        $response = $this->get(route('costing.recipes.create'));
        $response->assertStatus(200);

        // 2. Store new recipe
        $mealType = MealType::first();
        $product = Product::first();

        $response = $this->post(route('costing.recipes.store'), [
            'name' => 'وجبة دجاج مشوي مع أرز بسمتي',
            'meal_type_id' => $mealType->id,
            'ingredients' => [
                [
                    'product_id' => $product->id,
                    'raw_quantity_per_meal' => 0.200,
                    'unit' => 'kg',
                    'standard_unit_cost' => 210.00,
                ],
            ],
        ]);
        $response->assertRedirect(route('costing.recipes.index'));
        $this->assertDatabaseHas('recipes', ['name' => 'وجبة دجاج مشوي مع أرز بسمتي']);

        // 3. Daily Kitchen Order calculation
        $recipe = Recipe::where('name', 'وجبة دجاج مشوي مع أرز بسمتي')->first();
        $response = $this->get(route('costing.daily-order', [
            'recipe_id' => $recipe->id,
            'patient_count' => 300,
        ]));
        $response->assertStatus(200);
        $response->assertSee('الخامات المطلوبة لإعداد 300 وجبة');

        // 4. Consumption log index & store
        $hospital = Hospital::first();
        $response = $this->post(route('costing.consumption.store'), [
            'hospital_id' => $hospital->id,
            'recipe_id' => $recipe->id,
            'product_id' => $product->id,
            'actual_served_meals_count' => 300,
            'actual_consumed_qty' => 62.5,
            'consumption_date' => now()->toDateString(),
            'discrepancy_justification' => 'هدر بسيط نتيجة إعداد وجبات طوارئ إضافية',
        ]);
        $response->assertRedirect(route('costing.consumption.index'));
        $this->assertDatabaseHas('consumption_logs', [
            'recipe_id' => $recipe->id,
            'actual_served_meals_count' => 300,
        ]);

        $response = $this->get(route('costing.consumption.index'));
        $response->assertStatus(200);
        $response->assertSee('هدر بسيط نتيجة إعداد وجبات طوارئ إضافية', false);

        // 5. Root costing redirect
        $response = $this->get('/costing');
        $response->assertRedirect(route('costing.recipes.index'));
    }

    public function test_sales_and_tenders_department_lifecycle(): void
    {
        $this->actingAs($this->admin);

        // 1. Hospitals index & create
        $response = $this->get(route('sales.hospitals.index'));
        $response->assertStatus(200);

        $response = $this->get(route('sales.hospitals.create'));
        $response->assertStatus(200);

        // 2. Store Hospital -> should auto-create site warehouse
        $response = $this->post(route('sales.hospitals.store'), [
            'name' => 'مستشفى الدقي التخصصي',
            'contract_number' => 'CONTR-DOKKI-2026',
            'contact_person' => 'د. سمير',
            'phone' => '01012345678',
            'address' => 'الدقي - الجيزة',
            'daily_patient_capacity' => 250,
            'meal_price_breakfast' => 30.00,
            'meal_price_lunch' => 95.00,
            'meal_price_dinner' => 40.00,
            'contract_start_date' => '2026-01-01',
            'contract_end_date' => '2026-12-31',
        ]);
        $response->assertRedirect(route('sales.hospitals.index'));
        $this->assertDatabaseHas('hospitals', ['name' => 'مستشفى الدقي التخصصي']);
        $this->assertDatabaseHas('warehouses', ['type' => 'site']);

        // 3. Tenders index & create
        $response = $this->get(route('sales.tenders.index'));
        $response->assertStatus(200);

        $response = $this->get(route('sales.tenders.create'));
        $response->assertStatus(200);

        // 4. Competitor price archive
        $tender = Tender::first();
        $response = $this->get(route('sales.competitors.index'));
        $response->assertStatus(200);

        $response = $this->post(route('sales.competitors.store'), [
            'tender_id' => $tender->id,
            'competitor_name' => 'شركة المحروسة للأغذية',
            'item_or_meal_name' => 'وجبة غداء فاخرة',
            'our_bid_price' => 85.00,
            'competitor_price' => 91.00,
            'tender_year' => 2026,
            'notes' => 'أرشيف تسعير معتمد',
        ]);
        $response->assertRedirect(route('sales.competitors.index'));
        $this->assertDatabaseHas('competitor_prices', ['competitor_name' => 'شركة المحروسة للأغذية']);
    }

    public function test_procurement_and_finance_department_lifecycle(): void
    {
        $this->actingAs($this->accountant);

        // 1. Suppliers
        $response = $this->get(route('finance.suppliers.index'));
        $response->assertStatus(200);

        // 2. Treasury & Vouchers
        $response = $this->get(route('finance.treasury.index'));
        $response->assertStatus(200);

        $treasury = Treasury::first();
        $hospital = Hospital::first();

        // Create site petty cash payment voucher (عيش وكايزر)
        $response = $this->post(route('finance.treasury.vouchers.store'), [
            'type' => 'payment_voucher',
            'treasury_id' => $treasury->id,
            'hospital_id' => $hospital->id,
            'amount' => 350.00,
            'purpose' => 'شراء 700 رغيف عيش للمستشفى',
            'payment_method' => 'cash',
            'voucher_date' => date('Y-m-d'),
        ]);
        $response->assertRedirect(route('finance.treasury.index'));
        $this->assertDatabaseHas('financial_vouchers', ['amount' => 350.00]);

        // 3. Daily closing
        $response = $this->get(route('finance.closing.index'));
        $response->assertStatus(200);

        $response = $this->post(route('finance.closing.store'), [
            'date' => date('Y-m-d'),
        ]);
        $response->assertRedirect();
        $this->assertNotNull(DailyCashClosing::whereDate('closing_date', date('Y-m-d'))->first());
    }

    public function test_procurement_department_isolated_lifecycle(): void
    {
        $this->actingAs($this->admin);

        // 1. Procurement Orders index & create
        $response = $this->get(route('procurement.orders.index'));
        $response->assertStatus(200);

        $response = $this->get(route('procurement.orders.create'));
        $response->assertStatus(200);

        // 2. Store purchase order
        $response = $this->post(route('procurement.orders.store'), [
            'order_number' => 'PO-TEST-101',
            'order_date' => date('Y-m-d'),
            'total_amount' => 15000.00,
            'status' => 'pending',
            'notes' => 'طلب شراء لحوم طازجة',
        ]);
        $response->assertRedirect(route('procurement.orders.index'));
        $this->assertDatabaseHas('supply_orders', ['order_number' => 'PO-TEST-101']);

        // 3. Procurement Suppliers directory & store
        $response = $this->get(route('procurement.suppliers.index'));
        $response->assertStatus(200);

        $response = $this->post(route('procurement.suppliers.store'), [
            'code' => 'SUP-TEST-99',
            'name' => 'مورد دواجن تجريبي',
            'phone' => '01099999999',
            'payment_terms' => 'آجل 15 يوم',
            'current_balance' => 0.00,
        ]);
        $response->assertRedirect(route('procurement.suppliers.index'));
        $this->assertDatabaseHas('suppliers', ['code' => 'SUP-TEST-99']);
    }

    public function test_settings_and_permissions_matrix(): void
    {
        $this->actingAs($this->admin);

        // 1. Settings index
        $response = $this->get(route('settings.index'));
        $response->assertStatus(200);

        // 2. Users index & create
        $response = $this->get(route('users.index'));
        $response->assertStatus(200);

        $response = $this->get(route('users.create'));
        $response->assertStatus(200);

        // 3. Roles and permissions matrix
        $response = $this->get(route('roles.index'));
        $response->assertStatus(200);
        $response->assertSee('الأدوار ومصفوفة الصلاحيات');
        $response->assertSee('مدير عام المنظومة');
        $response->assertSee('مدير التكاليف والمقننات');
        $response->assertSee('مدير المخازن');
    }
}
