<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyCashClosing extends Model
{
    use HasFactory;

    protected $fillable = [
        'closing_date',
        'opening_balance',
        'total_inflow',
        'total_outflow',
        'closing_balance',
        'site_custody_spent',
        'supplier_payments',
        'status',
        'notes',
        'closed_by',
    ];

    protected $casts = [
        'closing_date' => 'date',
        'opening_balance' => 'decimal:2',
        'total_inflow' => 'decimal:2',
        'total_outflow' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'site_custody_spent' => 'decimal:2',
        'supplier_payments' => 'decimal:2',
    ];

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
