<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlindStockAuditItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'blind_stock_audit_id',
        'product_id',
        'batch_id',
        'physical_count',
        'system_quantity',
        'variance',
        'unit_cost',
        'variance_cost',
        'discrepancy_reason',
    ];

    protected function casts(): array
    {
        return [
            'physical_count' => 'decimal:4',
            'system_quantity' => 'decimal:4',
            'variance' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'variance_cost' => 'decimal:4',
        ];
    }

    public function audit(): BelongsTo
    {
        return $this->belongsTo(BlindStockAudit::class, 'blind_stock_audit_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id');
    }
}
