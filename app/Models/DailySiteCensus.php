<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailySiteCensus extends Model
{
    use HasFactory;

    public const PROTEIN_MEAT = 'meat';

    public const PROTEIN_POULTRY = 'poultry';

    public const CARB_RICE_VERMICELLI = 'rice_vermicelli';

    public const CARB_PLAIN_RICE = 'plain_rice';

    public const CARB_PASTA = 'pasta';

    public const VEG_COOKED = 'cooked_veg';

    public const VEG_BEANS = 'beans';

    public const VEG_COWPEA = 'cowpea';

    protected $fillable = [
        'census_date',
        'hospital_id',
        'site_manager_id',
        'protein_choice',
        'carb_choice',
        'veg_choice',
        'total_patient_count',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'census_date' => 'date',
            'total_patient_count' => 'integer',
        ];
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function siteManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'site_manager_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DailySiteCensusItem::class);
    }

    public function getProteinLabelAttribute(): string
    {
        return match ($this->protein_choice) {
            self::PROTEIN_MEAT => 'لحم مشفى (150 جم)',
            self::PROTEIN_POULTRY => 'دواجن - ربع فرخة (275 جم)',
            default => $this->protein_choice,
        };
    }

    public function getCarbLabelAttribute(): string
    {
        return match ($this->carb_choice) {
            self::CARB_RICE_VERMICELLI => 'أرز بالشعرية (85 جم أرز + 15 جم شعرية)',
            self::CARB_PLAIN_RICE => 'أرز أبيض بدون شعرية (100 جم)',
            self::CARB_PASTA => 'مكرونة معبأة (100 جم)',
            default => $this->carb_choice,
        };
    }

    public function getVegLabelAttribute(): string
    {
        return match ($this->veg_choice) {
            self::VEG_COOKED => 'خضار للطهي (200 جم)',
            self::VEG_BEANS => 'فاصوليا معبأة جافة (80 جم)',
            self::VEG_COWPEA => 'لوبيا معبأة جافة (80 جم)',
            default => $this->veg_choice,
        };
    }
}
