<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasteRecord extends Model
{
    use HasFactory;

    public const CATEGORY_NORMAL_COOKING_LOSS = 'normal_cooking_loss';

    public const CATEGORY_BUTCHERY_SPOILAGE = 'butchery_spoilage';

    public const CATEGORY_EXPIRED_BATCH = 'expired_batch';

    public const CATEGORY_TRANSIT_DAMAGE = 'transit_damage';

    public const CATEGORY_KITCHEN_BURN = 'kitchen_burn';

    public const CATEGORY_UNEXPLAINED_SHORTAGE = 'unexplained_shortage';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'waste_number',
        'waste_date',
        'warehouse_id',
        'hospital_id',
        'cost_center_id',
        'product_id',
        'inventory_batch_id',
        'quantity',
        'unit',
        'unit_cost',
        'total_cost_value',
        'waste_category',
        'is_abnormal',
        'reason',
        'reported_by',
        'approved_by',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'waste_date' => 'date',
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'total_cost_value' => 'decimal:2',
            'is_abnormal' => 'boolean',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'inventory_batch_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
