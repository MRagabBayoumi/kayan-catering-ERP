<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverSettlement extends Model
{
    use HasFactory;

    public const STATUS_SETTLED = 'settled';

    public const STATUS_DISPUTED = 'disputed';

    protected $fillable = [
        'settlement_number',
        'dispatch_order_id',
        'delivery_receipt_id',
        'driver_id',
        'accountant_id',
        'settled_at',
        'total_dispatched_qty',
        'total_delivered_qty',
        'total_returned_qty',
        'total_extra_qty',
        'total_rejected_qty',
        'total_cogs_cost',
        'erp_journal_voucher',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'settled_at' => 'datetime',
            'total_dispatched_qty' => 'decimal:4',
            'total_delivered_qty' => 'decimal:4',
            'total_returned_qty' => 'decimal:4',
            'total_extra_qty' => 'decimal:4',
            'total_rejected_qty' => 'decimal:4',
            'total_cogs_cost' => 'decimal:4',
        ];
    }

    public function dispatchOrder(): BelongsTo
    {
        return $this->belongsTo(DispatchOrder::class);
    }

    public function deliveryReceipt(): BelongsTo
    {
        return $this->belongsTo(DeliveryReceipt::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function accountant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accountant_id');
    }
}
