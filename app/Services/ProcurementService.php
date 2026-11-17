<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\GoodsReceiptNote;
use App\Models\GoodsReceiptNoteItem;
use App\Models\InventoryBatch;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProcurementService
{
    /**
     * 1. Create a Purchase Request (طلب شراء خامات).
     */
    public function createPurchaseRequest(array $data, array $items, int $userId): PurchaseRequest
    {
        return DB::transaction(function () use ($data, $items, $userId) {
            $count = PurchaseRequest::whereDate('created_at', Carbon::today())->count() + 1;
            $prNumber = 'PR-'.date('Ymd').'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);

            $request = PurchaseRequest::create([
                'pr_number' => $prNumber,
                'request_date' => $data['request_date'] ?? Carbon::today(),
                'required_by_date' => $data['required_by_date'] ?? Carbon::today()->addDays(3),
                'warehouse_id' => $data['warehouse_id'],
                'requested_by' => $userId,
                'status' => PurchaseRequest::STATUS_PENDING_APPROVAL,
                'reason' => $data['reason'] ?? 'تغطية عجز احتياج المطبخ التمويني',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                PurchaseRequestItem::create([
                    'purchase_request_id' => $request->id,
                    'product_id' => $item['product_id'],
                    'quantity_requested' => (float) $item['quantity'],
                    'unit' => $item['unit'] ?? 'kg',
                    'estimated_unit_cost' => (float) ($item['estimated_unit_cost'] ?? 0.00),
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $request->load('items.product', 'warehouse');
        });
    }

    /**
     * 2. Approve Purchase Request.
     */
    public function approvePurchaseRequest(int $requestId, int $userId): PurchaseRequest
    {
        $request = PurchaseRequest::findOrFail($requestId);
        $request->update([
            'status' => PurchaseRequest::STATUS_APPROVED,
            'approved_by' => $userId,
            'approved_at' => Carbon::now(),
        ]);

        return $request;
    }

    /**
     * Reject Purchase Request with reason.
     */
    public function rejectPurchaseRequest(int $requestId, int $userId, ?string $reason = null): PurchaseRequest
    {
        $request = PurchaseRequest::findOrFail($requestId);
        $request->update([
            'status' => PurchaseRequest::STATUS_REJECTED,
            'approved_by' => $userId,
            'approved_at' => Carbon::now(),
            'notes' => $reason ? ($request->notes ? $request->notes." | سبب الرفض: {$reason}" : "سبب الرفض: {$reason}") : $request->notes,
        ]);

        return $request;
    }

    /**
     * Compare Supplier Quotations & catalogs for items in a Purchase Request.
     *
     * @return array<string, mixed>
     */
    public function compareSupplierQuotes(int $requestId): array
    {
        $pr = PurchaseRequest::with(['items.product', 'warehouse'])->findOrFail($requestId);

        $productIds = $pr->items->pluck('product_id')->toArray();
        $supplierProducts = SupplierProduct::with('supplier', 'product')
            ->whereIn('product_id', $productIds)
            ->get();

        $activeSuppliers = Supplier::orderBy('name')->get();

        $comparison = [];
        $supplierTotals = [];

        foreach ($pr->items as $item) {
            $prodId = $item->product_id;
            $qty = (float) $item->quantity_requested;

            $itemOffers = [];
            $lowestPrice = null;
            $bestSupplierId = null;

            foreach ($activeSuppliers as $sup) {
                $sp = $supplierProducts->where('supplier_id', $sup->id)->where('product_id', $prodId)->first();
                $price = $sp ? (float) ($sp->supplier_price ?? $sp->unit_price ?? 0) : (float) ($item->product->cost_price ?? 0);
                if ($price <= 0) {
                    $price = (float) ($item->estimated_unit_cost > 0 ? $item->estimated_unit_cost : ($item->product->cost_price ?? 10.0));
                }
                $lineTotal = $price * $qty;

                $itemOffers[$sup->id] = [
                    'supplier_name' => $sup->name,
                    'unit_price' => $price,
                    'line_total' => $lineTotal,
                    'delivery_days' => 1,
                    'min_order_qty' => 1,
                ];

                if (! isset($supplierTotals[$sup->id])) {
                    $supplierTotals[$sup->id] = [
                        'supplier' => $sup,
                        'total_amount' => 0.0,
                        'items_count' => 0,
                    ];
                }
                $supplierTotals[$sup->id]['total_amount'] += $lineTotal;
                $supplierTotals[$sup->id]['items_count']++;

                if ($lowestPrice === null || $price < $lowestPrice) {
                    $lowestPrice = $price;
                    $bestSupplierId = $sup->id;
                }
            }

            $comparison[] = [
                'item' => $item,
                'offers' => $itemOffers,
                'lowest_price' => $lowestPrice,
                'best_supplier_id' => $bestSupplierId,
            ];
        }

        // Sort suppliers by total offer ascending
        usort($supplierTotals, fn ($a, $b) => $a['total_amount'] <=> $b['total_amount']);

        return [
            'purchase_request' => $pr,
            'items_comparison' => $comparison,
            'supplier_rankings' => $supplierTotals,
        ];
    }

    /**
     * Convert an approved Purchase Request to a formal Purchase Order for a chosen supplier.
     */
    public function convertPrToPo(int $requestId, int $supplierId, int $userId, ?array $overridePrices = null): PurchaseOrder
    {
        return DB::transaction(function () use ($requestId, $supplierId, $userId, $overridePrices) {
            $pr = PurchaseRequest::with(['items.product', 'warehouse'])->findOrFail($requestId);

            $supplierProducts = SupplierProduct::where('supplier_id', $supplierId)
                ->whereIn('product_id', $pr->items->pluck('product_id'))
                ->get()
                ->keyBy('product_id');

            $poItems = [];
            foreach ($pr->items as $item) {
                $unitPrice = 0.0;
                if (isset($overridePrices[$item->product_id])) {
                    $unitPrice = (float) $overridePrices[$item->product_id];
                } elseif ($supplierProducts->has($item->product_id)) {
                    $spItem = $supplierProducts->get($item->product_id);
                    $unitPrice = (float) ($spItem->supplier_price ?? $spItem->unit_price ?? 0);
                }

                if ($unitPrice <= 0) {
                    $unitPrice = (float) ($item->estimated_unit_cost > 0 ? $item->estimated_unit_cost : ($item->product->cost_price ?? 10.0));
                }

                $poItems[] = [
                    'product_id' => $item->product_id,
                    'quantity' => (float) $item->quantity_requested,
                    'unit' => $item->unit,
                    'unit_price' => $unitPrice,
                    'notes' => $item->notes,
                ];
            }

            $poData = [
                'purchase_request_id' => $pr->id,
                'supplier_id' => $supplierId,
                'warehouse_id' => $pr->warehouse_id,
                'order_date' => Carbon::today(),
                'expected_delivery_date' => $pr->required_by_date ?? Carbon::today()->addDays(2),
                'tax_amount' => 0.0,
                'notes' => "تم التوليد تلقائياً بناء على طلب الشراء المعتمد [{$pr->pr_number}] ومقارنة عروض الأسعار.",
            ];

            $po = $this->createPurchaseOrder($poData, $poItems, $userId);

            $pr->update(['status' => PurchaseRequest::STATUS_PO_CREATED]);

            return $po;
        });
    }

    /**
     * 3. Create a formal Purchase Order (أمر شراء معمد للمورد).
     */
    public function createPurchaseOrder(array $data, array $items, int $userId): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $items, $userId) {
            $count = PurchaseOrder::whereDate('created_at', Carbon::today())->count() + 1;
            $poNumber = 'PO-'.date('Ymd').'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);

            $subtotal = 0.00;
            foreach ($items as $item) {
                $subtotal += ((float) $item['quantity']) * ((float) $item['unit_price']);
            }
            $taxAmount = (float) ($data['tax_amount'] ?? 0.00);
            $totalAmount = $subtotal + $taxAmount;

            $po = PurchaseOrder::create([
                'po_number' => $poNumber,
                'purchase_request_id' => $data['purchase_request_id'] ?? null,
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'order_date' => $data['order_date'] ?? Carbon::today(),
                'expected_delivery_date' => $data['expected_delivery_date'] ?? Carbon::today()->addDays(2),
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'status' => PurchaseOrder::STATUS_APPROVED, // Auto-approved by manager upon issuance
                'created_by' => $userId,
                'approved_by' => $userId,
                'approved_at' => Carbon::now(),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $qty = (float) $item['quantity'];
                $price = (float) $item['unit_price'];
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $item['product_id'],
                    'quantity_ordered' => $qty,
                    'quantity_received' => 0.0000,
                    'unit' => $item['unit'] ?? 'kg',
                    'unit_price' => $price,
                    'total_price' => $qty * $price,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            if (! empty($data['purchase_request_id'])) {
                PurchaseRequest::where('id', $data['purchase_request_id'])->update(['status' => PurchaseRequest::STATUS_PO_CREATED]);
            }

            return $po->load('items.product', 'supplier', 'warehouse');
        });
    }

    /**
     * 4. Record Goods Receipt Note (GRN - إذن فحص واستلام مخزني للباتشات).
     */
    public function recordGoodsReceipt(array $header, array $items, int $userId): GoodsReceiptNote
    {
        return DB::transaction(function () use ($header, $items, $userId) {
            $count = GoodsReceiptNote::whereDate('created_at', Carbon::today())->count() + 1;
            $grnNumber = 'GRN-'.date('Ymd').'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);

            $grn = GoodsReceiptNote::create([
                'grn_number' => $grnNumber,
                'purchase_order_id' => $header['purchase_order_id'] ?? null,
                'supplier_id' => $header['supplier_id'],
                'warehouse_id' => $header['warehouse_id'],
                'receipt_date' => $header['receipt_date'] ?? Carbon::today(),
                'supplier_invoice_reference' => $header['supplier_invoice_reference'] ?? null,
                'delivery_note_reference' => $header['delivery_note_reference'] ?? null,
                'status' => GoodsReceiptNote::STATUS_STORED,
                'received_by' => $userId,
                'inspected_by' => $header['inspected_by'] ?? $userId,
                'inspection_notes' => $header['inspection_notes'] ?? 'تم الفحص ومطابقة الصلاحية ودرجات الحرارة',
                'notes' => $header['notes'] ?? null,
            ]);

            $totalAcceptedValue = 0.00;

            foreach ($items as $itemData) {
                $productId = $itemData['product_id'];
                $acceptedQty = (float) $itemData['quantity_accepted'];
                $deliveredQty = (float) ($itemData['quantity_delivered'] ?? $acceptedQty);
                $rejectedQty = max(0.00, $deliveredQty - $acceptedQty);
                $unitCost = (float) $itemData['unit_cost'];
                $lineTotal = $acceptedQty * $unitCost;
                $totalAcceptedValue += $lineTotal;

                $batchNumber = $itemData['batch_number'] ?? 'B-'.date('ymd').'-'.rand(1000, 9999);
                $expiryDate = $itemData['expiry_date'] ?? Carbon::today()->addMonths(6)->toDateString();
                $productionDate = $itemData['production_date'] ?? Carbon::today()->subDays(2)->toDateString();

                // Create Inventory Batch
                $batch = InventoryBatch::create([
                    'batch_number' => $batchNumber,
                    'product_id' => $productId,
                    'warehouse_id' => $header['warehouse_id'],
                    'initial_quantity' => $acceptedQty,
                    'remaining_quantity' => $acceptedQty,
                    'cost_price' => $unitCost,
                    'selling_price' => $unitCost * 1.25,
                    'received_at' => Carbon::now(),
                    'production_date' => $productionDate,
                    'expiry_date' => $expiryDate,
                    'is_exhausted' => false,
                    'notes' => "وارد من المورد عبر إذن فحص {$grn->grn_number}",
                ]);

                // Update Stock Balance in Warehouse
                $balance = StockBalance::firstOrCreate(
                    [
                        'product_id' => $productId,
                        'warehouse_id' => $header['warehouse_id'],
                    ],
                    [
                        'quantity' => 0.00,
                        'reserved_quantity' => 0.00,
                    ]
                );
                $balance->increment('quantity', $acceptedQty);

                // Create Stock Movement
                StockMovement::create([
                    'movement_number' => 'MOV-GRN-'.date('YmdHis').'-'.rand(100, 999),
                    'type' => StockMovement::TYPE_IN,
                    'product_id' => $productId,
                    'to_warehouse_id' => $header['warehouse_id'],
                    'batch_id' => $batch->id,
                    'quantity' => $acceptedQty,
                    'unit_cost' => $unitCost,
                    'reference_type' => 'goods_receipt_note',
                    'reference_id' => $grn->id,
                    'user_id' => $userId,
                    'notes' => "استلام فحص وتخزين إذن رقم {$grn->grn_number} - تشغيلة {$batch->batch_number}",
                ]);

                // Create GRN Item
                GoodsReceiptNoteItem::create([
                    'goods_receipt_note_id' => $grn->id,
                    'product_id' => $productId,
                    'quantity_delivered' => $deliveredQty,
                    'quantity_accepted' => $acceptedQty,
                    'quantity_rejected' => $rejectedQty,
                    'rejection_reason' => $rejectedQty > 0 ? ($itemData['rejection_reason'] ?? 'عدم مطابقة مواصفات الفحص') : null,
                    'unit' => $itemData['unit'] ?? 'kg',
                    'unit_cost' => $unitCost,
                    'total_cost' => $lineTotal,
                    'batch_number' => $batch->batch_number,
                    'production_date' => $productionDate,
                    'expiry_date' => $expiryDate,
                    'storage_temperature' => $itemData['storage_temperature'] ?? null,
                    'inventory_batch_id' => $batch->id,
                ]);

                // Update PO Item quantity_received if PO is linked
                if (! empty($header['purchase_order_id'])) {
                    PurchaseOrderItem::where('purchase_order_id', $header['purchase_order_id'])
                        ->where('product_id', $productId)
                        ->increment('quantity_received', $acceptedQty);
                }
            }

            // Update Supplier balance (Increase Accounts Payable)
            $supplier = Supplier::find($header['supplier_id']);
            if ($supplier) {
                $supplier->increment('current_balance', $totalAcceptedValue);
            }

            // Automatic Accounting Entry (Dr. Inventory, Cr. Supplier Payable)
            $invAccount = ChartOfAccount::where('code', '101300')->first();
            $apAccount = ChartOfAccount::where('code', '201000')->first();

            if ($invAccount && $apAccount && $totalAcceptedValue > 0) {
                $entryNumber = 'JV-GRN-'.date('Ymd').'-'.str_pad((string) $grn->id, 4, '0', STR_PAD_LEFT);
                $entry = JournalEntry::create([
                    'entry_number' => $entryNumber,
                    'entry_date' => Carbon::today(),
                    'description' => "إثبات استلام توريد خامات إذن رقم {$grn->grn_number} من {$supplier?->name}",
                    'source_type' => 'goods_receipt',
                    'source_id' => $grn->id,
                    'created_by' => $userId,
                    'is_posted' => true,
                ]);

                // Debit Inventory
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'chart_of_account_id' => $invAccount->id,
                    'account_type' => 'inventory',
                    'account_name' => $invAccount->name,
                    'debit' => $totalAcceptedValue,
                    'credit' => 0.00,
                    'notes' => "إضافة مخزون الخامات بالمخزن الرئيسي - {$grn->grn_number}",
                ]);

                // Credit Supplier Payable
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'chart_of_account_id' => $apAccount->id,
                    'account_type' => 'supplier',
                    'account_id' => $supplier?->id,
                    'account_name' => $supplier ? "المورد: {$supplier->name}" : $apAccount->name,
                    'debit' => 0.00,
                    'credit' => $totalAcceptedValue,
                    'notes' => "استحقاق فاتورة توريد {$grn->supplier_invoice_reference}",
                ]);
            }

            // Update PO Status if all items received
            if (! empty($header['purchase_order_id'])) {
                $po = PurchaseOrder::with('items')->find($header['purchase_order_id']);
                if ($po) {
                    $allReceived = true;
                    foreach ($po->items as $pi) {
                        if ($pi->quantity_received < $pi->quantity_ordered) {
                            $allReceived = false;
                            break;
                        }
                    }
                    $po->update([
                        'status' => $allReceived ? PurchaseOrder::STATUS_FULLY_RECEIVED : PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
                    ]);
                }
            }

            return $grn->load('items.product', 'supplier', 'warehouse');
        });
    }
}
