<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_COSTING_MANAGER = 'costing_manager';

    public const ROLE_STORE_MANAGER = 'store_manager';

    public const ROLE_STOREKEEPER = 'storekeeper';

    public const ROLE_DRIVER = 'driver';

    public const ROLE_ACCOUNTANT = 'accountant';

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'phone',
        'assigned_warehouse_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function assignedWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'assigned_warehouse_id');
    }

    public function vanWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'id', 'driver_id');
    }

    public function supplyOrders(): HasMany
    {
        return $this->hasMany(SupplyOrder::class, 'created_by');
    }

    public function dispatchOrdersAsDriver(): HasMany
    {
        return $this->hasMany(DispatchOrder::class, 'driver_id');
    }

    public function dispatchOrdersAsStorekeeper(): HasMany
    {
        return $this->hasMany(DispatchOrder::class, 'storekeeper_id');
    }

    public function deliveryReceipts(): HasMany
    {
        return $this->hasMany(DeliveryReceipt::class, 'driver_id');
    }

    public function blindAudits(): HasMany
    {
        return $this->hasMany(BlindStockAudit::class, 'storekeeper_id');
    }

    public function settlementsAsAccountant(): HasMany
    {
        return $this->hasMany(DriverSettlement::class, 'accountant_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isCostingManager(): bool
    {
        return $this->role === self::ROLE_COSTING_MANAGER || $this->isAdmin();
    }

    public function isStoreManager(): bool
    {
        return $this->role === self::ROLE_STORE_MANAGER || $this->isAdmin();
    }

    public function isStorekeeper(): bool
    {
        return $this->role === self::ROLE_STOREKEEPER || $this->isAdmin();
    }

    public function isDriver(): bool
    {
        return $this->role === self::ROLE_DRIVER;
    }

    public function isAccountant(): bool
    {
        return $this->role === self::ROLE_ACCOUNTANT || $this->isAdmin();
    }
}
