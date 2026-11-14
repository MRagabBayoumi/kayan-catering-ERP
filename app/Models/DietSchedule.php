<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DietSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'meal_type',
        'hospital_id',
        'daily_bread_count',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'daily_bread_count' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DietScheduleItem::class);
    }
}
