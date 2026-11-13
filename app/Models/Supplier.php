<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'phone',
        'contact_person',
        'current_balance',
        'credit_limit',
        'payment_terms_days',
    ];

    protected $casts = [
        'current_balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'payment_terms_days' => 'integer',
    ];

    public function paymentSchedules(): HasMany
    {
        return $this->hasMany(SupplierPaymentSchedule::class);
    }

    public function financialVouchers(): HasMany
    {
        return $this->hasMany(FinancialVoucher::class);
    }
}
