<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeasibilityStudy extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'study_number',
        'tender_id',
        'hospital_name',
        'planned_duration_months',
        'expected_total_meals',
        'target_selling_price_per_meal',
        'expected_revenue',
        'estimated_food_cost',
        'estimated_labor_cost',
        'estimated_transport_cost',
        'estimated_consumables_cost',
        'estimated_operating_cost',
        'estimated_total_cost',
        'expected_gross_margin',
        'expected_net_margin_percentage',
        'break_even_meals',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'planned_duration_months' => 'integer',
            'expected_total_meals' => 'integer',
            'break_even_meals' => 'integer',
            'target_selling_price_per_meal' => 'decimal:2',
            'expected_revenue' => 'decimal:2',
            'estimated_food_cost' => 'decimal:2',
            'estimated_labor_cost' => 'decimal:2',
            'estimated_transport_cost' => 'decimal:2',
            'estimated_consumables_cost' => 'decimal:2',
            'estimated_operating_cost' => 'decimal:2',
            'estimated_total_cost' => 'decimal:2',
            'expected_gross_margin' => 'decimal:2',
            'expected_net_margin_percentage' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(FeasibilityStudyItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
