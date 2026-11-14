<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteKitchenIssue extends Model
{
    use HasFactory;

    protected $fillable = [
        'hospital_id',
        'issue_date',
        'issue_number',
        'product_id',
        'quantity_issued',
        'unit',
        'issued_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'quantity_issued' => 'decimal:4',
        ];
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
