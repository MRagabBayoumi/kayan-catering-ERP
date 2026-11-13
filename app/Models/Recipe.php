<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipe_code',
        'name',
        'meal_type_id',
        'hospital_id',
        'standard_cost_per_meal',
        'is_approved_by_costing',
        'is_approved_by_site_mgr',
        'is_active',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'standard_cost_per_meal' => 'decimal:2',
            'is_approved_by_costing' => 'boolean',
            'is_approved_by_site_mgr' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function mealType(): BelongsTo
    {
        return $this->belongsTo(MealType::class);
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function consumptionLogs(): HasMany
    {
        return $this->hasMany(ConsumptionLog::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
