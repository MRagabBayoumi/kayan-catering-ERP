<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeVersionIngredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipe_version_id',
        'product_id',
        'raw_quantity_per_meal',
        'cooked_yield_quantity',
        'shrinkage_cooking_loss_pct',
        'butchery_yield_pct',
        'unit',
        'standard_unit_cost',
        'total_ingredient_cost',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'raw_quantity_per_meal' => 'decimal:4',
            'cooked_yield_quantity' => 'decimal:4',
            'shrinkage_cooking_loss_pct' => 'decimal:2',
            'butchery_yield_pct' => 'decimal:2',
            'standard_unit_cost' => 'decimal:4',
            'total_ingredient_cost' => 'decimal:4',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(RecipeVersion::class, 'recipe_version_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
