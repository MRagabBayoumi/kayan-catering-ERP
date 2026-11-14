<?php

namespace App\Http\Controllers;

use App\Models\DispatchOrder;
use App\Models\Hospital;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CateringLogisticsService;
use App\Services\KayanErpSyncService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function __construct(
        protected CateringLogisticsService $logisticsService
    ) {}

    /**
     * Stock balances and FIFO batches view.
     */
    public function index(Request $request): View
    {
        Gate::authorize('manage-warehouse');

        $selectedWarehouseId = $request->query('warehouse_id');

        $warehouses = Warehouse::where('is_active', true)->get();

        $batchesQuery = InventoryBatch::with('product', 'warehouse')
            ->where('is_exhausted', false)
            ->where('remaining_quantity', '>', 0)
            ->availableFEFO();

        if ($selectedWarehouseId) {
            $batchesQuery->where('warehouse_id', $selectedWarehouseId);
        }

        $batches = $batchesQuery->paginate(15)->withQueryString();

        $stockBalances = StockBalance::with('product', 'warehouse')
            ->when($selectedWarehouseId, fn ($q) => $q->where('warehouse_id', $selectedWarehouseId))
            ->get();

        $today = Carbon::today();
        $criticalBatchesCount = InventoryBatch::where('is_exhausted', false)
            ->where('remaining_quantity', '>', 0)
            ->where('expiry_date', '<=', $today->copy()->addDays(7))
            ->count();

        $barcodedProductsCount = Product::whereNotNull('barcode')->where('barcode', '!=', '')->count();

        return view('warehouse.index', [
            'title' => 'المخازن وتشغيلات الـ FIFO / FEFO',
            'warehouses' => $warehouses,
            'batches' => $batches,
            'stockBalances' => $stockBalances,
            'selectedWarehouseId' => $selectedWarehouseId,
            'criticalBatchesCount' => $criticalBatchesCount,
            'barcodedProductsCount' => $barcodedProductsCount,
        ]);
    }

    /**
     * FEFO Expiry Dashboard & Early Warning Engine.
     */
    public function expiry(Request $request): View
    {
        Gate::authorize('manage-warehouse');

        $warehouses = Warehouse::where('is_active', true)->get();
        $selectedWarehouseId = $request->query('warehouse_id');
        $selectedStatus = $request->query('status', 'all');
        $search = trim((string) $request->query('search', ''));

        $today = Carbon::today();
        $criticalThreshold = $today->copy()->addDays(7);
        $warningThreshold = $today->copy()->addDays(30);

        // Global Active Batches Query
        $baseActiveBatches = InventoryBatch::with('product', 'warehouse')
            ->where('is_exhausted', false)
            ->where('remaining_quantity', '>', 0);

        // Summary KPI Metrics
        $totalActiveBatches = (clone $baseActiveBatches)->count();

        $expiredBatches = (clone $baseActiveBatches)
            ->where('expiry_date', '<', $today)
            ->get();

        $criticalBatches = (clone $baseActiveBatches)
            ->where('expiry_date', '>=', $today)
            ->where('expiry_date', '<=', $criticalThreshold)
            ->get();

        $warningBatches = (clone $baseActiveBatches)
            ->where('expiry_date', '>', $criticalThreshold)
            ->where('expiry_date', '<=', $warningThreshold)
            ->get();

        $safeBatchesCount = (clone $baseActiveBatches)
            ->where(function ($q) use ($warningThreshold) {
                $q->where('expiry_date', '>', $warningThreshold)
                    ->orWhereNull('expiry_date');
            })
            ->count();

        $expiredValue = (float) $expiredBatches->sum(fn ($b) => (float) $b->remaining_quantity * (float) $b->cost_price);
        $criticalValue = (float) $criticalBatches->sum(fn ($b) => (float) $b->remaining_quantity * (float) $b->cost_price);
        $warningValue = (float) $warningBatches->sum(fn ($b) => (float) $b->remaining_quantity * (float) $b->cost_price);
        $atRiskValue = $criticalValue + $warningValue;

        // Filtered Query for Expiry Table
        $query = (clone $baseActiveBatches);

        if ($selectedWarehouseId) {
            $query->where('warehouse_id', $selectedWarehouseId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('batch_number', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%")
                            ->orWhere('barcode', 'like', "%{$search}%");
                    });
            });
        }

        if ($selectedStatus === 'expired') {
            $query->where('expiry_date', '<', $today);
        } elseif ($selectedStatus === 'critical') {
            $query->where('expiry_date', '>=', $today)->where('expiry_date', '<=', $criticalThreshold);
        } elseif ($selectedStatus === 'warning') {
            $query->where('expiry_date', '>', $criticalThreshold)->where('expiry_date', '<=', $warningThreshold);
        } elseif ($selectedStatus === 'safe') {
            $query->where(function ($q) use ($warningThreshold) {
                $q->where('expiry_date', '>', $warningThreshold)->orWhereNull('expiry_date');
            });
        }

        $batches = $query->availableFEFO()->paginate(20)->withQueryString();

        return view('warehouse.expiry', [
            'title' => 'محرك الصلاحيات وتنبيهات FEFO الذكية',
            'warehouses' => $warehouses,
            'selectedWarehouseId' => $selectedWarehouseId,
            'selectedStatus' => $selectedStatus,
            'search' => $search,
            'batches' => $batches,
            'kpis' => [
                'total_active' => $totalActiveBatches,
                'expired_count' => $expiredBatches->count(),
                'expired_value' => $expiredValue,
                'critical_count' => $criticalBatches->count(),
                'critical_value' => $criticalValue,
                'warning_count' => $warningBatches->count(),
                'warning_value' => $warningValue,
                'safe_count' => $safeBatchesCount,
                'at_risk_value' => $atRiskValue,
            ],
        ]);
    }

    /**
     * API: Suggest recipes in the kitchen to consume a batch nearing expiry.
     */
    public function suggestBatchConsumption(InventoryBatch $batch): JsonResponse
    {
        Gate::authorize('manage-warehouse');

        $batch->load('product');
        $recipes = Recipe::with(['ingredients' => function ($q) use ($batch) {
            $q->where('product_id', $batch->product_id);
        }, 'mealType', 'hospital'])
            ->whereHas('ingredients', function ($q) use ($batch) {
                $q->where('product_id', $batch->product_id);
            })
            ->where('is_active', true)
            ->get();

        $suggestions = [];
        $remainingQty = (float) $batch->remaining_quantity;

        foreach ($recipes as $recipe) {
            $ing = $recipe->ingredients->first();
            $rawPerMeal = (float) ($ing?->raw_quantity_per_meal ?? 0);
            $mealsPossible = $rawPerMeal > 0 ? (int) floor($remainingQty / $rawPerMeal) : 0;

            $suggestions[] = [
                'recipe_id' => $recipe->id,
                'recipe_code' => $recipe->recipe_code,
                'recipe_name' => $recipe->name,
                'meal_type' => $recipe->mealType?->name ?? 'وجبة عامة',
                'hospital_name' => $recipe->hospital?->name ?? 'كافة المستشفيات',
                'raw_per_meal' => $rawPerMeal,
                'unit' => $ing?->unit ?? $batch->product?->unit ?? 'كجم',
                'meals_possible' => $mealsPossible,
            ];
        }

        return response()->json([
            'success' => true,
            'batch' => [
                'id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'product_name' => $batch->product?->name,
                'remaining_quantity' => $remainingQty,
                'unit' => $batch->product?->unit ?? 'كجم',
                'days_until_expiry' => $batch->days_until_expiry,
                'expiry_date' => $batch->expiry_date?->format('Y-m-d'),
            ],
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * API: Calculate Kitchen Recipe BOM & Waste Factor for Dispatch.
     */
    public function calculateRecipeBom(Request $request, Recipe $recipe): JsonResponse
    {
        Gate::authorize('create-dispatch');

        $mealsCount = max(1, (int) $request->query('meals_count', 100));
        $warehouseId = $request->query('warehouse_id');

        $recipe->load('ingredients.product');

        $items = [];
        foreach ($recipe->ingredients as $ingredient) {
            $product = $ingredient->product;
            if (! $product) {
                continue;
            }

            $rawPerMeal = (float) $ingredient->raw_quantity_per_meal;
            $netQty = round($rawPerMeal * $mealsCount, 4);
            $wastePct = (float) ($product->acceptable_waste_pct ?? 0.00);
            $wasteBufferQty = round($netQty * ($wastePct / 100), 4);
            $recommendedDispatchQty = round($netQty + $wasteBufferQty, 4);

            $availableStock = 0.00;
            if ($warehouseId) {
                $availableStock = (float) InventoryBatch::where('product_id', $product->id)
                    ->where('warehouse_id', $warehouseId)
                    ->where('is_exhausted', false)
                    ->sum('remaining_quantity');
            } else {
                $availableStock = (float) InventoryBatch::where('product_id', $product->id)
                    ->where('is_exhausted', false)
                    ->sum('remaining_quantity');
            }

            $items[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'barcode' => $product->barcode,
                'unit' => $ingredient->unit ?: $product->unit,
                'raw_per_meal' => $rawPerMeal,
                'net_qty' => $netQty,
                'acceptable_waste_pct' => $wastePct,
                'waste_buffer_qty' => $wasteBufferQty,
                'recommended_dispatch_qty' => $recommendedDispatchQty,
                'available_stock' => $availableStock,
                'is_sufficient' => $availableStock >= $recommendedDispatchQty,
            ];
        }

        return response()->json([
            'success' => true,
            'recipe' => [
                'id' => $recipe->id,
                'name' => $recipe->name,
                'recipe_code' => $recipe->recipe_code,
                'meals_count' => $mealsCount,
            ],
            'items' => $items,
        ]);
    }

    /**
     * Trigger synchronization of barcodes from KAYAN ERP.
     */
    public function syncBarcodes(Request $request, KayanErpSyncService $syncService): RedirectResponse
    {
        Gate::authorize('manage-warehouse');

        try {
            $importNew = (bool) $request->input('import_new', false);
            $result = $syncService->syncBarcodes($importNew);

            $msg = "تمت المزامنة بنجاح! تم فحص {$result['total_erp']} صنف بالـ ERP، وتحديث باركود {$result['updated_barcodes']} صنف محلياً، واستيراد {$result['imported_products']} صنف جديد.";

            return back()->with('success', $msg);
        } catch (\Exception $e) {
            return back()->with('error', "فشل استيراد الباركود من KAYAN ERP: {$e->getMessage()}");
        }
    }

    /**
     * Dispatch orders list.
     */
    public function dispatches(): View
    {
        Gate::authorize('manage-warehouse');

        $dispatches = DispatchOrder::with('hospital', 'sourceWarehouse', 'transitWarehouse', 'driver', 'storekeeper', 'recipe')
            ->latest()
            ->paginate(15);

        return view('warehouse.dispatches.index', [
            'title' => 'أذون التحميل والصرف لسيارات المناديب',
            'dispatches' => $dispatches,
        ]);
    }

    /**
     * Create dispatch order form.
     */
    public function createDispatch(): View
    {
        Gate::authorize('create-dispatch');

        $hospitals = Hospital::where('is_active', true)->where('contract_status', Hospital::CONTRACT_ACTIVE)->get();
        $sourceWarehouses = Warehouse::where('type', '!=', Warehouse::TYPE_VAN_TRANSIT)->where('is_active', true)->get();
        $transitWarehouses = Warehouse::where('type', Warehouse::TYPE_VAN_TRANSIT)->where('is_active', true)->get();
        $drivers = User::where('role', User::ROLE_DRIVER)->where('is_active', true)->get();
        $products = Product::where('is_active', true)->get();
        $recipes = Recipe::with('ingredients.product')->where('is_active', true)->get();

        return view('warehouse.dispatches.create', [
            'title' => 'إصدار إذن تحميل وصرف لسيارة المندوب ومطابخ المستشفيات',
            'hospitals' => $hospitals,
            'sourceWarehouses' => $sourceWarehouses,
            'transitWarehouses' => $transitWarehouses,
            'drivers' => $drivers,
            'products' => $products,
            'recipes' => $recipes,
        ]);
    }

    /**
     * Store dispatch order.
     */
    public function storeDispatch(Request $request): RedirectResponse
    {
        Gate::authorize('create-dispatch');

        $validated = $request->validate([
            'hospital_id' => 'required|exists:hospitals,id',
            'recipe_id' => 'nullable|exists:recipes,id',
            'planned_meals_count' => 'nullable|integer|min:1',
            'source_warehouse_id' => 'required|exists:warehouses,id',
            'transit_warehouse_id' => 'required|exists:warehouses,id',
            'driver_id' => 'required|exists:users,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.planned_qty' => 'required|numeric|min:0.01',
            'items.*.buffer_qty' => 'nullable|numeric|min:0',
        ]);

        try {
            $dispatch = $this->logisticsService->createDispatchOrder([
                'hospital_id' => $validated['hospital_id'],
                'recipe_id' => $validated['recipe_id'] ?? null,
                'planned_meals_count' => $validated['planned_meals_count'] ?? null,
                'source_warehouse_id' => $validated['source_warehouse_id'],
                'transit_warehouse_id' => $validated['transit_warehouse_id'],
                'driver_id' => $validated['driver_id'],
                'storekeeper_id' => auth()->id(),
                'notes' => $validated['notes'] ?? null,
            ], $validated['items']);

            return redirect()->route('warehouse.dispatches')
                ->with('success', "تم بنجاح إصدار إذن الصرف والتحميل رقم [{$dispatch->dispatch_number}] ونقل البضاعة لعهدة سيارة السائق بنظام FEFO/FIFO.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
