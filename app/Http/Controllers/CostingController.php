<?php

namespace App\Http\Controllers;

use App\Models\ConsumptionLog;
use App\Models\MealType;
use App\Models\Product;
use App\Models\Recipe;
use App\Services\CostingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CostingController extends Controller
{
    public function __construct(protected CostingService $costingService) {}

    /**
     * 1. List Standard Recipes (BOM).
     */
    public function indexRecipes(): View
    {
        Gate::authorize('manage-costing');

        $accessibleHospitalIds = auth()->user()->accessibleHospitals()->pluck('id');

        $recipes = Recipe::with(['mealType', 'hospital', 'ingredients.product'])
            ->where(function ($q) use ($accessibleHospitalIds) {
                $q->whereNull('hospital_id')
                    ->orWhereIn('hospital_id', $accessibleHospitalIds);
            })
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('costing.recipes.index', compact('recipes'));
    }

    /**
     * Create Recipe Form.
     */
    public function createRecipe(): View
    {
        Gate::authorize('manage-costing');

        $mealTypes = MealType::where('is_active', true)->get();
        $hospitals = auth()->user()->accessibleHospitals();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('costing.recipes.create', compact('mealTypes', 'hospitals', 'products'));
    }

    /**
     * Store Recipe.
     */
    public function storeRecipe(Request $request): RedirectResponse
    {
        Gate::authorize('manage-costing');

        $request->merge([
            'ingredients' => array_values(array_filter(
                (array) $request->input('ingredients', []),
                fn ($item) => ! empty($item['product_id'])
            )),
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'meal_type_id' => 'required|exists:meal_types,id',
            'hospital_id' => 'nullable|exists:hospitals,id',
            'ingredients' => 'required|array|min:1',
            'ingredients.*.product_id' => 'required|exists:products,id',
            'ingredients.*.raw_quantity_per_meal' => 'required|numeric|min:0.001',
            'ingredients.*.unit' => 'required|string',
            'ingredients.*.standard_unit_cost' => 'nullable|numeric|min:0',
        ]);

        $this->costingService->createRecipe($validated, $validated['ingredients']);

        return redirect()->route('costing.recipes.index')->with('success', 'تم إنشاء المقنن القياسي للوجبة بنجاح.');
    }

    /**
     * 2. Daily Kitchen Order Calculation (حساب طلبيات المطبخ بناء على عدد النزلاء).
     */
    public function dailyOrder(Request $request): View
    {
        Gate::authorize('manage-costing');

        $hospitals = auth()->user()->accessibleHospitals();
        $recipes = Recipe::with('mealType')->where('is_active', true)->get();

        $selectedRecipe = null;
        $patientCount = (int) $request->input('patient_count', 0);
        $requirements = [];

        if ($request->filled('recipe_id') && $patientCount > 0) {
            $selectedRecipe = Recipe::with('ingredients.product')->find($request->recipe_id);
            if ($selectedRecipe) {
                $requirements = $this->costingService->calculateRequirements($selectedRecipe->id, $patientCount);
            }
        }

        return view('costing.daily-order', compact('hospitals', 'recipes', 'selectedRecipe', 'patientCount', 'requirements'));
    }

    /**
     * 3. Kitchen Actual Consumption List & Variance.
     */
    public function consumptionIndex(): View
    {
        Gate::authorize('manage-costing');

        $consumptions = ConsumptionLog::with(['hospital', 'recipe', 'product'])
            ->orderBy('consumption_date', 'desc')
            ->paginate(20);

        return view('costing.consumption.index', compact('consumptions'));
    }

    /**
     * Record actual consumption.
     */
    public function storeConsumption(Request $request): RedirectResponse
    {
        Gate::authorize('manage-costing');

        $validated = $request->validate([
            'hospital_id' => 'required|exists:hospitals,id',
            'recipe_id' => 'required|exists:recipes,id',
            'product_id' => 'required|exists:products,id',
            'actual_served_meals_count' => 'required|integer|min:1',
            'actual_consumed_qty' => 'required|numeric|min:0',
            'consumption_date' => 'required|date',
            'discrepancy_justification' => 'nullable|string',
        ]);

        $this->costingService->recordKitchenConsumption(
            header: [
                'hospital_id' => (int) $validated['hospital_id'],
                'recipe_id' => (int) $validated['recipe_id'],
                'actual_served_meals_count' => (int) $validated['actual_served_meals_count'],
                'consumption_date' => $validated['consumption_date'],
            ],
            consumedItems: [
                [
                    'product_id' => (int) $validated['product_id'],
                    'actual_consumed_qty' => (float) $validated['actual_consumed_qty'],
                    'discrepancy_justification' => $validated['discrepancy_justification'] ?? null,
                ],
            ]
        );

        return redirect()->route('costing.consumption.index')->with('success', 'تم تسجيل استهلاك المطبخ واحتساب نسبة الانحراف بنجاح.');
    }
}
