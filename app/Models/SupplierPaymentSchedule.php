<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPaymentSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'invoice_reference',
        'due_date',
        'amount',
        'status',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
