<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptOrder extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending_approval';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const PAYER_HOSPITAL = 'hospital';

    public const PAYER_SUPPLIER = 'supplier';

    public const PAYER_USER = 'user';

    public const PAYER_OTHER = 'other';

    protected $fillable = [
        'order_number',
        'order_date',
        'amount',
        'payer_type',
        'hospital_id',
        'supplier_id',
        'user_id',
        'payer_name',
        'treasury_id',
        'payment_method',
        'reference_number',
        'purpose',
        'notes',
        'status',
        'rejection_reason',
        'created_by',
        'approved_by',
        'approved_at',
        'financial_voucher_id',
    ];

    protected $casts = [
        'order_date' => 'date',
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function treasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function financialVoucher(): BelongsTo
    {
        return $this->belongsTo(FinancialVoucher::class);
    }

    public function getPayerLabelAttribute(): string
    {
        return match ($this->payer_type) {
            self::PAYER_HOSPITAL => $this->hospital?->name ?? 'مستشفى / عميل',
            self::PAYER_SUPPLIER => $this->supplier?->name ?? 'مورد',
            self::PAYER_USER => $this->user?->name ?? 'موظف / مسترد عهدة',
            default => $this->payer_name ?: 'جهة أخرى',
        };
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}
