<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    public const TYPE_MAIN = 'main';

    public const TYPE_DRY = 'dry';

    public const TYPE_CHILLED = 'chilled';

    public const TYPE_FROZEN = 'frozen';

    public const TYPE_VAN_TRANSIT = 'van_transit';

    public const TYPE_SITE = 'site';

    protected $fillable = [
        'code',
        'name',
        'type',
        'driver_id',
        'hospital_id',
        'location',
        'is_active',
        'is_default',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'to_warehouse_id');
    }

    public function isVanTransit(): bool
    {
        return $this->type === self::TYPE_VAN_TRANSIT;
    }
}
