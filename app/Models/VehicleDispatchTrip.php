<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleDispatchTrip extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_number',
        'vehicle_plate',
        'driver_id',
        'trip_date',
        'fuel_expense',
        'toll_and_other_expense',
        'total_trip_expense',
        'petty_cash_transaction_id',
        'created_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'trip_date' => 'date',
            'fuel_expense' => 'decimal:2',
            'toll_and_other_expense' => 'decimal:2',
            'total_trip_expense' => 'decimal:2',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pettyCashTransaction(): BelongsTo
    {
        return $this->belongsTo(PettyCashTransaction::class);
    }

    public function destinations(): HasMany
    {
        return $this->hasMany(VehicleTripDestination::class);
    }
}
