<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyCashClosing extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'closing_date',
        'opening_balance',
        'total_inflow',
        'total_outflow',
        'closing_balance',
        'actual_cash_count',
        'difference',
        'site_custody_spent',
        'supplier_payments',
        'status',
        'notes',
        'closed_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'closing_date' => 'date',
        'opening_balance' => 'decimal:2',
        'total_inflow' => 'decimal:2',
        'total_outflow' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'actual_cash_count' => 'decimal:2',
        'difference' => 'decimal:2',
        'site_custody_spent' => 'decimal:2',
        'supplier_payments' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function hasDeficit(): bool
    {
        return (float) $this->difference < 0;
    }

    public function hasSurplus(): bool
    {
        return (float) $this->difference > 0;
    }
}
