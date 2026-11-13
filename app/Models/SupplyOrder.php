<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplyOrder extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_number',
        'hospital_id',
        'order_date',
        'start_date',
        'end_date',
        'frequency',
        'status',
        'created_by',
        'approved_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplyOrderItem::class);
    }

    public function dispatchOrders(): HasMany
    {
        return $this->hasMany(DispatchOrder::class);
    }
}
