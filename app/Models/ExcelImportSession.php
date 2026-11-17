<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExcelImportSession extends Model
{
    use HasFactory;

    public const STATUS_STAGED = 'staged';

    public const STATUS_VALIDATED = 'validated';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_APPLIED = 'applied';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'session_uuid',
        'file_name',
        'import_type',
        'total_rows',
        'valid_rows',
        'invalid_rows',
        'status',
        'validation_errors',
        'staged_data',
        'uploaded_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'total_rows' => 'integer',
            'valid_rows' => 'integer',
            'invalid_rows' => 'integer',
            'validation_errors' => 'array',
            'staged_data' => 'array',
            'approved_at' => 'datetime',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
