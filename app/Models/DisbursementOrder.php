<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisbursementOrder extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const BENEFICIARY_SUPPLIER = 'supplier';

    public const BENEFICIARY_HOSPITAL = 'hospital';

    public const BENEFICIARY_USER = 'user';

    public const BENEFICIARY_OTHER = 'other';

    protected $fillable = [
        'order_number',
        'order_date',
        'amount',
        'beneficiary_type',
        'supplier_id',
        'hospital_id',
        'user_id',
        'beneficiary_name',
        'purpose',
        'notes',
        'status',
        'created_by',
        'financial_voucher_id',
        'executed_by',
        'executed_at',
        'execution_notes',
    ];

    protected $casts = [
        'order_date' => 'date',
        'amount' => 'decimal:2',
        'executed_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    public function financialVoucher(): BelongsTo
    {
        return $this->belongsTo(FinancialVoucher::class);
    }

    public function getBeneficiaryLabelAttribute(): string
    {
        return match ($this->beneficiary_type) {
            self::BENEFICIARY_SUPPLIER => $this->supplier?->name ?? 'مورد',
            self::BENEFICIARY_HOSPITAL => $this->hospital?->name ?? 'موقع مستشفى',
            self::BENEFICIARY_USER => $this->user?->name ?? 'موظف / صاحب عهدة',
            default => $this->beneficiary_name ?: 'جهة أخرى',
        };
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
