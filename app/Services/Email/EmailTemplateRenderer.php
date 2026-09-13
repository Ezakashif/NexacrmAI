<?php

namespace App\Services\Email;

use App\Services\SuperAdmin\PlatformSettingsService;

class EmailTemplateRenderer
{
    public function __construct(
        private readonly PlatformSettingsService $platformSettings,
    ) {}

    /**
     * @param  array<string, scalar|null>  $placeholders
     */
    public function replace(string $content, array $placeholders): string
    {
        $replacements = [];

        foreach ($placeholders as $key => $value) {
            $replacements['{{'.$key.'}}'] = $this->stringify($value);
            $replacements['{{ '.$key.' }}'] = $this->stringify($value);
        }

        return strtr($content, $replacements);
    }

    public function wrapBranded(string $htmlBody, string $subject, bool $useBranding = true): string
    {
        return view('emails.layouts.branded', [
            'subject' => $subject,
            'bodyHtml' => $htmlBody,
            'useBranding' => $useBranding,
            'platformName' => $this->platformSettings->platformName(),
            'logoUrl' => $this->platformSettings->logoUrl(),
            'supportEmail' => $this->platformSettings->get('mail_from_address') ?: config('mail.from.address'),
        ])->render();
    }

    private function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }
}
