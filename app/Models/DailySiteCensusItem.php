<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailySiteCensusItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_site_census_id',
        'diet_schedule_id',
        'patient_count',
    ];

    protected function casts(): array
    {
        return [
            'patient_count' => 'integer',
        ];
    }

    public function census(): BelongsTo
    {
        return $this->belongsTo(DailySiteCensus::class, 'daily_site_census_id');
    }

    public function dietSchedule(): BelongsTo
    {
        return $this->belongsTo(DietSchedule::class);
    }
}
