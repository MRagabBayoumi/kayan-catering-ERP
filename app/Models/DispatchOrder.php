<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DispatchOrder extends Model
{
    use HasFactory;

    public const STATUS_PREPARED = 'prepared';

    public const STATUS_IN_TRANSIT = 'in_transit';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_SETTLED = 'settled';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'dispatch_number',
        'supply_order_id',
        'hospital_id',
        'recipe_id',
        'planned_meals_count',
        'source_warehouse_id',
        'transit_warehouse_id',
        'driver_id',
        'storekeeper_id',
        'store_manager_id',
        'dispatch_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'dispatch_date' => 'datetime',
            'planned_meals_count' => 'integer',
        ];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function supplyOrder(): BelongsTo
    {
        return $this->belongsTo(SupplyOrder::class);
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function transitWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'transit_warehouse_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function storekeeper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'storekeeper_id');
    }

    public function storeManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'store_manager_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DispatchOrderItem::class);
    }

    public function deliveryReceipt(): HasOne
    {
        return $this->hasOne(DeliveryReceipt::class);
    }

    public function settlement(): HasOne
    {
        return $this->hasOne(DriverSettlement::class);
    }
}
