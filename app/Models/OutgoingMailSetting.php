<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property string $host
 * @property int $port
 * @property string $encryption
 * @property string $username
 * @property string|null $password
 * @property string $from_address
 * @property string|null $from_name
 * @property string|null $reply_to
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['host', 'port', 'encryption', 'username', 'password', 'from_address', 'from_name', 'reply_to', 'is_active'])]
class OutgoingMailSetting extends Model
{
    public const CACHE_KEY = 'outgoing_mail_settings';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'password' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (): void {
            Cache::forget(self::CACHE_KEY);
        });
    }

    /**
     * Get the cached singleton settings row, creating defaults if missing.
     */
    public static function cached(): self
    {
        $settings = Cache::get(self::CACHE_KEY);

        if (! $settings instanceof self) {
            Cache::forget(self::CACHE_KEY);

            $settings = self::firstOrCreate(
                ['id' => 1],
                [
                    'host' => 'smtp.hostinger.com',
                    'port' => 465,
                    'encryption' => 'ssl',
                    'username' => '',
                    'from_address' => '',
                    'from_name' => '',
                    'is_active' => false,
                ]
            )->refresh();

            Cache::forever(self::CACHE_KEY, $settings);
        }

        return $settings;
    }
}
