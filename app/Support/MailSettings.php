<?php

namespace App\Support;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Applies the SMTP settings saved in the admin panel to the mail config. When no
 * settings are saved (or the table is not migrated yet), the .env values stay.
 */
class MailSettings
{
    /**
     * True when the platform can actually send email: SMTP is saved in the admin
     * panel, or the server's .env already points at a real mailer (not 'log').
     */
    public static function isConfigured(): bool
    {
        try {
            if (Schema::hasTable('app_settings') && (AppSetting::mail()['host'] ?? '') !== '') {
                return true;
            }
        } catch (Throwable) {
            // Fall through to the .env check.
        }

        return config('mail.default') !== 'log' && config('mail.default') !== 'array';
    }

    public static function apply(): void
    {
        try {
            if (! Schema::hasTable('app_settings')) {
                return;
            }

            $mail = AppSetting::mail();
        } catch (Throwable) {
            return;
        }

        if (($mail['host'] ?? '') === '') {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $mail['host'],
            'mail.mailers.smtp.port' => (int) ($mail['port'] ?? 587),
            'mail.mailers.smtp.username' => $mail['username'] ?: null,
            'mail.mailers.smtp.password' => $mail['password'] ?: null,
            'mail.mailers.smtp.scheme' => ($mail['encryption'] ?? 'tls') === 'ssl' ? 'smtps' : null,
            'mail.from.address' => $mail['from_address'] ?: config('mail.from.address'),
            'mail.from.name' => $mail['from_name'] ?: config('mail.from.name'),
        ]);
    }
}
