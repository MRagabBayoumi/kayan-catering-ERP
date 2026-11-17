<?php

namespace App\Http\Controllers;

use App\Models\Hospital;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeVersion;
use App\Models\Warehouse;
use App\Services\ProcurementService;
use App\Services\RationEngineService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RationVersionController extends Controller
{
    public function __construct(
        protected RationEngineService $rationService,
        protected ProcurementService $procurementService
    ) {}

    /**
     * Show Recipe Versions and history.
     */
    public function versions(Recipe $recipe): View
    {
        Gate::authorize('manage-costing');

        $recipe->load(['mealType', 'hospital']);
        $versions = RecipeVersion::with(['ingredients.product', 'approver'])
            ->where('recipe_id', $recipe->id)
            ->orderBy('version_number', 'desc')
            ->get();

        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('costing.recipes.versions', [
            'title' => 'سجل إصدارات مقنن الوجبة: '.$recipe->name,
            'recipe' => $recipe,
            'versions' => $versions,
            'products' => $products,
        ]);
    }

    /**
     * Store new Recipe Version.
     */
    public function storeVersion(Request $request, Recipe $recipe): RedirectResponse
    {
        Gate::authorize('manage-costing');

        $validated = $request->validate([
            'version_name' => ['required', 'string', 'max:100'],
            'effective_from' => ['required', 'date'],
            'change_reason' => ['required', 'string', 'max:255'],
            'ingredients' => ['required', 'array', 'min:1'],
            'ingredients.*.product_id' => ['required', 'exists:products,id'],
            'ingredients.*.raw_quantity_per_meal' => ['required', 'numeric', 'min:0.0001'],
            'ingredients.*.cooked_yield_quantity' => ['nullable', 'numeric', 'min:0'],
            'ingredients.*.shrinkage_cooking_loss_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'ingredients.*.butchery_yield_pct' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'ingredients.*.standard_unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $header = [
            'version_name' => $validated['version_name'],
            'effective_from' => $validated['effective_from'],
            'change_reason' => $validated['change_reason'],
        ];

        $this->rationService->createRecipeVersion($recipe->id, $header, $validated['ingredients'], auth()->id() ?? 1);

        return redirect()->route('costing.recipes.versions', $recipe)
            ->with('success', 'تم حفظ واعتماد إصدار المقنن القياسي الجديد وتحديث التكلفة المعيارية دون التأثير على التواريخ السابقة.');
    }

    /**
     * MRP Material Requirements Planning Calculator.
     */
    public function mrp(Request $request): View
    {
        Gate::authorize('manage-costing');

        $recipes = Recipe::where('is_active', true)->with('mealType')->get();
        $hospitals = Hospital::where('is_active', true)->get();

        $selectedRecipeId = $request->query('recipe_id', $recipes->first()?->id);
        $mealsCount = (int) $request->query('meals_count', 500);

        $mrpResult = null;
        if ($selectedRecipeId && $mealsCount > 0) {
            $mrpResult = $this->rationService->calculateMaterialRequirements(
                (int) $selectedRecipeId,
                $mealsCount,
                $request->query('effective_date', Carbon::today()->toDateString())
            );
        }

        return view('costing.mrp.index', [
            'title' => 'محرك تخطيط الاحتياجات التموينية (Material Requirement Planning - MRP)',
            'recipes' => $recipes,
            'hospitals' => $hospitals,
            'selectedRecipeId' => $selectedRecipeId,
            'mealsCount' => $mealsCount,
            'mrpResult' => $mrpResult,
        ]);
    }

    /**
     * Create a Purchase Request directly from MRP Shortage Calculation.
     */
    public function createPrFromMrp(Request $request): RedirectResponse
    {
        Gate::authorize('manage-costing');

        $validated = $request->validate([
            'recipe_id' => ['required', 'exists:recipes,id'],
            'meals_count' => ['required', 'integer', 'min:1'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
        ]);

        $mrpResult = $this->rationService->calculateMaterialRequirements(
            (int) $validated['recipe_id'],
            (int) $validated['meals_count']
        );

        $recipe = Recipe::findOrFail((int) $validated['recipe_id']);
        $warehouseId = $validated['warehouse_id'] ?? Warehouse::where('type', Warehouse::TYPE_MAIN)->first()?->id ?? Warehouse::first()?->id;

        $prItems = [];
        foreach ($mrpResult['items'] as $req) {
            $prItems[] = [
                'product_id' => $req['product_id'],
                'quantity' => (float) $req['required_procurement_qty'],
                'unit' => $req['unit'] ?? 'kg',
                'estimated_unit_cost' => (float) ($req['unit_cost'] ?? 0),
                'notes' => "احتياج تمويني ناتج عن MRP للوجبة {$recipe->name} لعدد {$mrpResult['meals_count']} وجبة",
            ];
        }

        $pr = $this->procurementService->createPurchaseRequest([
            'warehouse_id' => $warehouseId,
            'request_date' => Carbon::today(),
            'required_by_date' => Carbon::today()->addDays(2),
            'reason' => "تغطية عجز MRP لوجبة: {$recipe->name} ({$mrpResult['meals_count']} وجبة)",
            'notes' => 'تم إنشاء طلب الشراء تلقائياً من محرك تخطيط الاحتياجات MRP',
        ], $prItems, auth()->id() ?? 1);

        return redirect()->route('procurement.requests.show', $pr->id)
            ->with('status', 'success')
            ->with('message', "تم بنجاح توليد طلب الشراء رقم [{$pr->pr_number}] لتغطية احتياجات MRP.");
    }
}
