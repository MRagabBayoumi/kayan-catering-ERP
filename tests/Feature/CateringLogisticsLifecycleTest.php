<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Hospital;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CateringLogisticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CateringLogisticsLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_catering_supply_dispatch_settlement_and_blind_audit_lifecycle(): void
    {
        // 1. Setup Master Data
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $costingMgr = User::factory()->create(['role' => User::ROLE_COSTING_MANAGER]);
        $storekeeper = User::factory()->create(['role' => User::ROLE_STOREKEEPER]);
        $driver = User::factory()->create(['role' => User::ROLE_DRIVER]);
        $accountant = User::factory()->create(['role' => User::ROLE_ACCOUNTANT]);

        $mainWh = Warehouse::create([
            'code' => 'WH-TEST-MAIN',
            'name' => 'المخزن الرئيسي',
            'type' => Warehouse::TYPE_MAIN,
            'is_active' => true,
        ]);

        $vanWh = Warehouse::create([
            'code' => 'VAN-TEST-01',
            'name' => 'عهدة سيارة السائق',
            'type' => Warehouse::TYPE_VAN_TRANSIT,
            'driver_id' => $driver->id,
            'is_active' => true,
        ]);

        $hospital = Hospital::create([
            'code' => 'HOSP-TEST',
            'name' => 'مستشفى الأمل التخصصي',
            'default_buffer_percentage' => 10.00,
            'is_active' => true,
        ]);

        $category = Category::create([
            'code' => 'CAT-TEST',
            'name' => 'لحوم',
            'slug' => 'meat-test',
            'storage_type' => 'chilled',
        ]);

        $beef = Product::create([
            'sku' => 'BEEF-TEST',
            'name' => 'لحم بقري مبرد',
            'category_id' => $category->id,
            'cost_price' => 350.00,
            'unit' => 'kg',
            'storage_type' => 'chilled',
            'is_active' => true,
        ]);

        // Stock in Main Warehouse with 2 FIFO batches
        // Batch 1 (Old: 50 kg @ 340 EGP)
        InventoryBatch::create([
            'batch_number' => 'B-001',
            'product_id' => $beef->id,
            'warehouse_id' => $mainWh->id,
            'initial_quantity' => 50.00,
            'remaining_quantity' => 50.00,
            'cost_price' => 340.00,
            'received_at' => Carbon::now()->subDays(2),
            'is_exhausted' => false,
        ]);

        // Batch 2 (Newer: 100 kg @ 360 EGP)
        InventoryBatch::create([
            'batch_number' => 'B-002',
            'product_id' => $beef->id,
            'warehouse_id' => $mainWh->id,
            'initial_quantity' => 100.00,
            'remaining_quantity' => 100.00,
            'cost_price' => 360.00,
            'received_at' => Carbon::now()->subDay(),
            'is_exhausted' => false,
        ]);

        StockBalance::create([
            'product_id' => $beef->id,
            'warehouse_id' => $mainWh->id,
            'quantity' => 150.00,
        ]);

        $service = new CateringLogisticsService;

        // 2. Costing Manager creates Supply Order for Hospital (70 kg)
        $supplyOrder = $service->createSupplyOrder([
            'hospital_id' => $hospital->id,
            'created_by' => $costingMgr->id,
            'frequency' => 'daily',
        ], [
            [
                'product_id' => $beef->id,
                'planned_quantity' => 70.00,
                'storage_type' => 'chilled',
                'unit' => 'kg',
            ],
        ]);

        $this->assertDatabaseHas('supply_orders', ['id' => $supplyOrder->id, 'hospital_id' => $hospital->id]);

        // 3. Dispatch to Driver Van with 10% safety buffer (70 planned + 10 buffer = 80 total dispatched)
        // FIFO deduction: 50 kg from Batch 1 (@340), 30 kg from Batch 2 (@360)
        $dispatch = $service->createDispatchOrder([
            'supply_order_id' => $supplyOrder->id,
            'hospital_id' => $hospital->id,
            'source_warehouse_id' => $mainWh->id,
            'transit_warehouse_id' => $vanWh->id,
            'driver_id' => $driver->id,
            'storekeeper_id' => $storekeeper->id,
        ], [
            [
                'product_id' => $beef->id,
                'planned_qty' => 70.00,
                'buffer_qty' => 10.00,
            ],
        ]);

        $this->assertEquals(80.00, $dispatch->items->sum('total_dispatched_qty'));

        // Check Main Warehouse balance decreased from 150 to 70
        $this->assertEquals(70.00, StockBalance::where('product_id', $beef->id)->where('warehouse_id', $mainWh->id)->value('quantity'));
        // Check Van Warehouse balance is 80
        $this->assertEquals(80.00, StockBalance::where('product_id', $beef->id)->where('warehouse_id', $vanWh->id)->value('quantity'));

        // Check Batch 1 is exhausted (0 remaining), Batch 2 has 70 remaining
        $this->assertEquals(0.00, InventoryBatch::where('batch_number', 'B-001')->value('remaining_quantity'));
        $this->assertEquals(70.00, InventoryBatch::where('batch_number', 'B-002')->value('remaining_quantity'));

        // 4. Delivery at Hospital:
        // Hospital requested 75 kg (70 planned + 5 extra from buffer).
        // Driver returns remaining 5 kg.
        $receipt = $service->recordDeliveryReceipt($dispatch->id, [
            'receiving_officer_name' => 'رئيس تمريض المستشفى',
        ], [
            [
                'product_id' => $beef->id,
                'dispatched_qty' => 80.00,
                'actual_delivered_qty' => 75.00,
                'extra_requested_qty' => 5.00,
                'returned_qty' => 5.00,
                'rejected_qty' => 0.00,
            ],
        ]);

        $this->assertDatabaseHas('delivery_receipts', ['id' => $receipt->id]);

        // 5. Warehouse Accountant Trip Settlement:
        $settlement = $service->settleDriverTrip($dispatch->id, $receipt->id, $accountant->id, 'اعتماد رحلة مستشفى الأمل');

        $this->assertDatabaseHas('driver_settlements', [
            'id' => $settlement->id,
            'total_delivered_qty' => 75.00,
            'total_returned_qty' => 5.00,
        ]);

        // Returned 5 kg added back to Main Warehouse: 70 + 5 = 75 kg
        $this->assertEquals(75.00, (float) StockBalance::where('product_id', $beef->id)->where('warehouse_id', $mainWh->id)->value('quantity'));

        // Van Warehouse has 0 kg remaining (75 delivered + 5 returned = 80 total cleared)
        $this->assertEquals(0.00, (float) StockBalance::where('product_id', $beef->id)->where('warehouse_id', $vanWh->id)->value('quantity'));

        // 6. Blind Daily Stock Audit by Storekeeper:
        // Storekeeper physically counts 74 kg in Main Warehouse (1 kg discrepancy / shortage)
        $audit = $service->submitBlindAudit($mainWh->id, $storekeeper->id, [
            [
                'product_id' => $beef->id,
                'physical_count' => 74.00,
                'discrepancy_reason' => 'عجز جفاف وفقد طبيعي في التبريد',
            ],
        ], 'جرد مسائي للمخزن الرئيسي');

        $auditItem = $audit->items->first();
        $this->assertEquals(74.00, (float) $auditItem->physical_count);
        $this->assertEquals(75.00, (float) $auditItem->system_quantity);
        $this->assertEquals(-1.00, (float) $auditItem->variance);
    }
}
