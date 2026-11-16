<?php

namespace Tests\Feature;

use App\Models\MealType;
use App\Models\Product;
use App\Models\User;
use App\Services\CostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeUnitConversionMathTest extends TestCase
{
    use RefreshDatabase;

    protected User $costingManager;

    protected Product $cardamomProduct;

    protected MealType $mealType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->costingManager = User::where('role', User::ROLE_COSTING_MANAGER)->first();

        // 1 kg of cardamom cost 800 EGP
        $this->cardamomProduct = Product::create([
            'sku' => 'TEST-SPICE-CARDAMOM',
            'name' => 'حبهان مطحون ممتاز',
            'unit' => 'kg',
            'cost_price' => 800.00,
            'is_active' => true,
        ]);

        $this->mealType = MealType::first() ?? MealType::create([
            'name' => 'غداء عادي',
            'code' => 'LUNCH',
        ]);
    }

    public function test_fractional_grams_under_one_gram_converts_accurately_without_threshold_bug(): void
    {
        $this->actingAs($this->costingManager);

        $costingService = app(CostingService::class);

        // Recipe specifies 0.5 grams (نصف جرام) per meal
        // 0.5 grams must become 0.0005 kg!
        // At 800 EGP/kg, cost per meal = 0.0005 * 800 = 0.40 EGP (40 piasters)
        // If the old bug occurred (>= 1.0 check), 0.5 remained 0.5 kg, resulting in 400 EGP per meal!
        $recipe = $costingService->createRecipe([
            'name' => 'وجبة دجاج بالحبهان الفاخر',
            'meal_type_id' => $this->mealType->id,
            'target_calories' => 600,
        ], [
            [
                'product_id' => $this->cardamomProduct->id,
                'raw_quantity_per_meal' => 0.5,
                'unit' => 'gram',
                'standard_unit_cost' => 800.00,
            ],
        ]);

        $ingredient = $recipe->ingredients->first();

        $this->assertEquals('kg', $ingredient->unit);
        $this->assertEquals(0.0005, (float) $ingredient->raw_quantity_per_meal);
        $this->assertEquals(0.40, round((float) $recipe->standard_cost_per_meal, 2));
    }

    public function test_arabic_alias_gram_converts_accurately(): void
    {
        $this->actingAs($this->costingManager);

        $costingService = app(CostingService::class);

        // Test with Arabic unit "جرام" and 0.5 grams (نصف جرام)
        $recipe = $costingService->createRecipe([
            'name' => 'وجبة أرز بالزعفران',
            'meal_type_id' => $this->mealType->id,
        ], [
            [
                'product_id' => $this->cardamomProduct->id,
                'raw_quantity_per_meal' => 0.5,
                'unit' => 'جرام',
                'standard_unit_cost' => 800.00,
            ],
        ]);

        $ingredient = $recipe->ingredients->first();

        $this->assertEquals('kg', $ingredient->unit);
        $this->assertEquals(0.0005, (float) $ingredient->raw_quantity_per_meal);
        $this->assertEquals(0.40, round((float) $recipe->standard_cost_per_meal, 2));
    }
}
