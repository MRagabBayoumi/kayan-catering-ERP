<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'dispatch_order_id',
        'product_id',
        'inventory_batch_id',
        'planned_qty',
        'buffer_qty',
        'total_dispatched_qty',
        'unit_cost',
        'total_cost',
    ];

    protected function casts(): array
    {
        return [
            'planned_qty' => 'decimal:4',
            'buffer_qty' => 'decimal:4',
            'total_dispatched_qty' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:4',
        ];
    }

    public function dispatchOrder(): BelongsTo
    {
        return $this->belongsTo(DispatchOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'inventory_batch_id');
    }
}
