<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecipeVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipe_id',
        'version_number',
        'version_name',
        'effective_from',
        'effective_to',
        'total_standard_cost',
        'is_active',
        'is_locked',
        'approved_by',
        'approved_at',
        'change_reason',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'total_standard_cost' => 'decimal:4',
            'is_active' => 'boolean',
            'is_locked' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeVersionIngredient::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
