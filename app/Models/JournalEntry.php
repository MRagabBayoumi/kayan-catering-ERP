<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    use HasFactory;

    public const SOURCE_DISBURSEMENT = 'disbursement';

    public const SOURCE_RECEIPT = 'receipt';

    public const SOURCE_CLOSING = 'closing';

    public const SOURCE_MANUAL = 'manual';

    protected $fillable = [
        'entry_number',
        'entry_date',
        'description',
        'source_type',
        'source_id',
        'created_by',
        'is_posted',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'is_posted' => 'boolean',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTotalDebitAttribute(): float
    {
        return (float) $this->lines->sum('debit');
    }

    public function getTotalCreditAttribute(): float
    {
        return (float) $this->lines->sum('credit');
    }

    public function isBalanced(): bool
    {
        return abs($this->total_debit - $this->total_credit) < 0.001;
    }
}
