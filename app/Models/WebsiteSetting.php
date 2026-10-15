<?php

namespace App\Models;

use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $primary_color_key
 * @property string|null $logo_path
 * @property string|null $favicon_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['primary_color_key', 'logo_path', 'favicon_path'])]
class WebsiteSetting extends Model
{
    public const CACHE_KEY = 'website_settings';

    public const DEFAULT_COLOR_KEY = 'amber';

    /**
     * Preset warna primer Filament yang didukung.
     *
     * @return array<string, string>
     */
    public static function colorOptions(): array
    {
        return [
            'amber' => 'Amber',
            'blue' => 'Blue',
            'emerald' => 'Emerald',
            'indigo' => 'Indigo',
            'orange' => 'Orange',
            'rose' => 'Rose',
            'sky' => 'Sky',
            'teal' => 'Teal',
            'violet' => 'Violet',
        ];
    }

    /**
     * Palet warna Filament untuk key yang tersimpan.
     *
     * @return array<int, string>
     */
    public function primaryColor(): array
    {
        return self::colorPalette($this->primary_color_key);
    }

    /**
     * @return array<int, string>
     */
    public static function colorPalette(?string $key): array
    {
        return match ($key) {
            'blue' => Color::Blue,
            'emerald' => Color::Emerald,
            'indigo' => Color::Indigo,
            'orange' => Color::Orange,
            'rose' => Color::Rose,
            'sky' => Color::Sky,
            'teal' => Color::Teal,
            'violet' => Color::Violet,
            default => Color::Amber,
        };
    }

    public function logoUrl(): ?string
    {
        if (! $this->logo_path || ! Storage::disk('public')->exists($this->logo_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }

    public function faviconUrl(): ?string
    {
        if (! $this->favicon_path || ! Storage::disk('public')->exists($this->favicon_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->favicon_path);
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
                ['primary_color_key' => self::DEFAULT_COLOR_KEY]
            )->refresh();

            Cache::forever(self::CACHE_KEY, $settings);
        }

        return $settings;
    }
}
