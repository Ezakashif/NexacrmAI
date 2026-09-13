<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $documentTitle }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; line-height: 1.55; }
        h1 { font-size: 20px; margin: 0 0 6px; }
        h2 { font-size: 16px; margin: 22px 0 8px; page-break-after: avoid; }
        h3 { font-size: 13px; margin: 16px 0 6px; page-break-after: avoid; }
        h4 { font-size: 12px; margin: 12px 0 4px; }
        .meta { color: #555; margin-bottom: 18px; font-size: 10px; }
        .section { page-break-before: always; }
        .section:first-of-type { page-break-before: auto; }
        .section-title { font-size: 18px; margin: 0 0 4px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        .section-path { color: #666; font-size: 9px; margin-bottom: 12px; }
        pre { background: #f5f5f5; border: 1px solid #ddd; padding: 8px; font-size: 9px; white-space: pre-wrap; word-wrap: break-word; }
        code { font-size: 9px; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0 12px; }
        th, td { border: 1px solid #ccc; padding: 5px 7px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        blockquote { border-left: 3px solid #17a2b8; margin: 8px 0; padding: 4px 10px; color: #444; }
        ul, ol { margin: 6px 0 10px 18px; }
        p { margin: 0 0 8px; }
        a { color: #0d6efd; text-decoration: none; }
        .toc { margin: 12px 0 24px; }
        .toc-section { font-weight: bold; margin-top: 10px; }
        .toc-item { margin-left: 12px; }
        .cover { margin-bottom: 28px; }
    </style>
</head>
<body>
    <div class="cover">
        <h1>{{ $documentTitle }}</h1>
        <div class="meta">
            Generated {{ $generatedAt->toDayDateTimeString() }}
            @if (! empty($scopeLabel))
                · {{ $scopeLabel }}
            @endif
        </div>

        @if (! empty($toc))
            <h2>Contents</h2>
            <div class="toc">
                @foreach ($toc as $group)
                    <div class="toc-section">{{ $group['section'] }}</div>
                    @foreach ($group['items'] as $item)
                        <div class="toc-item">{{ $item['title'] }}</div>
                    @endforeach
                @endforeach
            </div>
        @endif
    </div>

    @foreach ($sections as $section)
        <div class="section">
            <div class="section-title">{{ $section['title'] }}</div>
            <div class="section-path">docs/{{ $section['path'] === 'README' ? 'README.md' : $section['path'].'.md' }}</div>
            <div class="crm-docs-pdf-body">
                {!! $section['html'] !!}
            </div>
        </div>
    @endforeach
</body>
</html>
