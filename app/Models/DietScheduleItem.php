<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DietScheduleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'diet_schedule_id',
        'product_id',
        'category_type',
        'portion_quantity',
        'unit',
        'weekly_frequency',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'portion_quantity' => 'decimal:4',
            'weekly_frequency' => 'decimal:2',
        ];
    }

    public function dietSchedule(): BelongsTo
    {
        return $this->belongsTo(DietSchedule::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
