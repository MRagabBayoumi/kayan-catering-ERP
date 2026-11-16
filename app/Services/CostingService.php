<?php

namespace App\Services;

use App\Models\ConsumptionLog;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CostingService
{
    public function __construct(
        protected ?UnitConversionService $uomService = null
    ) {
        $this->uomService = $this->uomService ?? app(UnitConversionService::class);
    }

    /**
     * 1. Create or Update a Standard Recipe / Portion BOM.
     */
    public function createRecipe(array $data, array $ingredients): Recipe
    {
        return DB::transaction(function () use ($data, $ingredients) {
            $recipeCode = $data['recipe_code'] ?? 'RCP-'.date('Ymd').'-'.str_pad((string) (Recipe::count() + 1), 3, '0', STR_PAD_LEFT);

            $recipe = Recipe::create([
                'recipe_code' => $recipeCode,
                'name' => $data['name'],
                'meal_type_id' => $data['meal_type_id'],
                'hospital_id' => $data['hospital_id'] ?? null,
                'standard_cost_per_meal' => $data['standard_cost_per_meal'] ?? 0.00,
                'is_approved_by_costing' => $data['is_approved_by_costing'] ?? true,
                'is_approved_by_site_mgr' => $data['is_approved_by_site_mgr'] ?? true,
                'is_active' => true,
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? auth()->id(),
            ]);

            $totalEstCost = 0.00;

            foreach ($ingredients as $ing) {
                $rawQty = (float) $ing['raw_quantity_per_meal'];
                $unit = strtolower((string) ($ing['unit'] ?? 'kg'));
                $product = Product::find($ing['product_id']);

                // Sourced automatically from Procurement Department / Product Master
                $unitCost = (float) ($ing['standard_unit_cost'] ?? 0.00);
                if ($unitCost <= 0.00 && $product) {
                    $unitCost = (float) $product->cost_price;
                }

                // Normalize unit and quantity for standard warehouse inventory consistency (e.g. g -> kg, ml -> l)
                $normalized = $this->uomService->normalizeForRecipe($rawQty, $unit);
                $rawQty = $normalized['quantity'];
                $unit = $normalized['unit'];

                $totalEstCost += ($rawQty * $unitCost);

                RecipeIngredient::create([
                    'recipe_id' => $recipe->id,
                    'product_id' => $ing['product_id'],
                    'raw_quantity_per_meal' => $rawQty,
                    'cooked_yield_quantity' => $ing['cooked_yield_quantity'] ?? null,
                    'unit' => $unit,
                    'standard_unit_cost' => $unitCost,
                    'notes' => $ing['notes'] ?? null,
                ]);
            }

            if ($totalEstCost > 0) {
                $recipe->standard_cost_per_meal = $totalEstCost;
                $recipe->save();
            }

            return $recipe->load('ingredients.product', 'mealType');
        });
    }

    /**
     * 2. Calculate Standard Requirements for a specific meal count.
     * Formula: Expected = Patient Count * Portion per Meal.
     *
     * @return array<int, array{product_id: int, product_name: string, standard_qty: float, unit: string}>
     */
    public function calculateRequirements(int $recipeId, int $servedMealCount): array
    {
        $recipe = Recipe::with('ingredients.product')->findOrFail($recipeId);

        $requirements = [];
        foreach ($recipe->ingredients as $ing) {
            $standardQty = (float) $ing->raw_quantity_per_meal * $servedMealCount;
            $requirements[] = [
                'product_id' => $ing->product_id,
                'product_name' => $ing->product->name,
                'raw_portion_per_meal' => (float) $ing->raw_quantity_per_meal,
                'total_standard_qty' => round($standardQty, 4),
                'unit' => $ing->unit,
                'standard_unit_cost' => (float) $ing->standard_unit_cost,
                'estimated_total_cost' => round($standardQty * (float) $ing->standard_unit_cost, 2),
            ];
        }

        return $requirements;
    }

    /**
     * 3. Track Daily Kitchen Consumption & Compare against Standard Portion.
     */
    public function recordKitchenConsumption(array $header, array $consumedItems): array
    {
        return DB::transaction(function () use ($header, $consumedItems) {
            $recipe = Recipe::with('ingredients')->findOrFail($header['recipe_id']);
            $hospitalId = $header['hospital_id'];
            $mealCount = (int) $header['actual_served_meals_count'];
            $consumptionDate = $header['consumption_date'] ?? Carbon::today();

            $logs = [];

            foreach ($consumedItems as $item) {
                $productId = $item['product_id'];
                $actualConsumedQty = (float) $item['actual_consumed_qty'];

                // Get standard ingredient definition
                $ing = $recipe->ingredients->firstWhere('product_id', $productId);
                $portionPerMeal = $ing ? (float) $ing->raw_quantity_per_meal : 0.00;
                $standardExpectedQty = $portionPerMeal * $mealCount;

                // Variance = Actual - Standard (+ means over-consumption, - means savings)
                $varianceQty = $actualConsumedQty - $standardExpectedQty;

                // Look up FIFO unit cost from latest active batch for this product
                $fifoCost = (float) (InventoryBatch::where('product_id', $productId)
                    ->availableFEFO()
                    ->value('cost_price') ?? ($ing?->standard_unit_cost ?? 0.00));

                $actualFifoCost = $actualConsumedQty * $fifoCost;
                $standardCost = $standardExpectedQty * $fifoCost;
                $costVariance = $varianceQty * $fifoCost;

                $status = ConsumptionLog::STATUS_NORMAL;
                if ($varianceQty > 0.05 * $standardExpectedQty) {
                    $status = ConsumptionLog::STATUS_OVER_CONSUMPTION_ALERT;
                } elseif ($varianceQty < -0.01) {
                    $status = ConsumptionLog::STATUS_SAVED;
                }

                $logNumber = 'CONS-'.date('Ymd').'-'.rand(1000, 9999);

                $logs[] = ConsumptionLog::create([
                    'log_number' => $logNumber,
                    'hospital_id' => $hospitalId,
                    'recipe_id' => $recipe->id,
                    'consumption_date' => $consumptionDate,
                    'actual_served_meals_count' => $mealCount,
                    'product_id' => $productId,
                    'standard_expected_qty' => $standardExpectedQty,
                    'actual_consumed_qty' => $actualConsumedQty,
                    'variance_qty' => $varianceQty,
                    'actual_fifo_cost' => $actualFifoCost,
                    'standard_cost' => $standardCost,
                    'cost_variance' => $costVariance,
                    'status' => $status,
                    'discrepancy_justification' => $item['discrepancy_justification'] ?? null,
                    'verified_by_costing' => $header['verified_by_costing'] ?? auth()->id(),
                ]);
            }

            return $logs;
        });
    }
}
