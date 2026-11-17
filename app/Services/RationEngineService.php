<?php

namespace App\Services;

use App\Models\ExcelImportSession;
use App\Models\Recipe;
use App\Models\RecipeVersion;
use App\Models\RecipeVersionIngredient;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RationEngineService
{
    /**
     * 1. Create a new Recipe Version (حفظ إصدار جديد وتجميد التعديل بأثر رجعي).
     */
    public function createRecipeVersion(int $recipeId, array $header, array $ingredients, int $userId): RecipeVersion
    {
        return DB::transaction(function () use ($recipeId, $header, $ingredients, $userId) {
            $recipe = Recipe::findOrFail($recipeId);

            // Deactivate previous active versions
            RecipeVersion::where('recipe_id', $recipeId)->update(['is_active' => false]);

            $latestVersionNum = (int) RecipeVersion::where('recipe_id', $recipeId)->max('version_number');
            $newVersionNum = $latestVersionNum + 1;

            $totalStandardCost = 0.00;
            foreach ($ingredients as $ing) {
                $rawQty = (float) $ing['raw_quantity_per_meal'];
                $unitCost = (float) ($ing['standard_unit_cost'] ?? 0.00);
                $totalStandardCost += ($rawQty * $unitCost);
            }

            $version = RecipeVersion::create([
                'recipe_id' => $recipeId,
                'version_number' => $newVersionNum,
                'version_name' => $header['version_name'] ?? "إصدار رقم {$newVersionNum}",
                'effective_from' => $header['effective_from'] ?? Carbon::today(),
                'effective_to' => $header['effective_to'] ?? null,
                'total_standard_cost' => $totalStandardCost,
                'is_active' => true,
                'is_locked' => false,
                'approved_by' => $userId,
                'approved_at' => Carbon::now(),
                'change_reason' => $header['change_reason'] ?? 'تعديل وتدقيق مقادير الوجبة',
            ]);

            foreach ($ingredients as $ing) {
                $rawQty = (float) $ing['raw_quantity_per_meal'];
                $cookedYield = (float) ($ing['cooked_yield_quantity'] ?? $rawQty);
                $shrinkagePct = (float) ($ing['shrinkage_cooking_loss_pct'] ?? 0.00);
                $butcheryYieldPct = (float) ($ing['butchery_yield_pct'] ?? 100.00);
                $unitCost = (float) ($ing['standard_unit_cost'] ?? 0.00);

                RecipeVersionIngredient::create([
                    'recipe_version_id' => $version->id,
                    'product_id' => $ing['product_id'],
                    'raw_quantity_per_meal' => $rawQty,
                    'cooked_yield_quantity' => $cookedYield,
                    'shrinkage_cooking_loss_pct' => $shrinkagePct,
                    'butchery_yield_pct' => $butcheryYieldPct,
                    'unit' => $ing['unit'] ?? 'kg',
                    'standard_unit_cost' => $unitCost,
                    'total_ingredient_cost' => $rawQty * $unitCost,
                    'notes' => $ing['notes'] ?? null,
                ]);
            }

            // Update recipe standard_cost_per_meal
            $recipe->update(['standard_cost_per_meal' => $totalStandardCost]);

            return $version->load('ingredients.product');
        });
    }

    /**
     * 2. Calculate MRP Material Demand from Census & Active Recipe Version.
     */
    public function calculateMaterialRequirements(int $recipeId, int $mealsCount, ?string $effectiveDate = null): array
    {
        $targetDate = $effectiveDate ? Carbon::parse($effectiveDate) : Carbon::today();

        // Get effective recipe version for this date
        $version = RecipeVersion::with('ingredients.product')
            ->where('recipe_id', $recipeId)
            ->where('effective_from', '<=', $targetDate)
            ->where(function ($q) use ($targetDate) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $targetDate);
            })
            ->latest('version_number')
            ->first();

        $requirements = [];
        $totalTheoreticalCost = 0.00;

        $ingredients = $version ? $version->ingredients : (Recipe::with('ingredients.product')->find($recipeId)?->ingredients ?? collect());

        foreach ($ingredients as $item) {
            $rawPerMeal = (float) $item->raw_quantity_per_meal;
            $grossDemand = $rawPerMeal * $mealsCount;

            $butcheryYield = (float) ($item->butchery_yield_pct ?? 100);
            $yieldFactor = $butcheryYield > 0 ? ($butcheryYield / 100.0) : 1.0;
            $adjustedGrossRequirement = $grossDemand / $yieldFactor;

            $unitCost = (float) ($item->standard_unit_cost ?? $item->product?->cost_price ?? 0);
            $lineCost = $adjustedGrossRequirement * $unitCost;
            $totalTheoreticalCost += $lineCost;

            $requirements[] = [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'unit' => $item->unit,
                'raw_per_meal' => $rawPerMeal,
                'meals_count' => $mealsCount,
                'theoretical_net_qty' => round($grossDemand, 4),
                'butchery_yield_pct' => $butcheryYield,
                'shrinkage_cooking_loss_pct' => (float) ($item->shrinkage_cooking_loss_pct ?? 0),
                'required_procurement_qty' => round($adjustedGrossRequirement, 4),
                'unit_cost' => $unitCost,
                'total_cost' => round($lineCost, 2),
            ];
        }

        return [
            'recipe_id' => $recipeId,
            'version' => $version,
            'meals_count' => $mealsCount,
            'total_cost' => round($totalTheoreticalCost, 2),
            'cost_per_meal' => $mealsCount > 0 ? round($totalTheoreticalCost / $mealsCount, 2) : 0.00,
            'items' => $requirements,
        ];
    }

    /**
     * 3. Stage and Validate Excel Import Session.
     */
    public function stageExcelData(string $fileName, string $importType, array $rows, int $userId): ExcelImportSession
    {
        $validRows = 0;
        $invalidRows = 0;
        $validationErrors = [];
        $sanitizedData = [];

        foreach ($rows as $index => $row) {
            $rowNum = $index + 1;
            $errors = [];

            if (empty($row['product_code']) && empty($row['product_name'])) {
                $errors[] = "السطر {$rowNum}: كود الصنف أو اسم الصنف مطلوب.";
            }

            if (! isset($row['quantity']) || ! is_numeric($row['quantity']) || (float) $row['quantity'] <= 0) {
                $errors[] = "السطر {$rowNum}: الكمية غير صحيحة أو أقل من الصفر.";
            }

            if (! empty($errors)) {
                $invalidRows++;
                $validationErrors = array_merge($validationErrors, $errors);
            } else {
                $validRows++;
                $sanitizedData[] = $row;
            }
        }

        return ExcelImportSession::create([
            'session_uuid' => (string) Str::uuid(),
            'file_name' => $fileName,
            'import_type' => $importType,
            'total_rows' => count($rows),
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
            'status' => $invalidRows === 0 ? ExcelImportSession::STATUS_VALIDATED : ExcelImportSession::STATUS_STAGED,
            'validation_errors' => $validationErrors,
            'staged_data' => $sanitizedData,
            'uploaded_by' => $userId,
        ]);
    }
}
