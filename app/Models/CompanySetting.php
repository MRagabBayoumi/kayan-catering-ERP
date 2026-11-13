<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CompanySetting extends Model
{
    protected $table = 'company_settings';

    protected $fillable = [
        'company_name',
        'company_name_ar',
        'phone',
        'email',
        'tax_number',
        'commercial_register',
        'address',
        'logo_path',
        'currency',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public static function instance(): self
    {
        return self::firstOrCreate(
            ['id' => 1],
            [
                'company_name' => 'Kayan Catering ERP',
                'company_name_ar' => 'شركة كيان لتوريد الأغذية وإعاشة المستشفيات',
                'currency' => 'EGP',
            ]
        );
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }
}
