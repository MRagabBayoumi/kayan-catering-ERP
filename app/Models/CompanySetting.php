<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CompanySetting extends Model
{
    use Auditable;

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

    public static function getSettings(): self
    {
        return self::instance();
    }

    public function isMaintenance(): bool
    {
        return (bool) ($this->settings['maintenance_mode'] ?? false);
    }

    public function getCompanyPhoneAttribute(): ?string
    {
        return $this->phone;
    }

    public function setCompanyPhoneAttribute(?string $value): void
    {
        $this->attributes['phone'] = $value;
    }

    public function getProgramStatusAttribute(): string
    {
        return $this->isMaintenance() ? 'maintenance' : 'active';
    }

    public function setProgramStatusAttribute(string $value): void
    {
        $current = $this->settings ?? [];
        $current['maintenance_mode'] = ($value === 'maintenance');
        $this->settings = $current;
    }

    public function getTelegramEnabledAttribute(): bool
    {
        return (bool) ($this->settings['telegram_enabled'] ?? false);
    }

    public function setTelegramEnabledAttribute(bool $value): void
    {
        $current = $this->settings ?? [];
        $current['telegram_enabled'] = $value;
        $this->settings = $current;
    }

    public function getTelegramBotTokenAttribute(): ?string
    {
        return $this->settings['telegram_bot_token'] ?? null;
    }

    public function setTelegramBotTokenAttribute(?string $value): void
    {
        $current = $this->settings ?? [];
        $current['telegram_bot_token'] = $value;
        $this->settings = $current;
    }

    public function getTelegramChatIdAttribute(): ?string
    {
        return $this->settings['telegram_chat_id'] ?? null;
    }

    public function setTelegramChatIdAttribute(?string $value): void
    {
        $current = $this->settings ?? [];
        $current['telegram_chat_id'] = $value;
        $this->settings = $current;
    }

    public function getTelegramNotifyShiftCloseAttribute(): bool
    {
        return (bool) ($this->settings['telegram_notify_shift_close'] ?? true);
    }

    public function setTelegramNotifyShiftCloseAttribute(bool $value): void
    {
        $current = $this->settings ?? [];
        $current['telegram_notify_shift_close'] = $value;
        $this->settings = $current;
    }

    public function getTelegramNotifyLowStockAttribute(): bool
    {
        return (bool) ($this->settings['telegram_notify_low_stock'] ?? true);
    }

    public function setTelegramNotifyLowStockAttribute(bool $value): void
    {
        $current = $this->settings ?? [];
        $current['telegram_notify_low_stock'] = $value;
        $this->settings = $current;
    }

    public function getTelegramNotifySalesAttribute(): bool
    {
        return (bool) ($this->settings['telegram_notify_sales'] ?? false);
    }

    public function setTelegramNotifySalesAttribute(bool $value): void
    {
        $current = $this->settings ?? [];
        $current['telegram_notify_sales'] = $value;
        $this->settings = $current;
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        if (! Storage::disk('public')->exists($this->logo_path)) {
            return null;
        }

        return '/storage/'.ltrim($this->logo_path, '/');
    }
}
