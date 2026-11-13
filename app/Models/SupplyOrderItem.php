<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'supply_order_id',
        'product_id',
        'storage_type',
        'planned_quantity',
        'dispatched_quantity',
        'delivered_quantity',
        'unit',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'planned_quantity' => 'decimal:4',
            'dispatched_quantity' => 'decimal:4',
            'delivered_quantity' => 'decimal:4',
        ];
    }

    public function supplyOrder(): BelongsTo
    {
        return $this->belongsTo(SupplyOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getRemainingToDispatchAttribute(): float
    {
        return max(0.00, (float) $this->planned_quantity - (float) $this->dispatched_quantity);
    }
}
