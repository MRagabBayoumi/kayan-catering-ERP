<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tender extends Model
{
    use HasFactory;

    public const STATUS_UNDER_STUDY = 'under_study';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_AWARDED = 'awarded';

    public const STATUS_LOST = 'lost';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'tender_number',
        'title',
        'hospital_id',
        'entity_name',
        'announcement_date',
        'envelope_opening_date',
        'bid_bond_amount',
        'final_guarantee_amount',
        'booklet_cost',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'announcement_date' => 'date',
            'envelope_opening_date' => 'date',
            'bid_bond_amount' => 'decimal:2',
            'final_guarantee_amount' => 'decimal:2',
            'booklet_cost' => 'decimal:2',
        ];
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function competitorPrices(): HasMany
    {
        return $this->hasMany(CompetitorPrice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
