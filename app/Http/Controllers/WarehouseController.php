<?php

namespace App\Http\Controllers;

use App\Models\DispatchOrder;
use App\Models\Hospital;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CateringLogisticsService;
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
            ->orderBy('received_at', 'asc');

        if ($selectedWarehouseId) {
            $batchesQuery->where('warehouse_id', $selectedWarehouseId);
        }

        $batches = $batchesQuery->paginate(15)->withQueryString();

        $stockBalances = StockBalance::with('product', 'warehouse')
            ->when($selectedWarehouseId, fn ($q) => $q->where('warehouse_id', $selectedWarehouseId))
            ->get();

        return view('warehouse.index', [
            'title' => 'المخازن وتشغيلات الـ FIFO',
            'warehouses' => $warehouses,
            'batches' => $batches,
            'stockBalances' => $stockBalances,
            'selectedWarehouseId' => $selectedWarehouseId,
        ]);
    }

    /**
     * Dispatch orders list.
     */
    public function dispatches(): View
    {
        Gate::authorize('manage-warehouse');

        $dispatches = DispatchOrder::with('hospital', 'sourceWarehouse', 'transitWarehouse', 'driver', 'storekeeper')
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

        return view('warehouse.dispatches.create', [
            'title' => 'إصدار إذن تحميل وصرف لسيارة المندوب',
            'hospitals' => $hospitals,
            'sourceWarehouses' => $sourceWarehouses,
            'transitWarehouses' => $transitWarehouses,
            'drivers' => $drivers,
            'products' => $products,
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
                'source_warehouse_id' => $validated['source_warehouse_id'],
                'transit_warehouse_id' => $validated['transit_warehouse_id'],
                'driver_id' => $validated['driver_id'],
                'storekeeper_id' => auth()->id(),
                'notes' => $validated['notes'] ?? null,
            ], $validated['items']);

            return redirect()->route('warehouse.dispatches')
                ->with('success', "تم بنجاح إصدار إذن الصرف والتحميل رقم [{$dispatch->dispatch_number}] ونقل البضاعة لعهدة سيارة السائق بنظام FIFO.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
