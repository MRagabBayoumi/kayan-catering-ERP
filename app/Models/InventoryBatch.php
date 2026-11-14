<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_number',
        'product_id',
        'warehouse_id',
        'initial_quantity',
        'remaining_quantity',
        'cost_price',
        'production_date',
        'expiry_date',
        'received_at',
        'is_exhausted',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'initial_quantity' => 'decimal:4',
            'remaining_quantity' => 'decimal:4',
            'cost_price' => 'decimal:4',
            'production_date' => 'date',
            'expiry_date' => 'date',
            'received_at' => 'datetime',
            'is_exhausted' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'batch_id');
    }

    /**
     * Scope query to active (non-exhausted) batches ordered by FEFO:
     * Earliest expiring first, then oldest received for identical or unset expiries.
     */
    public function scopeAvailableFEFO(Builder $query): Builder
    {
        return $query->where('is_exhausted', false)
            ->where('remaining_quantity', '>', 0)
            ->orderByRaw('CASE WHEN expiry_date IS NOT NULL THEN 0 ELSE 1 END ASC')
            ->orderBy('expiry_date', 'asc')
            ->orderBy('received_at', 'asc')
            ->orderBy('id', 'asc');
    }

    /**
     * Scope query to active (non-exhausted) batches ordered by FIFO (oldest received first).
     */
    public function scopeAvailableFIFO(Builder $query): Builder
    {
        return $this->scopeAvailableFEFO($query);
    }

    /**
     * Calculate days remaining until expiry.
     */
    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (! $this->expiry_date) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->expiry_date->startOfDay(), false);
    }

    /**
     * Expiry category: expired, critical (<7d), warning (<30d), safe (>30d), or none.
     */
    public function getExpiryStatusAttribute(): string
    {
        $days = $this->days_until_expiry;

        if ($days === null) {
            return 'none';
        }

        if ($days < 0) {
            return 'expired';
        }

        if ($days <= 7) {
            return 'critical';
        }

        if ($days <= 30) {
            return 'warning';
        }

        return 'safe';
    }

    /**
     * Total remaining value of the batch.
     */
    public function getTotalValueAttribute(): float
    {
        return round((float) $this->remaining_quantity * (float) $this->cost_price, 2);
    }

    /**
     * Determine if this batch has confirmed selling prices assigned.
     */
    public function hasPricing(): bool
    {
        return ! is_null($this->retail_price) && ! is_null($this->wholesale_price);
    }

    /**
     * Get specific price tier per base unit.
     */
    public function getPriceForTier(string $tier = 'retail'): ?float
    {
        $prod = $this->product ?? Product::find($this->product_id);

        $price = match (strtolower(trim($tier))) {
            'wholesale' => $this->wholesale_price ?? $prod?->wholesale_price,
            'distributor' => $this->distributor_price ?? $this->wholesale_price ?? $prod?->distributor_price ?? $prod?->wholesale_price,
            default => $this->retail_price ?? $prod?->retail_price,
        };

        return ! is_null($price) ? (float) $price : null;
    }

    /**
     * Calculate exact COGS deduction with final unit closure reconciliation.
     * Guarantees total_cogs across all partial sales strictly matches total_initial_cost.
     */
    public function calculateExactCogs(float $quantityBase, bool $isFinalDepletion = false): float
    {
        $newRemaining = round((float) $this->remaining_quantity - $quantityBase, 4);

        if ($isFinalDepletion || $newRemaining <= 0.0001) {
            // Final depletion: resolve any penny fraction accumulated from division
            $initialCost = (float) $this->total_initial_cost;
            if ($initialCost <= 0.0) {
                $initialCost = round((float) $this->initial_quantity * (float) $this->cost_price, 2);
            }
            $allocatedSoFar = (float) $this->accumulated_cogs_deducted;
            $cogs = max(0.00, round($initialCost - $allocatedSoFar, 2));
        } else {
            $unitCost = (float) $this->cost_price;
            $cogs = round($quantityBase * $unitCost, 2);
        }

        return $cogs;
    }

    /**
     * Generate unique batch number.
     */
    public static function generateBatchNumber(): string
    {
        $prefix = 'BATCH-'.date('Ymd').'-';
        $random = strtoupper(bin2hex(random_bytes(2)));
        $count = static::whereDate('created_at', today())->count() + 1;

        return $prefix.str_pad((string) $count, 4, '0', STR_PAD_LEFT).'-'.$random;
    }
}
