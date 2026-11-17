<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceiptNote extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_INSPECTED = 'inspected';

    public const STATUS_STORED = 'stored';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'grn_number',
        'purchase_order_id',
        'supplier_id',
        'warehouse_id',
        'receipt_date',
        'supplier_invoice_reference',
        'delivery_note_reference',
        'status',
        'received_by',
        'inspected_by',
        'inspection_notes',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'receipt_date' => 'date',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptNoteItem::class);
    }
}
