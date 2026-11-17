<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntryLine extends Model
{
    use HasFactory;

    public const ACCOUNT_TREASURY = 'treasury';

    public const ACCOUNT_BANK = 'bank';

    public const ACCOUNT_SUPPLIER = 'supplier';

    public const ACCOUNT_HOSPITAL_CUSTODY = 'hospital_custody';

    public const ACCOUNT_EXPENSE = 'expense';

    public const ACCOUNT_REVENUE = 'revenue';

    public const ACCOUNT_CASH_SHORTAGE = 'cash_shortage';

    public const ACCOUNT_OWNER_EQUITY = 'owner_equity';

    protected $fillable = [
        'journal_entry_id',
        'chart_of_account_id',
        'cost_center_id',
        'account_type',
        'account_id',
        'account_name',
        'debit',
        'credit',
        'notes',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function chartOfAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }
}
