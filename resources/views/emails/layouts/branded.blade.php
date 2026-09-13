{{-- Branded HTML email shell --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:Georgia,'Times New Roman',serif;color:#1a1f2c;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f6f8;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border:1px solid #e2e6ee;">
                @if ($useBranding)
                <tr>
                    <td style="padding:24px 28px 12px;border-bottom:1px solid #eef1f6;">
                        @if (! empty($logoUrl))
                            <img src="{{ $logoUrl }}" alt="{{ $platformName }}" style="max-height:40px;max-width:180px;display:block;">
                        @else
                            <div style="font-size:22px;font-weight:700;letter-spacing:0.02em;color:#0f766e;">{{ $platformName }}</div>
                        @endif
                    </td>
                </tr>
                @endif
                <tr>
                    <td style="padding:28px;font-size:16px;line-height:1.6;color:#243044;">
                        {!! $bodyHtml !!}
                    </td>
                </tr>
                @if ($useBranding)
                <tr>
                    <td style="padding:16px 28px 24px;border-top:1px solid #eef1f6;font-size:12px;line-height:1.5;color:#6b7280;">
                        {{ $platformName }}
                        @if (! empty($supportEmail))
                            · <a href="mailto:{{ $supportEmail }}" style="color:#0f766e;text-decoration:none;">{{ $supportEmail }}</a>
                        @endif
                    </td>
                </tr>
                @endif
            </table>
        </td>
    </tr>
</table>
</body>
</html>
