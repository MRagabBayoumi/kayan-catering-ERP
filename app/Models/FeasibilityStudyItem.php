<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeasibilityStudyItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'feasibility_study_id',
        'cost_category',
        'item_name',
        'quantity',
        'unit',
        'unit_cost',
        'total_cost',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:2',
        ];
    }

    public function study(): BelongsTo
    {
        return $this->belongsTo(FeasibilityStudy::class, 'feasibility_study_id');
    }
}
