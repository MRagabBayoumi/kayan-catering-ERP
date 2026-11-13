<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlindStockAudit extends Model
{
    use HasFactory;

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_REVIEWED = 'reviewed';

    public const STATUS_SETTLED = 'settled';

    protected $fillable = [
        'audit_number',
        'warehouse_id',
        'storekeeper_id',
        'accountant_id',
        'audited_at',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'audited_at' => 'datetime',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function storekeeper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'storekeeper_id');
    }

    public function accountant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accountant_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BlindStockAuditItem::class);
    }
}
