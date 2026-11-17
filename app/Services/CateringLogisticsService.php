<?php

namespace App\Services;

use App\Models\BlindStockAudit;
use App\Models\BlindStockAuditItem;
use App\Models\DeliveryReceipt;
use App\Models\DeliveryReceiptItem;
use App\Models\DispatchOrder;
use App\Models\DispatchOrderItem;
use App\Models\DriverSettlement;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\SupplyOrder;
use App\Models\SupplyOrderItem;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CateringLogisticsService
{
    /**
     * 1. Create a Supply Order from Costing / Contracts Management.
     */
    public function createSupplyOrder(array $data, array $items): SupplyOrder
    {
        return DB::transaction(function () use ($data, $items) {
            $orderNumber = $data['order_number'] ?? 'SO-'.date('Ymd').'-'.str_pad((string) (SupplyOrder::count() + 1), 4, '0', STR_PAD_LEFT);

            $order = SupplyOrder::create([
                'order_number' => $orderNumber,
                'hospital_id' => $data['hospital_id'],
                'order_date' => $data['order_date'] ?? Carbon::today(),
                'start_date' => $data['start_date'] ?? Carbon::today(),
                'end_date' => $data['end_date'] ?? Carbon::today(),
                'frequency' => $data['frequency'] ?? 'daily',
                'status' => $data['status'] ?? SupplyOrder::STATUS_APPROVED,
                'created_by' => $data['created_by'] ?? auth()->id(),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                SupplyOrderItem::create([
                    'supply_order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'storage_type' => $item['storage_type'] ?? 'dry',
                    'planned_quantity' => $item['planned_quantity'],
                    'dispatched_quantity' => 0.0000,
                    'delivered_quantity' => 0.0000,
                    'unit' => $item['unit'] ?? 'kg',
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $order->load('items.product', 'hospital');
        });
    }

    /**
     * 2. Issue Dispatch Order (Load & Transfer from Warehouse to Driver Van Custody with FIFO).
     */
    public function createDispatchOrder(array $header, array $items): DispatchOrder
    {
        return DB::transaction(function () use ($header, $items) {
            $dispatchNumber = 'DSP-'.date('Ymd').'-'.str_pad((string) (DispatchOrder::count() + 1), 4, '0', STR_PAD_LEFT);

            $dispatch = DispatchOrder::create([
                'dispatch_number' => $dispatchNumber,
                'supply_order_id' => $header['supply_order_id'] ?? null,
                'hospital_id' => $header['hospital_id'],
                'recipe_id' => $header['recipe_id'] ?? null,
                'planned_meals_count' => ! empty($header['planned_meals_count']) ? (int) $header['planned_meals_count'] : null,
                'source_warehouse_id' => $header['source_warehouse_id'],
                'transit_warehouse_id' => $header['transit_warehouse_id'], // Van Warehouse
                'driver_id' => $header['driver_id'],
                'storekeeper_id' => $header['storekeeper_id'] ?? auth()->id(),
                'store_manager_id' => $header['store_manager_id'] ?? null,
                'dispatch_date' => Carbon::now(),
                'status' => DispatchOrder::STATUS_IN_TRANSIT,
                'notes' => $header['notes'] ?? null,
            ]);

            foreach ($items as $itemData) {
                $productId = $itemData['product_id'];
                $plannedQty = (float) $itemData['planned_qty'];
                $bufferQty = (float) ($itemData['buffer_qty'] ?? 0.00);
                $totalToDispatch = $plannedQty + $bufferQty;

                if ($totalToDispatch <= 0) {
                    continue;
                }

                // Deduct from Source Warehouse and transfer to Transit Van Warehouse using strict FIFO
                $remainingQtyToFulfill = $totalToDispatch;

                $batches = InventoryBatch::where('product_id', $productId)
                    ->where('warehouse_id', $header['source_warehouse_id'])
                    ->availableFEFO()
                    ->lockForUpdate()
                    ->get();

                $availableInSource = $batches->sum('remaining_quantity');
                if ($availableInSource < $totalToDispatch) {
                    $prod = Product::find($productId);
                    throw new InvalidArgumentException("الرصيد المتاح من الصنف ({$prod?->name}) في المخزن المصدر غير كافٍ. المطلوب: {$totalToDispatch}، المتاح: {$availableInSource}");
                }

                foreach ($batches as $batch) {
                    if ($remainingQtyToFulfill <= 0) {
                        break;
                    }

                    $takeQty = min($batch->remaining_quantity, $remainingQtyToFulfill);
                    $batch->remaining_quantity -= $takeQty;
                    if ($batch->remaining_quantity <= 0.0001) {
                        $batch->is_exhausted = true;
                    }
                    $batch->save();

                    // Create/Add Batch in Van Transit Warehouse for custody tracking
                    InventoryBatch::create([
                        'batch_number' => $batch->batch_number.'-VAN'.$dispatch->id,
                        'product_id' => $productId,
                        'warehouse_id' => $header['transit_warehouse_id'],
                        'initial_quantity' => $takeQty,
                        'remaining_quantity' => $takeQty,
                        'cost_price' => $batch->cost_price,
                        'production_date' => $batch->production_date,
                        'expiry_date' => $batch->expiry_date,
                        'received_at' => Carbon::now(),
                        'is_exhausted' => false,
                        'notes' => 'محمل بعهدة السيارة لإذن الصرف '.$dispatchNumber,
                    ]);

                    // Dispatch Item Line
                    DispatchOrderItem::create([
                        'dispatch_order_id' => $dispatch->id,
                        'product_id' => $productId,
                        'inventory_batch_id' => $batch->id,
                        'planned_qty' => min($takeQty, $plannedQty),
                        'buffer_qty' => max(0, $takeQty - $plannedQty),
                        'total_dispatched_qty' => $takeQty,
                        'unit_cost' => $batch->cost_price,
                        'total_cost' => $takeQty * $batch->cost_price,
                    ]);

                    // Record Stock Movement: Transfer from Source to Transit
                    StockMovement::create([
                        'movement_number' => 'MOV-TR-'.date('YmdHis').'-'.rand(100, 999),
                        'type' => StockMovement::TYPE_TRANSFER,
                        'product_id' => $productId,
                        'from_warehouse_id' => $header['source_warehouse_id'],
                        'to_warehouse_id' => $header['transit_warehouse_id'],
                        'batch_id' => $batch->id,
                        'quantity' => $takeQty,
                        'unit_cost' => $batch->cost_price,
                        'reference_type' => 'DispatchOrder',
                        'reference_id' => $dispatch->id,
                        'user_id' => $header['storekeeper_id'] ?? auth()->id(),
                        'notes' => "تحميل عهدة سيارة من المخزن لإذن صرف {$dispatchNumber}",
                    ]);

                    $remainingQtyToFulfill -= $takeQty;
                }

                // Update Stock Balances for both Source and Transit Warehouses
                $sourceBalance = StockBalance::firstOrCreate(
                    ['product_id' => $productId, 'warehouse_id' => $header['source_warehouse_id']],
                    ['quantity' => 0.0000]
                );
                $sourceBalance->quantity = max(0, $sourceBalance->quantity - $totalToDispatch);
                $sourceBalance->save();

                $transitBalance = StockBalance::firstOrCreate(
                    ['product_id' => $productId, 'warehouse_id' => $header['transit_warehouse_id']],
                    ['quantity' => 0.0000]
                );
                $transitBalance->quantity += $totalToDispatch;
                $transitBalance->save();

                // If linked to Supply Order, update dispatched_quantity
                if (! empty($header['supply_order_id'])) {
                    SupplyOrderItem::where('supply_order_id', $header['supply_order_id'])
                        ->where('product_id', $productId)
                        ->increment('dispatched_quantity', $plannedQty);
                }
            }

            return $dispatch->load('items.product', 'transitWarehouse', 'sourceWarehouse', 'hospital');
        });
    }

    /**
     * 3. Record Delivery at Hospital (Actual Delivery, Extra Requests & Returns).
     * Transits inventory from Driver Van Custody to Hospital Site Warehouse.
     */
    public function recordDeliveryReceipt(int $dispatchOrderId, array $receiptData, array $itemResults): DeliveryReceipt
    {
        return DB::transaction(function () use ($dispatchOrderId, $receiptData, $itemResults) {
            $dispatch = DispatchOrder::with('hospital')->findOrFail($dispatchOrderId);

            $receiptNumber = 'REC-'.date('Ymd').'-'.str_pad((string) (DeliveryReceipt::count() + 1), 4, '0', STR_PAD_LEFT);

            $receipt = DeliveryReceipt::create([
                'receipt_number' => $receiptNumber,
                'dispatch_order_id' => $dispatch->id,
                'hospital_id' => $dispatch->hospital_id,
                'driver_id' => $dispatch->driver_id,
                'receiving_officer_name' => $receiptData['receiving_officer_name'] ?? null,
                'received_at' => $receiptData['received_at'] ?? Carbon::now(),
                'status' => DeliveryReceipt::STATUS_VERIFIED,
                'notes' => $receiptData['notes'] ?? null,
            ]);

            // Ensure Hospital Site Warehouse exists
            $hospital = $dispatch->hospital ?? Hospital::find($dispatch->hospital_id);
            $siteWarehouse = Warehouse::firstOrCreate(
                ['hospital_id' => $hospital->id, 'type' => Warehouse::TYPE_SITE],
                [
                    'code' => 'SITE-'.($hospital->code ?? 'H'.$hospital->id),
                    'name' => 'مخزن موقع '.$hospital->name,
                    'is_active' => true,
                    'is_default' => false,
                ]
            );

            foreach ($itemResults as $res) {
                $actualDelivered = (float) $res['actual_delivered_qty'];
                $dispatchedQty = (float) $res['dispatched_qty'];
                $returnedQty = (float) ($res['returned_qty'] ?? 0.0000);
                $rejectedQty = (float) ($res['rejected_qty'] ?? 0.0000);

                DeliveryReceiptItem::create([
                    'delivery_receipt_id' => $receipt->id,
                    'product_id' => $res['product_id'],
                    'dispatched_qty' => $dispatchedQty,
                    'actual_delivered_qty' => $actualDelivered,
                    'extra_requested_qty' => $res['extra_requested_qty'] ?? 0.0000,
                    'returned_qty' => $returnedQty,
                    'rejected_qty' => $rejectedQty,
                    'rejection_reason' => $res['rejection_reason'] ?? null,
                ]);

                // Transfer delivered goods from Van Transit to Hospital Site Warehouse
                if ($actualDelivered > 0) {
                    // 1. Deduct from Van Transit Warehouse
                    $vanBal = StockBalance::where('product_id', $res['product_id'])
                        ->where('warehouse_id', $dispatch->transit_warehouse_id)
                        ->first();
                    if ($vanBal) {
                        $vanBal->quantity = max(0, $vanBal->quantity - $actualDelivered);
                        $vanBal->save();
                    }

                    // 2. Increment Hospital Site Warehouse Balance
                    $siteBal = StockBalance::firstOrCreate(
                        ['product_id' => $res['product_id'], 'warehouse_id' => $siteWarehouse->id],
                        ['quantity' => 0.0000]
                    );
                    $siteBal->quantity += $actualDelivered;
                    $siteBal->save();

                    // 3. Find source batch info from Van
                    $sourceVanBatch = InventoryBatch::where('product_id', $res['product_id'])
                        ->where('warehouse_id', $dispatch->transit_warehouse_id)
                        ->latest()
                        ->first();

                    $unitCost = $sourceVanBatch ? (float) $sourceVanBatch->cost_price : (float) (Product::find($res['product_id'])?->cost_price ?? 0);

                    // 4. Create batch in Site Warehouse
                    InventoryBatch::create([
                        'batch_number' => ($sourceVanBatch?->batch_number ?? 'B-'.$receipt->id).'-SITE',
                        'product_id' => $res['product_id'],
                        'warehouse_id' => $siteWarehouse->id,
                        'initial_quantity' => $actualDelivered,
                        'remaining_quantity' => $actualDelivered,
                        'cost_price' => $unitCost,
                        'production_date' => $sourceVanBatch?->production_date ?? Carbon::today(),
                        'expiry_date' => $sourceVanBatch?->expiry_date ?? Carbon::today()->addMonths(3),
                        'received_at' => Carbon::now(),
                        'is_exhausted' => false,
                        'notes' => 'وارد للمخزن الفرعي لموقع '.$hospital->name.' عبر إشعار استلام رقم '.$receiptNumber,
                    ]);

                    // 5. Stock movement: Van -> Hospital Site Warehouse
                    StockMovement::create([
                        'movement_number' => 'MOV-SITE-'.date('YmdHis').'-'.rand(100, 999),
                        'type' => StockMovement::TYPE_TRANSFER,
                        'product_id' => $res['product_id'],
                        'from_warehouse_id' => $dispatch->transit_warehouse_id,
                        'to_warehouse_id' => $siteWarehouse->id,
                        'quantity' => $actualDelivered,
                        'unit_cost' => $unitCost,
                        'reference_type' => 'DeliveryReceipt',
                        'reference_id' => $receipt->id,
                        'user_id' => auth()->id() ?? $dispatch->driver_id,
                        'notes' => "تسليم بضاعة لمخزن موقع مستشفى {$hospital->name} عبر إشعار {$receiptNumber}",
                    ]);
                }

                // Update supply order delivered quantity if linked
                if ($dispatch->supply_order_id) {
                    SupplyOrderItem::where('supply_order_id', $dispatch->supply_order_id)
                        ->where('product_id', $res['product_id'])
                        ->increment('delivered_quantity', $actualDelivered);
                }
            }

            $dispatch->status = DispatchOrder::STATUS_DELIVERED;
            $dispatch->save();

            return $receipt->load('items.product', 'dispatchOrder');
        });
    }

    /**
     * 4. Perform Blind Stock Audit (Storekeeper enters counts blindly without seeing system balances).
     */
    public function submitBlindAudit(int $warehouseId, int $storekeeperId, array $countedItems, ?string $notes = null): BlindStockAudit
    {
        return DB::transaction(function () use ($warehouseId, $storekeeperId, $countedItems, $notes) {
            $auditNumber = 'AUDIT-'.date('Ymd').'-'.str_pad((string) (BlindStockAudit::count() + 1), 4, '0', STR_PAD_LEFT);

            $audit = BlindStockAudit::create([
                'audit_number' => $auditNumber,
                'warehouse_id' => $warehouseId,
                'storekeeper_id' => $storekeeperId,
                'audited_at' => Carbon::now(),
                'status' => BlindStockAudit::STATUS_SUBMITTED,
                'notes' => $notes,
            ]);

            foreach ($countedItems as $item) {
                $productId = $item['product_id'];
                $physicalCount = (float) $item['physical_count'];

                // System calculates system_quantity automatically in backend (Hidden from storekeeper)
                $systemQty = (float) (StockBalance::where('product_id', $productId)
                    ->where('warehouse_id', $warehouseId)
                    ->value('quantity') ?? 0.0000);

                $variance = $physicalCount - $systemQty;

                $product = Product::find($productId);
                $unitCost = (float) ($product?->cost_price ?? 0.00);
                $varianceCost = $variance * $unitCost;

                BlindStockAuditItem::create([
                    'blind_stock_audit_id' => $audit->id,
                    'product_id' => $productId,
                    'batch_id' => $item['batch_id'] ?? null,
                    'physical_count' => $physicalCount,
                    'system_quantity' => $systemQty,
                    'variance' => $variance,
                    'unit_cost' => $unitCost,
                    'variance_cost' => $varianceCost,
                    'discrepancy_reason' => $item['discrepancy_reason'] ?? null,
                ]);
            }

            return $audit->load('items.product', 'warehouse', 'storekeeper');
        });
    }

    /**
     * 5. Settle Driver Trip & Generate ERP Integration Voucher.
     */
    public function settleDriverTrip(int $dispatchOrderId, int $deliveryReceiptId, int $accountantId, ?string $notes = null): DriverSettlement
    {
        return DB::transaction(function () use ($dispatchOrderId, $deliveryReceiptId, $accountantId, $notes) {
            $dispatch = DispatchOrder::with('items')->findOrFail($dispatchOrderId);
            $receipt = DeliveryReceipt::with('items')->findOrFail($deliveryReceiptId);

            $totalDispatched = $receipt->items->sum('dispatched_qty');
            $totalDelivered = $receipt->items->sum('actual_delivered_qty');
            $totalReturned = $receipt->items->sum('returned_qty');
            $totalExtra = $receipt->items->sum('extra_requested_qty');
            $totalRejected = $receipt->items->sum('rejected_qty');

            // Calculate total COGS (Cost of Goods Delivered to Hospital)
            $totalCogsCost = 0.00;
            foreach ($dispatch->items as $dItem) {
                $rItem = $receipt->items->firstWhere('product_id', $dItem->product_id);
                if ($rItem) {
                    $totalCogsCost += ((float) $rItem->actual_delivered_qty * (float) $dItem->unit_cost);
                }
            }

            // Process Delivered and Returned goods for each item
            foreach ($receipt->items as $rItem) {
                $prodId = $rItem->product_id;
                $deliveredQty = (float) $rItem->actual_delivered_qty;
                $retQty = (float) $rItem->returned_qty;

                // 1. Deduct Delivered Quantity from Driver Van Warehouse (if not already transferred during delivery receipt)
                $alreadyTransferred = StockMovement::where('reference_type', 'DeliveryReceipt')
                    ->where('reference_id', $receipt->id)
                    ->where('product_id', $prodId)
                    ->exists();

                if ($deliveredQty > 0 && ! $alreadyTransferred) {
                    $vanBal = StockBalance::where('product_id', $prodId)
                        ->where('warehouse_id', $dispatch->transit_warehouse_id)
                        ->first();
                    if ($vanBal) {
                        $vanBal->quantity = max(0, $vanBal->quantity - $deliveredQty);
                        $vanBal->save();
                    }

                    StockMovement::create([
                        'movement_number' => 'MOV-OUT-'.date('YmdHis').'-'.rand(100, 999),
                        'type' => StockMovement::TYPE_OUT,
                        'product_id' => $prodId,
                        'from_warehouse_id' => $dispatch->transit_warehouse_id,
                        'to_warehouse_id' => null,
                        'quantity' => $deliveredQty,
                        'reference_type' => 'DeliveryReceipt',
                        'reference_id' => $receipt->id,
                        'user_id' => $accountantId,
                        'notes' => "صرف نهائي وتوريد لمستشفى {$dispatch->hospital->name} (إشعار تسليم {$receipt->receipt_number})",
                    ]);
                }

                // 2. Return unused buffer or rejected goods from Driver Van Warehouse back to Main Warehouse
                if ($retQty > 0) {
                    $vanBal = StockBalance::where('product_id', $prodId)
                        ->where('warehouse_id', $dispatch->transit_warehouse_id)
                        ->first();
                    if ($vanBal) {
                        $vanBal->quantity = max(0, $vanBal->quantity - $retQty);
                        $vanBal->save();
                    }

                    $sourceBal = StockBalance::firstOrCreate(
                        ['product_id' => $prodId, 'warehouse_id' => $dispatch->source_warehouse_id],
                        ['quantity' => 0.0000]
                    );
                    $sourceBal->quantity += $retQty;
                    $sourceBal->save();

                    StockMovement::create([
                        'movement_number' => 'MOV-RET-'.date('YmdHis').'-'.rand(100, 999),
                        'type' => StockMovement::TYPE_TRANSFER,
                        'product_id' => $prodId,
                        'from_warehouse_id' => $dispatch->transit_warehouse_id,
                        'to_warehouse_id' => $dispatch->source_warehouse_id,
                        'quantity' => $retQty,
                        'reference_type' => 'DriverSettlement',
                        'reference_id' => $dispatch->id,
                        'user_id' => $accountantId,
                        'notes' => "تسوية مرتجع من سيارة المندوب إلى المخزن الرئيسي بعد التوريد (إذن {$dispatch->dispatch_number})",
                    ]);
                }
            }

            // Generate ERP Journal Reference
            $erpVoucher = 'ERP-JV-'.date('Ymd').'-HOSP'.$dispatch->hospital_id.'-'.rand(1000, 9999);

            $settlement = DriverSettlement::create([
                'settlement_number' => 'SETTLE-'.date('Ymd').'-'.str_pad((string) (DriverSettlement::count() + 1), 4, '0', STR_PAD_LEFT),
                'dispatch_order_id' => $dispatch->id,
                'delivery_receipt_id' => $receipt->id,
                'driver_id' => $dispatch->driver_id,
                'accountant_id' => $accountantId,
                'settled_at' => Carbon::now(),
                'total_dispatched_qty' => $totalDispatched,
                'total_delivered_qty' => $totalDelivered,
                'total_returned_qty' => $totalReturned,
                'total_extra_qty' => $totalExtra,
                'total_rejected_qty' => $totalRejected,
                'total_cogs_cost' => $totalCogsCost,
                'erp_journal_voucher' => $erpVoucher,
                'status' => DriverSettlement::STATUS_SETTLED,
                'notes' => $notes,
            ]);

            $dispatch->status = DispatchOrder::STATUS_SETTLED;
            $dispatch->save();

            $receipt->status = DeliveryReceipt::STATUS_SETTLED;
            $receipt->save();

            return $settlement->load('dispatchOrder', 'deliveryReceipt', 'driver', 'accountant');
        });
    }
}
