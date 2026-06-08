<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'description'];

    public static function get(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function set(string $key, $value)
    {
        return self::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function isSystemActive(): bool
    {
        return self::get('system_active', 'true') === 'true';
    }

    public static function isSubscriptionActive(): bool
    {
        $status = self::get('subscription_status', 'active');
        $expiry = self::get('subscription_expires_at');

        if ($status !== 'active') {
            return false;
        }

        if ($expiry && now()->greaterThan(\Illuminate\Support\Carbon::parse($expiry))) {
            return false;
        }

        return true;
    }

    public static function getSubscriptionExpiryDate(): ?\Illuminate\Support\Carbon
    {
        $expiry = self::get('subscription_expires_at');
        return $expiry ? \Illuminate\Support\Carbon::parse($expiry) : null;
    }
}
