<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptNoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'goods_receipt_note_id',
        'product_id',
        'quantity_delivered',
        'quantity_accepted',
        'quantity_rejected',
        'rejection_reason',
        'unit',
        'unit_cost',
        'total_cost',
        'batch_number',
        'production_date',
        'expiry_date',
        'storage_temperature',
        'inventory_batch_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_delivered' => 'decimal:4',
            'quantity_accepted' => 'decimal:4',
            'quantity_rejected' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:2',
            'storage_temperature' => 'decimal:2',
            'production_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function receiptNote(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptNote::class, 'goods_receipt_note_id');
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
