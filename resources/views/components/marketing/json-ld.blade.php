@props([
    'type' => 'organization',
])

@php
    $brand = config('marketing.name');
    $contact = config('marketing.contact');
    $social = array_values(array_filter(
        config('marketing.social', []),
        fn ($url) => filled($url) && $url !== '#'
    ));
    $logoUrl = app(\App\Services\SuperAdmin\PlatformSettingsService::class)->logoUrl()
        ?: url('/branding/algos-logo.png');

    $organization = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $brand,
        'url' => url('/'),
        'logo' => $logoUrl,
        'email' => $contact['email'] ?? null,
        'telephone' => $contact['phone'] ?? null,
        'sameAs' => $social,
    ];

    if (filled($contact['address'] ?? null)) {
        $organization['address'] = [
            '@type' => 'PostalAddress',
            'streetAddress' => $contact['address'],
        ];
    }

    $website = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $brand,
        'url' => url('/'),
        'description' => config('marketing.description'),
        'publisher' => [
            '@type' => 'Organization',
            'name' => $brand,
        ],
    ];

    $payload = $type === 'website' ? $website : $organization;
@endphp

<script type="application/ld+json">{!! json_encode($payload, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
