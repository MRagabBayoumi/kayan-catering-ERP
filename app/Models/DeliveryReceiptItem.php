<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryReceiptItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_receipt_id',
        'product_id',
        'dispatched_qty',
        'actual_delivered_qty',
        'extra_requested_qty',
        'returned_qty',
        'rejected_qty',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'dispatched_qty' => 'decimal:4',
            'actual_delivered_qty' => 'decimal:4',
            'extra_requested_qty' => 'decimal:4',
            'returned_qty' => 'decimal:4',
            'rejected_qty' => 'decimal:4',
        ];
    }

    public function deliveryReceipt(): BelongsTo
    {
        return $this->belongsTo(DeliveryReceipt::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
