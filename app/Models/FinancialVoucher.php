<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialVoucher extends Model
{
    use HasFactory;

    public const TYPE_PAYMENT = 'payment_voucher';

    public const TYPE_RECEIPT = 'receipt_voucher';

    public const TYPE_BANK_TRANSFER = 'bank_transfer';

    protected $fillable = [
        'voucher_number',
        'type',
        'treasury_id',
        'supplier_id',
        'hospital_id',
        'amount',
        'purpose',
        'payment_method',
        'created_by',
        'voucher_date',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'voucher_date' => 'date',
    ];

    public function treasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
