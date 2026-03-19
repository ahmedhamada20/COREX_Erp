<?php

namespace App\Services\Tenants;

use App\Models\Settings;
use Illuminate\Support\Facades\Cache;

class SettingsCacheService
{
    private const TTL = 3600; // 1 hour

    public function get(int $tenantId): ?Settings
    {
        return Cache::remember(
            key: "tenant.{$tenantId}.settings",
            ttl: self::TTL,
            callback: fn () => Settings::where('user_id', $tenantId)->first()
        );
    }

    public function forget(int $tenantId): void
    {
        Cache::forget("tenant.{$tenantId}.settings");
    }

    /**
     * Returns a specific setting value with a default fallback.
     */
    public function value(int $tenantId, string $key, mixed $default = null): mixed
    {
        return $this->get($tenantId)?->{$key} ?? $default;
    }
}
