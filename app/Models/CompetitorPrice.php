<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitorPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'tender_id',
        'competitor_name',
        'item_or_meal_name',
        'our_bid_price',
        'competitor_price',
        'variance_percentage',
        'tender_year',
        'was_awarded',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'our_bid_price' => 'decimal:2',
            'competitor_price' => 'decimal:2',
            'variance_percentage' => 'decimal:2',
            'tender_year' => 'integer',
            'was_awarded' => 'boolean',
        ];
    }

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }
}
