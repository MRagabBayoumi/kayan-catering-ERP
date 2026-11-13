<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Treasury extends Model
{
    use HasFactory;

    public const TYPE_CASHBOX = 'cashbox';

    public const TYPE_BANK = 'bank';

    public const TYPE_SITE_CUSTODY = 'site_custody';

    protected $fillable = [
        'name',
        'type',
        'hospital_id',
        'balance',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(FinancialVoucher::class);
    }
}
