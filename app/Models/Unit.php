<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory;

    public const CATEGORY_WEIGHT = 'weight';

    public const CATEGORY_VOLUME = 'volume';

    public const CATEGORY_COUNT = 'count';

    public const CATEGORY_PACKAGING = 'packaging';

    protected $fillable = [
        'name',
        'code',
        'symbol',
        'category',
        'conversion_factor',
        'is_base_unit',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'conversion_factor' => 'decimal:6',
            'is_base_unit' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeBaseUnits(Builder $query): Builder
    {
        return $query->where('is_base_unit', true);
    }

    public function isBase(): bool
    {
        return (bool) $this->is_base_unit;
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'unit', 'name');
    }
}
