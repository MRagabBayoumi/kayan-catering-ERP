<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryReceipt extends Model
{
    use HasFactory;

    public const STATUS_PENDING_VERIFICATION = 'pending_verification';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_SETTLED = 'settled';

    protected $fillable = [
        'receipt_number',
        'dispatch_order_id',
        'hospital_id',
        'driver_id',
        'receiving_officer_name',
        'received_at',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
        ];
    }

    public function dispatchOrder(): BelongsTo
    {
        return $this->belongsTo(DispatchOrder::class);
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryReceiptItem::class);
    }
}
