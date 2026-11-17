<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractMealPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'meal_type_id',
        'diet_category',
        'price_per_meal',
        'minimum_daily_quantity',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'price_per_meal' => 'decimal:2',
            'minimum_daily_quantity' => 'integer',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function mealType(): BelongsTo
    {
        return $this->belongsTo(MealType::class);
    }
}
