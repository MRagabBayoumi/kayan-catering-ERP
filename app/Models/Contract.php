<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_TERMINATED = 'terminated';

    protected $fillable = [
        'contract_number',
        'hospital_id',
        'tender_id',
        'cost_center_id',
        'title',
        'start_date',
        'end_date',
        'status',
        'contract_value',
        'billing_cycle',
        'payment_terms_days',
        'penalty_terms',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'approved_at' => 'datetime',
            'contract_value' => 'decimal:2',
            'payment_terms_days' => 'integer',
        ];
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function mealPrices(): HasMany
    {
        return $this->hasMany(ContractMealPrice::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
