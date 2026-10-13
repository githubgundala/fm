<?php

namespace App\Services;

use App\Models\OutgoingMailSetting;
use Illuminate\Support\Facades\Schema;
use Throwable;

class OutgoingMailConfigurator
{
    /**
     * Override the runtime mail configuration from the stored settings.
     *
     * Silently skips when the settings are inactive, unconfigured, or the
     * table does not exist yet (fresh install / running migrations).
     */
    public function apply(): void
    {
        try {
            if (! Schema::hasTable('outgoing_mail_settings')) {
                return;
            }

            $settings = OutgoingMailSetting::cached();

            if (! $settings->is_active || trim($settings->host) === '' || trim($settings->username) === '') {
                return;
            }

            config()->set('mail.default', 'smtp');

            config()->set('mail.mailers.smtp', array_merge(
                (array) config('mail.mailers.smtp', []),
                [
                    'transport' => 'smtp',
                    'scheme' => $settings->encryption === 'ssl' ? 'smtps' : 'smtp',
                    'host' => $settings->host,
                    'port' => $settings->port,
                    'username' => $settings->username,
                    'password' => $settings->password,
                ]
            ));

            config()->set('mail.from.address', $settings->from_address !== '' ? $settings->from_address : $settings->username);
            config()->set('mail.from.name', $settings->from_name !== '' && $settings->from_name !== null ? $settings->from_name : config('app.name'));
        } catch (Throwable) {
            // Never break boot (migrations, queue workers, tests) on mail config.
        }
    }
}
