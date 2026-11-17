<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostCenter extends Model
{
    use HasFactory;

    public const TYPE_HOSPITAL = 'hospital';

    public const TYPE_VEHICLE = 'vehicle';

    public const TYPE_CENTRAL_WAREHOUSE = 'central_warehouse';

    public const TYPE_ADMIN = 'admin';

    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'code',
        'name',
        'type',
        'hospital_id',
        'parent_id',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function wasteRecords(): HasMany
    {
        return $this->hasMany(WasteRecord::class);
    }
}
