<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'product_id',
        'supplier_item_code',
        'supplier_item_name',
        'supplier_price',
        'currency',
        'effective_date',
        'is_preferred',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'supplier_price' => 'decimal:4',
            'effective_date' => 'date',
            'is_preferred' => 'boolean',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
