<?php

namespace App\Http\Controllers;

use App\Models\DispatchOrder;
use App\Models\DriverSettlement;
use App\Services\CateringLogisticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DriverSettlementController extends Controller
{
    public function __construct(
        protected CateringLogisticsService $logisticsService
    ) {}

    /**
     * List settlements.
     */
    public function index(): View
    {
        Gate::authorize('manage-settlements');

        $settlements = DriverSettlement::with('dispatchOrder.hospital', 'driver', 'accountant')
            ->latest()
            ->paginate(15);

        return view('warehouse.settlements.index', [
            'title' => 'تسويات رحلات المناديب ومخرجات الـ ERP',
            'settlements' => $settlements,
        ]);
    }

    /**
     * Settle trip form for accountant.
     */
    public function create(Request $request): View
    {
        Gate::authorize('manage-settlements');

        $dispatchOrderId = $request->query('dispatch_id');
        $dispatch = DispatchOrder::with('hospital', 'driver', 'items.product', 'deliveryReceipt.items')->findOrFail($dispatchOrderId);

        return view('warehouse.settlements.create', [
            'title' => 'اعتماد تسوية رحلة توريد المندوب: '.$dispatch->driver->name,
            'dispatch' => $dispatch,
        ]);
    }

    /**
     * Store driver settlement.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-settlements');

        $validated = $request->validate([
            'dispatch_order_id' => 'required|exists:dispatch_orders,id',
            'receiving_officer_name' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.dispatched_qty' => 'required|numeric',
            'items.*.actual_delivered_qty' => 'required|numeric|min:0',
            'items.*.returned_qty' => 'required|numeric|min:0',
            'items.*.extra_requested_qty' => 'nullable|numeric|min:0',
            'items.*.rejected_qty' => 'nullable|numeric|min:0',
            'items.*.rejection_reason' => 'nullable|string',
        ]);

        try {
            // 1. Record delivery receipt
            $receipt = $this->logisticsService->recordDeliveryReceipt(
                $validated['dispatch_order_id'],
                ['receiving_officer_name' => $validated['receiving_officer_name']],
                $validated['items']
            );

            // 2. Perform settlement & generate ERP outputs
            $settlement = $this->logisticsService->settleDriverTrip(
                $validated['dispatch_order_id'],
                $receipt->id,
                auth()->id(),
                $validated['notes'] ?? null
            );

            return redirect()->route('warehouse.settlements.index')
                ->with('success', "تم بنجاح اعتماد تسوية الرحلة رقم [{$settlement->settlement_number}] وتوليد قيد الـ ERP رقم [{$settlement->erp_journal_voucher}].");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
