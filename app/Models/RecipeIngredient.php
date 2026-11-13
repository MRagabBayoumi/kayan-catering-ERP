<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeIngredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipe_id',
        'product_id',
        'raw_quantity_per_meal',
        'cooked_yield_quantity',
        'unit',
        'standard_unit_cost',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'raw_quantity_per_meal' => 'decimal:4',
            'cooked_yield_quantity' => 'decimal:4',
            'standard_unit_cost' => 'decimal:4',
        ];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
