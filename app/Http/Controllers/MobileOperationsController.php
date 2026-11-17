<?php

namespace App\Http\Controllers;

use App\Models\DeliveryReceipt;
use App\Models\DispatchOrder;
use App\Models\Hospital;
use App\Models\User;
use App\Services\CateringLogisticsService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MobileOperationsController extends Controller
{
    public function __construct(
        protected CateringLogisticsService $logisticsService
    ) {}

    /**
     * Mobile Operations Hub (بوابة العمليات الميدانية للموبايل).
     */
    public function index(): View
    {
        $user = auth()->user();

        $activeDispatchesCount = DispatchOrder::where('status', DispatchOrder::STATUS_IN_TRANSIT)
            ->when($user->role === User::ROLE_DRIVER, fn ($q) => $q->where('driver_id', $user->id))
            ->count();

        $pendingReceivingCount = DispatchOrder::where('status', DispatchOrder::STATUS_IN_TRANSIT)->count();
        $recentReceiptsCount = DeliveryReceipt::whereDate('created_at', Carbon::today())->count();

        return view('mobile.index', [
            'title' => 'بوابة العمليات الميدانية المتنقلة (Mobile Ops)',
            'activeDispatchesCount' => $activeDispatchesCount,
            'pendingReceivingCount' => $pendingReceivingCount,
            'recentReceiptsCount' => $recentReceiptsCount,
            'user' => $user,
        ]);
    }

    /**
     * Driver Dispatches & Mobile Custody Screen.
     */
    public function driverTrips(): View
    {
        $user = auth()->user();

        $trips = DispatchOrder::with(['hospital', 'sourceWarehouse', 'transitWarehouse', 'items.product', 'deliveryReceipt'])
            ->when($user->role === User::ROLE_DRIVER, fn ($q) => $q->where('driver_id', $user->id))
            ->whereIn('status', [DispatchOrder::STATUS_IN_TRANSIT, DispatchOrder::STATUS_PREPARED, DispatchOrder::STATUS_DELIVERED])
            ->latest()
            ->paginate(10);

        return view('mobile.driver', [
            'title' => 'رحلات التوريد والعهدة الميدانية للسائق',
            'trips' => $trips,
            'user' => $user,
        ]);
    }

    /**
     * Hospital Storekeeper Mobile Receiving Screen (استلام الموقع وفحص الأصناف).
     */
    public function hospitalReceiving(int $dispatchId): View
    {
        $dispatch = DispatchOrder::with(['hospital', 'driver', 'items.product', 'transitWarehouse'])->findOrFail($dispatchId);

        return view('mobile.receiving', [
            'title' => "فحص واستلام التوريد: {$dispatch->hospital->name}",
            'dispatch' => $dispatch,
        ]);
    }

    /**
     * Submit Hospital Receiving Confirmation (نقل العهدة لمخزن الموقع وإصدار POD).
     */
    public function submitHospitalReceiving(Request $request, int $dispatchId): RedirectResponse
    {
        $validated = $request->validate([
            'receiving_officer_name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.dispatched_qty' => ['required', 'numeric'],
            'items.*.actual_delivered_qty' => ['required', 'numeric', 'min:0'],
            'items.*.returned_qty' => ['nullable', 'numeric', 'min:0'],
            'items.*.rejected_qty' => ['nullable', 'numeric', 'min:0'],
            'items.*.rejection_reason' => ['nullable', 'string'],
        ]);

        $receipt = $this->logisticsService->recordDeliveryReceipt(
            $dispatchId,
            [
                'receiving_officer_name' => $validated['receiving_officer_name'],
                'notes' => $validated['notes'] ?? null,
            ],
            $validated['items']
        );

        return redirect()->route('mobile.receipt.show', $receipt->id)
            ->with('status', 'success')
            ->with('message', "تم بنجاح اعتماد استلام التوريد ونقل الأصناف للمخزن الفرعي بالموقع وإصدار إشعار التسليم [{$receipt->receipt_number}].");
    }

    /**
     * Display generated Proof of Delivery (POD) on mobile.
     */
    public function showReceipt(int $receiptId): View
    {
        $receipt = DeliveryReceipt::with(['hospital', 'driver', 'dispatchOrder.sourceWarehouse', 'items.product'])->findOrFail($receiptId);

        return view('mobile.receipt-view', [
            'title' => "إشعار تسليم وتوريد: {$receipt->receipt_number}",
            'receipt' => $receipt,
        ]);
    }
}
