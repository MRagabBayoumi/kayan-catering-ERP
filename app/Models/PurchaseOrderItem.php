<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'quantity_ordered',
        'quantity_received',
        'unit',
        'unit_price',
        'total_price',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_ordered' => 'decimal:4',
            'quantity_received' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'total_price' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
