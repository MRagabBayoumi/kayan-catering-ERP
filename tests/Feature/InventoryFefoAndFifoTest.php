<?php

namespace Tests\Feature;

use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryFefoAndFifoTest extends TestCase
{
    use RefreshDatabase;

    protected Product $product;

    protected Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->product = Product::create([
            'sku' => 'TEST-ISOLATED-MEAT-'.uniqid(),
            'name' => 'لحم بلدي فاخر منعزل',
            'unit' => 'kg',
            'cost_price' => 280.00,
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::first() ?? Warehouse::create([
            'code' => 'WH-TEST-MAIN',
            'name' => 'المخزن المركزي التجريبي',
            'is_active' => true,
        ]);
    }

    public function test_fefo_strictly_excludes_expired_batches(): void
    {
        $today = Carbon::today();

        // 1. Expired batch (yesterday)
        $expiredBatch = InventoryBatch::create([
            'batch_number' => 'BATCH-EXP-01',
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'initial_quantity' => 50,
            'remaining_quantity' => 50,
            'cost_price' => 250,
            'expiry_date' => $today->copy()->subDay(),
            'received_at' => $today->copy()->subDays(10),
            'is_exhausted' => false,
        ]);

        // 2. Valid batch expiring tomorrow
        $validBatch1 = InventoryBatch::create([
            'batch_number' => 'BATCH-VAL-01',
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'initial_quantity' => 30,
            'remaining_quantity' => 30,
            'cost_price' => 260,
            'expiry_date' => $today->copy()->addDay(),
            'received_at' => $today->copy()->subDays(5),
            'is_exhausted' => false,
        ]);

        // 3. Valid batch expiring in 10 days
        $validBatch2 = InventoryBatch::create([
            'batch_number' => 'BATCH-VAL-02',
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'initial_quantity' => 40,
            'remaining_quantity' => 40,
            'cost_price' => 270,
            'expiry_date' => $today->copy()->addDays(10),
            'received_at' => $today->copy()->subDays(8),
            'is_exhausted' => false,
        ]);

        $availableFefo = InventoryBatch::where('product_id', $this->product->id)
            ->availableFEFO()
            ->get();

        // Expired batch must NEVER be in available FEFO
        $this->assertFalse($availableFefo->contains('id', $expiredBatch->id));

        // FEFO order: validBatch1 (expiring tomorrow) MUST come before validBatch2 (expiring in 10 days)
        $this->assertEquals($validBatch1->id, $availableFefo->first()->id);
        $this->assertEquals($validBatch2->id, $availableFefo->skip(1)->first()->id);
    }

    public function test_fifo_orders_by_received_at_and_decouples_from_expiry_date(): void
    {
        $today = Carbon::today();

        // Batch A: Received 10 days ago, expires in 20 days
        $batchA = InventoryBatch::create([
            'batch_number' => 'BATCH-FIFO-A',
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'initial_quantity' => 10,
            'remaining_quantity' => 10,
            'cost_price' => 200,
            'expiry_date' => $today->copy()->addDays(20),
            'received_at' => $today->copy()->subDays(10),
            'is_exhausted' => false,
        ]);

        // Batch B: Received 3 days ago, expires in 2 days (earlier expiry than A, but received later)
        $batchB = InventoryBatch::create([
            'batch_number' => 'BATCH-FIFO-B',
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'initial_quantity' => 15,
            'remaining_quantity' => 15,
            'cost_price' => 210,
            'expiry_date' => $today->copy()->addDays(2),
            'received_at' => $today->copy()->subDays(3),
            'is_exhausted' => false,
        ]);

        $availableFifo = InventoryBatch::where('product_id', $this->product->id)
            ->whereIn('id', [$batchA->id, $batchB->id])
            ->availableFIFO()
            ->get();

        // FIFO must order Batch A first (oldest received: 10 days ago) even though Batch B expires earlier
        $this->assertEquals($batchA->id, $availableFifo->first()->id);
        $this->assertEquals($batchB->id, $availableFifo->last()->id);
    }

    public function test_expired_scope_identifies_quarantine_batches(): void
    {
        $today = Carbon::today();

        $expired = InventoryBatch::create([
            'batch_number' => 'BATCH-EXPIRED-QUARANTINE',
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'initial_quantity' => 25,
            'remaining_quantity' => 25,
            'cost_price' => 150,
            'expiry_date' => $today->copy()->subDays(3),
            'received_at' => $today->copy()->subDays(20),
            'is_exhausted' => false,
        ]);

        $quarantine = InventoryBatch::expired()->get();

        $this->assertTrue($quarantine->contains('id', $expired->id));
    }

    public function test_near_expiry_scope_identifies_batches_within_window(): void
    {
        $today = Carbon::today();

        $nearExpiry = InventoryBatch::create([
            'batch_number' => 'BATCH-NEAR-EXP',
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'initial_quantity' => 15,
            'remaining_quantity' => 15,
            'cost_price' => 150,
            'expiry_date' => $today->copy()->addDays(3),
            'received_at' => $today->copy()->subDays(5),
            'is_exhausted' => false,
        ]);

        $farExpiry = InventoryBatch::create([
            'batch_number' => 'BATCH-FAR-EXP',
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'initial_quantity' => 15,
            'remaining_quantity' => 15,
            'cost_price' => 150,
            'expiry_date' => $today->copy()->addDays(30),
            'received_at' => $today->copy()->subDays(5),
            'is_exhausted' => false,
        ]);

        $warningBatches = InventoryBatch::nearExpiry(5)->get();

        $this->assertTrue($warningBatches->contains('id', $nearExpiry->id));
        $this->assertFalse($warningBatches->contains('id', $farExpiry->id));
    }
}
