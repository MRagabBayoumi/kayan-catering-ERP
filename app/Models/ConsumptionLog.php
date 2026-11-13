<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsumptionLog extends Model
{
    use HasFactory;

    public const STATUS_NORMAL = 'normal';

    public const STATUS_OVER_CONSUMPTION_ALERT = 'over_consumption_alert';

    public const STATUS_SAVED = 'saved';

    protected $fillable = [
        'log_number',
        'hospital_id',
        'recipe_id',
        'consumption_date',
        'actual_served_meals_count',
        'product_id',
        'standard_expected_qty',
        'actual_consumed_qty',
        'variance_qty',
        'actual_fifo_cost',
        'standard_cost',
        'cost_variance',
        'status',
        'discrepancy_justification',
        'verified_by_costing',
    ];

    protected function casts(): array
    {
        return [
            'consumption_date' => 'date',
            'actual_served_meals_count' => 'integer',
            'standard_expected_qty' => 'decimal:4',
            'actual_consumed_qty' => 'decimal:4',
            'variance_qty' => 'decimal:4',
            'actual_fifo_cost' => 'decimal:4',
            'standard_cost' => 'decimal:4',
            'cost_variance' => 'decimal:4',
        ];
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_costing');
    }

    public function isOverConsumed(): bool
    {
        return $this->variance_qty > 0.0001;
    }
}
