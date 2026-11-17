<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\DailySiteCensus;
use App\Models\DeliveryReceipt;
use App\Models\DispatchOrder;
use App\Models\DriverSettlement;
use App\Models\GoodsReceiptNote;
use App\Models\PurchaseOrder;
use Illuminate\View\View;

class PrintDocumentsController extends Controller
{
    /**
     * Print formal Purchase Order (PO).
     */
    public function purchaseOrder(int $id): View
    {
        $po = PurchaseOrder::with(['supplier', 'warehouse', 'items.product', 'creator', 'approver'])->findOrFail($id);
        $company = CompanySetting::first();

        return view('prints.purchase-order', [
            'title' => "أمر شراء معمد: {$po->po_number}",
            'po' => $po,
            'company' => $company,
        ]);
    }

    /**
     * Print Goods Receipt Note (GRN) Inspection Document.
     */
    public function goodsReceipt(int $id): View
    {
        $grn = GoodsReceiptNote::with(['supplier', 'warehouse', 'purchaseOrder', 'items.product', 'receiver', 'inspector'])->findOrFail($id);
        $company = CompanySetting::first();

        return view('prints.goods-receipt', [
            'title' => "إذن فحص واستلام توريد: {$grn->grn_number}",
            'grn' => $grn,
            'company' => $company,
        ]);
    }

    /**
     * Print Dispatch & Vehicle Loading Document.
     */
    public function dispatchOrder(int $id): View
    {
        $dispatch = DispatchOrder::with(['hospital', 'driver', 'sourceWarehouse', 'transitWarehouse', 'items.product', 'storekeeper'])->findOrFail($id);
        $company = CompanySetting::first();

        return view('prints.dispatch-order', [
            'title' => "إذن تحميل وصرف لسيارة المندوب: {$dispatch->dispatch_number}",
            'dispatch' => $dispatch,
            'company' => $company,
        ]);
    }

    /**
     * Print Official Delivery Receipt & Proof of Delivery (POD).
     */
    public function deliveryReceipt(int $id): View
    {
        $receipt = DeliveryReceipt::with(['hospital', 'driver', 'dispatchOrder.sourceWarehouse', 'items.product'])->findOrFail($id);
        $company = CompanySetting::first();

        return view('prints.delivery-receipt', [
            'title' => "إشعار تسليم وتوريد رسمي: {$receipt->receipt_number}",
            'receipt' => $receipt,
            'company' => $company,
        ]);
    }

    /**
     * Print Driver Trip Custody Settlement Reconciliation Sheet.
     */
    public function driverSettlement(int $id): View
    {
        $settlement = DriverSettlement::with(['dispatchOrder.hospital', 'deliveryReceipt.items.product', 'driver', 'accountant'])->findOrFail($id);
        $company = CompanySetting::first();

        return view('prints.driver-settlement', [
            'title' => "محضر تسوية عهدة رحلة السائق: {$settlement->settlement_number}",
            'settlement' => $settlement,
            'company' => $company,
        ]);
    }

    /**
     * Print Patient Diet Meal Tray Thermal Labels from Daily Census.
     */
    public function dietLabels(int $censusId): View
    {
        $census = DailySiteCensus::with(['hospital', 'items.dietSchedule'])->findOrFail($censusId);
        $company = CompanySetting::first();

        return view('prints.diet-labels', [
            'title' => "ملصقات وجبات النزلاء والحميات: {$census->hospital->name}",
            'census' => $census,
            'company' => $company,
        ]);
    }
}
