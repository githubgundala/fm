<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class OutgoingMailTester
{
    public function __construct(
        private OutgoingMailConfigurator $configurator,
    ) {}

    /**
     * Send a test email using the given (possibly unsaved) settings.
     *
     * @param  array{host: string, port: int, encryption: string, username: string, password: ?string, from_address: string, from_name: ?string, reply_to: ?string}  $config
     */
    public function sendTest(array $config, string $to): void
    {
        $host = trim((string) ($config['host'] ?? ''));
        $port = (int) ($config['port'] ?? 0);
        $username = trim((string) ($config['username'] ?? ''));
        $password = $config['password'] ?? null;
        $fromAddress = trim((string) ($config['from_address'] ?? ''));
        $encryption = strtolower((string) ($config['encryption'] ?? 'ssl'));

        if ($host === '' || $port < 1 || $port > 65535) {
            throw new RuntimeException('Host dan port SMTP tidak valid.');
        }

        if ($username === '' || ! filled($password)) {
            throw new RuntimeException('Username dan password SMTP wajib diisi untuk tes kirim.');
        }

        if ($fromAddress === '' || filter_var($fromAddress, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('Alamat pengirim (from) tidak valid.');
        }

        if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('Alamat email tujuan tidak valid.');
        }

        if (! in_array($encryption, ['ssl', 'tls', 'none'], true)) {
            throw new RuntimeException('Enkripsi harus SSL, TLS, atau tanpa enkripsi.');
        }

        $originalSmtp = config('mail.mailers.smtp');
        $originalFrom = config('mail.from');

        config()->set('mail.mailers.smtp-test', [
            'transport' => 'smtp',
            'scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
            'host' => $host,
            'port' => $port,
            'username' => $username,
            'password' => $password,
            'timeout' => 15,
        ]);

        try {
            Mail::mailer('smtp-test')->raw(
                'Ini adalah email tes dari aplikasi. Jika Anda menerima email ini, pengaturan SMTP sudah benar.',
                function ($message) use ($to, $config, $fromAddress): void {
                    $message->to($to)
                        ->subject('Tes Pengaturan Email')
                        ->from($fromAddress, $config['from_name'] ?? config('app.name'));

                    if (filled($config['reply_to'] ?? null)) {
                        $message->replyTo((string) $config['reply_to']);
                    }
                }
            );
        } catch (Throwable $e) {
            throw new RuntimeException('Gagal mengirim email tes: '.$e->getMessage());
        } finally {
            config()->set('mail.mailers.smtp-test', null);
            Mail::purge('smtp-test');

            // Restore the boot-time SMTP config derived from stored settings.
            config()->set('mail.mailers.smtp', $originalSmtp);
            config()->set('mail.from', $originalFrom);
            $this->configurator->apply();
        }
    }
}
