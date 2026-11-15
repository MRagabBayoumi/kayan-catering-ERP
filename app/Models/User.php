<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

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

    public const ROLE_PROCUREMENT_MANAGER = 'procurement_manager';

    public const ROLE_SALES_MANAGER = 'sales_manager';

    public const ROLE_SITE_MANAGER = 'site_manager';

    public static function rolesList(): array
    {
        return [
            self::ROLE_ADMIN => 'مدير عام المنظومة (صلاحيات كاملة)',
            self::ROLE_STORE_MANAGER => 'مدير المخازن والتشغيلات (FIFO)',
            self::ROLE_STOREKEEPER => 'أمين المخزن الرئيسي (الجرد الأعمى والصرف)',
            self::ROLE_DRIVER => 'سائق سيارة التوزيع ومندوب التوريد',
            self::ROLE_COSTING_MANAGER => 'مدير التكاليف والمقننات القياسية (BOM)',
            self::ROLE_SALES_MANAGER => 'مدير المبيعات والمناقصات وعقود المستشفيات',
            self::ROLE_PROCUREMENT_MANAGER => 'مسؤول المشتريات ومتابعة الموردين',
            self::ROLE_ACCOUNTANT => 'محاسب المخزن والمالية (تسوية العهد)',
            self::ROLE_SITE_MANAGER => 'مدير موقع ومطابخ المستشفى',
        ];
    }

    public function getRoleLabelAttribute(): string
    {
        return self::rolesList()[$this->role] ?? 'مستخدم نظام';
    }

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'phone',
        'assigned_warehouse_id',
        'assigned_hospital_id',
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

    public function assignedHospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'assigned_hospital_id');
    }

    public function supervisedHospitals(): BelongsToMany
    {
        return $this->belongsToMany(Hospital::class, 'hospital_user');
    }

    /**
     * Get the hospitals this user is authorized to manage/supervise.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Hospital>|Collection<int, Hospital>
     */
    public function accessibleHospitals()
    {
        if ($this->isAdmin()) {
            return Hospital::where('is_active', true)->orderBy('name')->get();
        }

        if ($this->role === self::ROLE_SITE_MANAGER && $this->assignedHospital) {
            return collect([$this->assignedHospital]);
        }

        $supervised = $this->supervisedHospitals()->where('is_active', true)->orderBy('name')->get();

        if ($supervised->isNotEmpty()) {
            return $supervised;
        }

        // Fallback for general accountants/staff if no specific hospital was assigned
        return Hospital::where('is_active', true)->orderBy('name')->get();
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

    public function isSalesRep(): bool
    {
        return false;
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
