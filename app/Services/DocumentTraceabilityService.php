<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\DeliveryReceipt;
use App\Models\DispatchOrder;
use App\Models\DriverSettlement;
use App\Models\GoodsReceiptNote;
use App\Models\JournalEntry;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\SiteKitchenIssue;
use App\Models\SupplyOrder;

class DocumentTraceabilityService
{
    /**
     * Build comprehensive forward and backward traceability chain for any business document.
     *
     * @return array<string, mixed>
     */
    public function trace(string $type, int $id): array
    {
        return match (strtolower($type)) {
            'contract' => $this->traceContract($id),
            'supply_order' => $this->traceSupplyOrder($id),
            'dispatch_order' => $this->traceDispatchOrder($id),
            'delivery_receipt' => $this->traceDeliveryReceipt($id),
            'settlement', 'driver_settlement' => $this->traceSettlement($id),
            'purchase_request' => $this->tracePurchaseRequest($id),
            'purchase_order' => $this->tracePurchaseOrder($id),
            'goods_receipt_note', 'grn' => $this->traceGrn($id),
            default => [
                'type' => $type,
                'id' => $id,
                'document' => null,
                'upstream' => [],
                'downstream' => [],
            ],
        };
    }

    protected function traceContract(int $id): array
    {
        $contract = Contract::with(['hospital.costCenter', 'tender', 'mealPrices.mealType'])->findOrFail($id);

        $supplyOrders = SupplyOrder::where('hospital_id', $contract->hospital_id)->latest()->take(10)->get();
        $dispatches = DispatchOrder::where('hospital_id', $contract->hospital_id)->latest()->take(10)->get();
        $receipts = DeliveryReceipt::where('hospital_id', $contract->hospital_id)->latest()->take(10)->get();
        $settlements = DriverSettlement::whereHas('dispatchOrder', fn ($q) => $q->where('hospital_id', $contract->hospital_id))->latest()->take(10)->get();
        $kitchenIssues = SiteKitchenIssue::where('hospital_id', $contract->hospital_id)->latest()->take(10)->get();
        $journalEntries = JournalEntry::where('source_type', 'hospital_revenue')->where('source_id', $contract->id)->latest()->get();

        return [
            'type' => 'عقد مستشفى معتمد',
            'doc_number' => $contract->contract_number,
            'status' => $contract->status,
            'title' => "عقد: {$contract->hospital->name}",
            'document' => $contract,
            'upstream' => [
                ['label' => 'المستشفى المتعاقد', 'value' => $contract->hospital->name, 'route' => route('sales.hospitals.index')],
                ['label' => 'المناقصة المرتبطة', 'value' => $contract->tender?->title ?? 'إسناد مباشر', 'route' => route('sales.tenders.index')],
                ['label' => 'مركز التكلفة المالي', 'value' => $contract->hospital->costCenter?->name ?? 'مركز تكلفة المستشفى', 'route' => route('finance.profitability.index', ['hospital_id' => $contract->hospital_id])],
            ],
            'downstream' => [
                'supply_orders' => $supplyOrders,
                'dispatch_orders' => $dispatches,
                'delivery_receipts' => $receipts,
                'settlements' => $settlements,
                'kitchen_issues' => $kitchenIssues,
                'journal_entries' => $journalEntries,
            ],
        ];
    }

    protected function traceSupplyOrder(int $id): array
    {
        $so = SupplyOrder::with(['hospital.activeContract', 'dispatchOrders.driver', 'items.product'])->findOrFail($id);

        return [
            'type' => 'أمر توريد مستشفى',
            'doc_number' => $so->order_number,
            'status' => $so->status,
            'title' => "أمر توريد: {$so->hospital->name}",
            'document' => $so,
            'upstream' => [
                ['label' => 'المستشفى', 'value' => $so->hospital->name, 'route' => route('sales.hospitals.index')],
                ['label' => 'العقد النشط', 'value' => $so->hospital->activeContract?->contract_number ?? 'عقد عام', 'route' => route('sales.contracts.index')],
            ],
            'downstream' => [
                'dispatch_orders' => $so->dispatchOrders,
                'delivery_receipts' => DeliveryReceipt::whereIn('dispatch_order_id', $so->dispatchOrders->pluck('id'))->get(),
            ],
        ];
    }

    protected function traceDispatchOrder(int $id): array
    {
        $disp = DispatchOrder::with(['hospital', 'driver', 'sourceWarehouse', 'transitWarehouse', 'supplyOrder', 'deliveryReceipt.items', 'settlement'])->findOrFail($id);

        return [
            'type' => 'إذن تحميل وصرف لسيارة المندوب',
            'doc_number' => $disp->dispatch_number,
            'status' => $disp->status,
            'title' => "إذن صرف: {$disp->hospital->name}",
            'document' => $disp,
            'upstream' => [
                ['label' => 'المستشفى المستهدف', 'value' => $disp->hospital->name, 'route' => route('sales.hospitals.index')],
                ['label' => 'المخزن المصدر', 'value' => $disp->sourceWarehouse->name ?? '-', 'route' => route('warehouse.index')],
                ['label' => 'عهدة السيارة', 'value' => $disp->transitWarehouse->name ?? '-', 'route' => route('mobile.driver.trips')],
                ['label' => 'السائق المسؤول', 'value' => $disp->driver->name ?? '-', 'route' => route('mobile.driver.trips')],
            ],
            'downstream' => [
                'delivery_receipt' => $disp->deliveryReceipt,
                'settlement' => $disp->settlement,
            ],
        ];
    }

    protected function traceDeliveryReceipt(int $id): array
    {
        $rcpt = DeliveryReceipt::with(['hospital', 'driver', 'dispatchOrder.sourceWarehouse', 'dispatchOrder.settlement', 'items.product'])->findOrFail($id);

        return [
            'type' => 'إشعار تسليم وتوريد (POD)',
            'doc_number' => $rcpt->receipt_number,
            'status' => $rcpt->status,
            'title' => "إشعار تسليم: {$rcpt->hospital->name}",
            'document' => $rcpt,
            'upstream' => [
                ['label' => 'إذن التحميل والصرف', 'value' => $rcpt->dispatchOrder?->dispatch_number ?? '-', 'route' => route('warehouse.dispatches')],
                ['label' => 'المستشفى المستلم', 'value' => $rcpt->hospital->name, 'route' => route('sales.hospitals.index')],
                ['label' => 'السائق المورّد', 'value' => $rcpt->driver->name ?? '-', 'route' => route('mobile.driver.trips')],
            ],
            'downstream' => [
                'settlement' => $rcpt->dispatchOrder?->settlement,
                'hospital_warehouse' => $rcpt->hospital->siteWarehouse,
            ],
        ];
    }

    protected function traceSettlement(int $id): array
    {
        $set = DriverSettlement::with(['dispatchOrder.hospital', 'deliveryReceipt', 'driver', 'accountant'])->findOrFail($id);

        return [
            'type' => 'محضر تسوية عهدة السائق',
            'doc_number' => $set->settlement_number,
            'status' => $set->status,
            'title' => "تسوية عهدة: {$set->driver->name}",
            'document' => $set,
            'upstream' => [
                ['label' => 'إذن الصرف والتحميل', 'value' => $set->dispatchOrder?->dispatch_number ?? '-', 'route' => route('warehouse.dispatches')],
                ['label' => 'إشعار التسليم POD', 'value' => $set->deliveryReceipt?->receipt_number ?? '-', 'route' => route('mobile.receipt.show', $set->delivery_receipt_id)],
                ['label' => 'قيد اليومية التلقائي', 'value' => $set->erp_journal_voucher, 'route' => route('finance.journal.index')],
            ],
            'downstream' => [],
        ];
    }

    protected function tracePurchaseRequest(int $id): array
    {
        $pr = PurchaseRequest::with(['warehouse', 'requester', 'approver', 'items.product', 'purchaseOrder.supplier', 'purchaseOrder.goodsReceiptNotes'])->findOrFail($id);

        return [
            'type' => 'طلب شراء واحتياج تمويني',
            'doc_number' => $pr->pr_number,
            'status' => $pr->status,
            'title' => "طلب شراء: {$pr->pr_number}",
            'document' => $pr,
            'upstream' => [
                ['label' => 'المخزن الطالب', 'value' => $pr->warehouse->name ?? '-', 'route' => route('warehouse.index')],
                ['label' => 'طالب الشراء', 'value' => $pr->requester->name ?? 'النظام', 'route' => '#'],
            ],
            'downstream' => [
                'purchase_order' => $pr->purchaseOrder,
                'goods_receipt_notes' => $pr->purchaseOrder?->goodsReceiptNotes,
            ],
        ];
    }

    protected function tracePurchaseOrder(int $id): array
    {
        $po = PurchaseOrder::with(['supplier', 'warehouse', 'purchaseRequest', 'goodsReceiptNotes.items', 'creator', 'items.product'])->findOrFail($id);

        return [
            'type' => 'أمر شراء معمد للمورد',
            'doc_number' => $po->po_number,
            'status' => $po->status,
            'title' => "أمر شراء: {$po->po_number}",
            'document' => $po,
            'upstream' => [
                ['label' => 'طلب الشراء الأصلي', 'value' => $po->purchaseRequest?->pr_number ?? 'طلب مباشر', 'route' => $po->purchase_request_id ? route('procurement.requests.show', $po->purchase_request_id) : '#'],
                ['label' => 'المورد المعتمد', 'value' => $po->supplier->name ?? '-', 'route' => route('procurement.suppliers.index')],
                ['label' => 'المخزن المستلم', 'value' => $po->warehouse->name ?? '-', 'route' => route('warehouse.index')],
            ],
            'downstream' => [
                'goods_receipt_notes' => $po->goodsReceiptNotes,
            ],
        ];
    }

    protected function traceGrn(int $id): array
    {
        $grn = GoodsReceiptNote::with(['supplier', 'warehouse', 'purchaseOrder', 'receiver', 'items.product'])->findOrFail($id);
        $journalEntry = JournalEntry::where('source_type', 'goods_receipt')->where('source_id', $grn->id)->first();

        return [
            'type' => 'إذن فحص واستلام مخزني (GRN)',
            'doc_number' => $grn->grn_number,
            'status' => $grn->status,
            'title' => "إذن فحص: {$grn->grn_number}",
            'document' => $grn,
            'upstream' => [
                ['label' => 'أمر الشراء المرتبط', 'value' => $grn->purchaseOrder?->po_number ?? 'بدون أمر شراء', 'route' => route('procurement.orders.index')],
                ['label' => 'المورد', 'value' => $grn->supplier->name ?? '-', 'route' => route('procurement.suppliers.index')],
                ['label' => 'المخزن المستلم', 'value' => $grn->warehouse->name ?? '-', 'route' => route('warehouse.index')],
            ],
            'downstream' => [
                'journal_entry' => $journalEntry,
            ],
        ];
    }
}
