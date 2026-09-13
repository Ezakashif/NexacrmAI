<?php

namespace App\Services\SuperAdmin;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;

class PlatformSettingsService
{
    public const CACHE_KEY = 'platform_settings';

    /**
     * @return array<string, string|null>
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function () {
            try {
                if (! \Illuminate\Support\Facades\Schema::hasTable('platform_settings')) {
                    return [];
                }

                return PlatformSetting::query()
                    ->pluck('value', 'key')
                    ->all();
            } catch (\Throwable) {
                return [];
            }
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key);

        if ($value === null) {
            return $default;
        }

        return in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Whether CRM users must verify email before accessing tenant routes.
     *
     * Defaults to enabled when the setting has not been seeded yet.
     */
    public function emailVerificationRequired(): bool
    {
        return $this->getBool('email_verification_required', true);
    }

    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->get($key);

        return is_numeric($value) ? (int) $value : $default;
    }

    public function getDecrypted(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key);

        if (blank($value)) {
            return $default;
        }

        try {
            return Crypt::decryptString((string) $value);
        } catch (\Throwable) {
            return $default;
        }
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }

            PlatformSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value === null ? null : (string) $value],
            );
        }

        Cache::forget(self::CACHE_KEY);
    }

    public function setEncrypted(string $key, ?string $value): void
    {
        if (blank($value)) {
            return;
        }

        $this->setMany([$key => Crypt::encryptString($value)]);
    }

    public function platformName(): string
    {
        return (string) ($this->get('platform_name') ?: config('app.name'));
    }

    /**
     * Public-relative path suitable for asset(), e.g. "storage/platform/logo.png".
     */
    public function logoAssetPath(?string $variant = null): ?string
    {
        if ($variant === 'light') {
            $light = $this->get('platform_logo_light_path');

            if (filled($light)) {
                return 'storage/'.ltrim((string) $light, '/');
            }

            if (is_file(public_path('branding/algos-logo-light.png'))) {
                return 'branding/algos-logo-light.png';
            }
        }

        $path = $this->get('platform_logo_path');

        if (filled($path)) {
            return 'storage/'.ltrim((string) $path, '/');
        }

        if (is_file(public_path('branding/algos-logo.png'))) {
            return 'branding/algos-logo.png';
        }

        return null;
    }

    public function logoUrl(?string $variant = null): ?string
    {
        $assetPath = $this->logoAssetPath($variant === 'light' ? 'light' : null);

        // Fall back to the primary logo when a light variant is requested but missing.
        if ($assetPath === null && $variant === 'light') {
            $assetPath = $this->logoAssetPath();
        }

        if ($assetPath === null) {
            return null;
        }

        $absolute = public_path($assetPath);
        $version = is_file($absolute) ? (string) filemtime($absolute) : (string) time();

        return asset($assetPath).'?v='.$version;
    }

    public function faviconUrl(): ?string
    {
        $path = $this->get('platform_favicon_path');

        if (filled($path) && is_file(public_path('storage/'.ltrim((string) $path, '/')))) {
            return asset('storage/'.ltrim((string) $path, '/')).'?v='.filemtime(public_path('storage/'.ltrim((string) $path, '/')));
        }

        return null;
    }

    /**
     * Apply platform branding and runtime settings (timezone, mail, currency).
     */
    public function applyBranding(): void
    {
        $name = e($this->platformName());

        // CRM sidebar is dark (`sidebar-dark-primary`) — use the light logo there.
        // Login screens are light — use the primary/dark-ink logo.
        $sidebarLogo = $this->logoAssetPath('light') ?: $this->logoAssetPath();
        $authLogo = $this->logoAssetPath() ?: $sidebarLogo;

        $timezone = (string) ($this->get('default_timezone') ?: config('app.timezone', 'UTC'));
        $currency = strtoupper((string) ($this->get('default_currency') ?: 'USD'));
        $mailFromName = $this->get('mail_from_name');
        $mailFromAddress = $this->get('mail_from_address');

        config([
            'app.name' => $this->platformName(),
            'app.timezone' => $timezone,
            'app.currency' => $currency,
            'adminlte.logo' => '<b>'.$name.'</b>',
            'adminlte.logo_img_alt' => $this->platformName(),
        ]);

        date_default_timezone_set($timezone);

        if (filled($mailFromName) || filled($mailFromAddress)) {
            config([
                'mail.from.name' => filled($mailFromName) ? (string) $mailFromName : config('mail.from.name'),
                'mail.from.address' => filled($mailFromAddress) ? (string) $mailFromAddress : config('mail.from.address'),
            ]);
        }

        $smtpHost = $this->get('smtp_host');
        // Env API/dev mailers win so platform SMTP cannot override Resend/etc.
        // or hang auth on unreachable SMTP hosts in deploy environments.
        $envMailer = strtolower((string) config('mail.default'));
        $envForcesNonSmtp = in_array($envMailer, [
            'log', 'array', 'resend', 'postmark', 'ses', 'ses-v2', 'mailgun',
        ], true);

        if (filled($smtpHost) && ! $envForcesNonSmtp) {
            $smtpHost = $this->normalizeSmtpHost((string) $smtpHost);
            $smtpEncryption = $this->get('smtp_encryption');
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => $smtpHost,
                'mail.mailers.smtp.port' => $this->getInt('smtp_port', 587),
                'mail.mailers.smtp.username' => $this->get('smtp_username'),
                'mail.mailers.smtp.password' => $this->getDecrypted('smtp_password'),
                'mail.mailers.smtp.scheme' => $smtpEncryption === 'ssl' ? 'smtps' : null,
                // Fail fast instead of hitting max_execution_time (uncaught FatalError).
                'mail.mailers.smtp.timeout' => 5,
            ]);
        }

        if ($sidebarLogo !== null) {
            config([
                'adminlte.logo_img' => $sidebarLogo,
                'adminlte.logo_img_class' => 'brand-image',
                'adminlte.logo' => '',
                'adminlte.auth_logo.enabled' => true,
                'adminlte.auth_logo.img.path' => $authLogo,
                'adminlte.auth_logo.img.alt' => $this->platformName(),
                'adminlte.auth_logo.img.class' => '',
                'adminlte.auth_logo.img.width' => null,
                'adminlte.auth_logo.img.height' => 56,
            ]);
        }

        config([
            'adminlte.register_url' => $this->getBool('registration_enabled') ? 'register' : false,
        ]);

        $this->applyMarketingContactDetails();

        // Normalize .env MAIL_HOST as well (common typo: smpt.gmail.com).
        $envHost = config('mail.mailers.smtp.host');
        if (filled($envHost)) {
            config(['mail.mailers.smtp.host' => $this->normalizeSmtpHost((string) $envHost)]);
        }
    }

    public function announcement(): ?string
    {
        $value = $this->get('broadcast_announcement');

        return filled($value) ? (string) $value : null;
    }

    /**
     * Overlay Super Admin company contact / social settings onto marketing config
     * so footer, contact page, SEO, and mail all share the same public details.
     */
    private function applyMarketingContactDetails(): void
    {
        $brand = $this->platformName();
        $contact = config('marketing.contact', []);
        $social = config('marketing.social', []);

        $email = $this->get('company_email');
        $phone = $this->get('company_phone');
        $address = $this->get('company_address');
        $linkedin = $this->get('company_linkedin_url');
        $facebook = $this->get('company_facebook_url');
        $twitter = $this->get('company_twitter_url');
        $github = $this->get('company_github_url');

        config([
            'marketing.name' => $brand,
            'marketing.contact.email' => filled($email) ? (string) $email : ($contact['email'] ?? null),
            'marketing.contact.phone' => filled($phone) ? (string) $phone : ($contact['phone'] ?? null),
            // Address is optional. An explicit empty Super Admin value hides it everywhere
            // (no config placeholder fallback). Env/config is used only when unset.
            'marketing.contact.address' => filled($address)
                ? (string) $address
                : (array_key_exists('company_address', $this->all())
                    ? null
                    : (filled($contact['address'] ?? null) ? (string) $contact['address'] : null)),
            'marketing.social.linkedin' => filled($linkedin) ? (string) $linkedin : ($social['linkedin'] ?? null),
            'marketing.social.facebook' => filled($facebook) ? (string) $facebook : ($social['facebook'] ?? null),
            'marketing.social.twitter' => filled($twitter) ? (string) $twitter : ($social['twitter'] ?? null),
            'marketing.social.github' => filled($github) ? (string) $github : ($social['github'] ?? null),
        ]);
    }

    private function normalizeSmtpHost(string $host): string
    {
        $host = strtolower(trim($host));
        $host = preg_replace('#^(smtp|smtps)://#', '', $host) ?? $host;
        $host = rtrim($host, '/');

        return preg_replace('/^smpt\./', 'smtp.', $host) ?? $host;
    }
}
