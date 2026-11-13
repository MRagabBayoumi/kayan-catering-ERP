<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Hospital extends Model
{
    use HasFactory;

    public const CONTRACT_ACTIVE = 'active';

    public const CONTRACT_EXPIRED = 'expired';

    public const CONTRACT_TERMINATED = 'terminated';

    public const CONTRACT_SUSPENDED = 'suspended';

    protected $fillable = [
        'code',
        'name',
        'contract_number',
        'contact_person',
        'phone',
        'address',
        'daily_patient_capacity',
        'default_buffer_percentage',
        'is_active',
        'contract_status',
        'contract_start_date',
        'contract_end_date',
        'terminated_at',
        'termination_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'daily_patient_capacity' => 'integer',
            'default_buffer_percentage' => 'decimal:2',
            'contract_start_date' => 'date',
            'contract_end_date' => 'date',
            'terminated_at' => 'datetime',
        ];
    }

    public function supplyOrders(): HasMany
    {
        return $this->hasMany(SupplyOrder::class);
    }

    public function dispatchOrders(): HasMany
    {
        return $this->hasMany(DispatchOrder::class);
    }

    public function deliveryReceipts(): HasMany
    {
        return $this->hasMany(DeliveryReceipt::class);
    }

    public function tenders(): HasMany
    {
        return $this->hasMany(Tender::class);
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    public function consumptionLogs(): HasMany
    {
        return $this->hasMany(ConsumptionLog::class);
    }

    public function siteWarehouse(): HasOne
    {
        return $this->hasOne(Warehouse::class)->where('type', Warehouse::TYPE_SITE);
    }

    public function isContractActive(): bool
    {
        return $this->contract_status === self::CONTRACT_ACTIVE && $this->is_active;
    }
}
