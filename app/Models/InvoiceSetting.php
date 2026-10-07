<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property string $company_name
 * @property string|null $company_address
 * @property string|null $company_phone
 * @property string|null $company_email
 * @property string|null $logo_path
 * @property string|null $signature_path
 * @property string|null $signature_name
 * @property string $invoice_prefix
 * @property string $payment_prefix
 * @property bool $yearly_reset
 * @property int $default_due_days
 * @property float|null $default_tax_rate
 * @property string|null $default_terms
 * @property string|null $default_notes
 * @property string $email_subject_template
 * @property string|null $email_body_template
 * @property bool $allow_overpayment
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['company_name', 'company_address', 'company_phone', 'company_email', 'logo_path', 'signature_path', 'signature_name', 'invoice_prefix', 'payment_prefix', 'yearly_reset', 'default_due_days', 'default_tax_rate', 'default_terms', 'default_notes', 'email_subject_template', 'email_body_template', 'allow_overpayment'])]
class InvoiceSetting extends Model
{
    public const CACHE_KEY = 'invoice_settings';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'yearly_reset' => 'boolean',
            'default_tax_rate' => 'decimal:2',
            'allow_overpayment' => 'boolean',
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
                    'company_name' => 'PT Contoh Sejahtera',
                    'email_subject_template' => 'Invoice {invoice_number} dari {company_name}',
                    'email_body_template' => "Yth. {client_name},\n\nTerlampir invoice {invoice_number} sebesar Rp{amount_due} dengan jatuh tempo {due_date}.\n\nTerima kasih.",
                ]
            )->refresh();

            Cache::forever(self::CACHE_KEY, $settings);
        }

        return $settings;
    }
}
