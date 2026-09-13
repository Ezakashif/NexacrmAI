<style>
    .crm-docs-content { line-height: 1.6; color: #1e293b; }
    .crm-docs-content h1 { font-size: 1.875rem; font-weight: 800; letter-spacing: -0.02em; margin: 0 0 0.75rem; color: #0f172a; }
    .crm-docs-content h2 { font-size: 1.25rem; font-weight: 700; margin: 1.75rem 0 0.75rem; color: #0f172a; }
    .crm-docs-content h3 { font-size: 1.05rem; font-weight: 700; margin: 1.35rem 0 0.5rem; color: #0f172a; }
    .crm-docs-content p, .crm-docs-content ul, .crm-docs-content ol { margin-bottom: 0.85rem; }
    .crm-docs-content a { color: #0369a1; text-decoration: underline; text-underline-offset: 2px; }
    .crm-docs-content a:hover { color: #0c4a6e; }
    .crm-docs-content pre { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: .5rem; padding: .85rem 1rem; overflow-x: auto; margin-bottom: 1rem; }
    .crm-docs-content code { font-size: .875em; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
    .crm-docs-content :not(pre) > code { background: #f1f5f9; padding: .1rem .35rem; border-radius: .25rem; }
    .crm-docs-content table { width: 100%; margin: 0.75rem 0 1.25rem; border-collapse: collapse; font-size: 0.925rem; }
    .crm-docs-content table th, .crm-docs-content table td { border-bottom: 1px solid #e2e8f0; padding: .65rem .75rem; text-align: left; vertical-align: top; }
    .crm-docs-content table th { font-weight: 700; color: #334155; background: #f8fafc; }
    .crm-docs-content blockquote { border-left: 4px solid #0ea5e9; background: #f0f9ff; padding: .85rem 1rem; margin: 1rem 0; color: #334155; border-radius: 0 .5rem .5rem 0; }
    .crm-docs-content blockquote p:last-child { margin-bottom: 0; }
    .crm-docs-content hr { border: 0; border-top: 1px solid #e2e8f0; margin: 1.5rem 0; }
</style>

@php
    $docsRoutes = $docsRoutes ?? [
        'index' => 'docs.index',
        'show' => 'docs.show',
        'pdf' => 'docs.pdf',
        'pdfPage' => 'docs.pdf.page',
    ];
@endphp

<div class="grid gap-6 lg:grid-cols-[16rem_minmax(0,1fr)] xl:grid-cols-[18rem_minmax(0,1fr)]">
    <aside class="lg:sticky lg:top-24 lg:self-start">
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between gap-2 border-b border-slate-200 bg-slate-50 px-4 py-3">
                <strong class="text-sm font-semibold text-slate-900">Contents</strong>
                <a href="{{ route($docsRoutes['pdf']) }}"
                   class="inline-flex items-center gap-1 rounded-md border border-sky-200 bg-white px-2 py-1 text-xs font-semibold text-sky-700 transition hover:bg-sky-50"
                   title="Download all documentation as PDF">
                    PDF
                </a>
            </div>
            <nav class="max-h-[70vh] overflow-y-auto text-sm" aria-label="Documentation contents">
                @foreach ($nav as $group)
                    <div class="bg-slate-50 px-4 py-2 text-[0.68rem] font-bold uppercase tracking-wide text-slate-500">
                        {{ $group['section'] }}
                    </div>
                    @foreach ($group['items'] as $item)
                        <a href="{{ $item['url'] }}"
                           class="block border-t border-slate-100 px-4 py-2.5 transition {{ $path === $item['path'] ? 'bg-sky-600 font-semibold text-white' : 'text-slate-700 hover:bg-slate-50' }}">
                            {{ $item['title'] }}
                        </a>
                    @endforeach
                @endforeach
            </nav>
        </div>
    </aside>

    <div class="min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3 sm:px-5">
            <strong class="text-sm font-semibold text-slate-900">{{ $title }}</strong>
            <div class="flex flex-wrap items-center gap-2">
                <code class="text-xs text-slate-500">docs/{{ $path === 'README' ? 'README.md' : $path.'.md' }}</code>
                <a href="{{ route($docsRoutes['pdfPage'], ['path' => $path === 'README' ? 'README' : $path]) }}"
                   class="inline-flex items-center rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                   title="Download this page as PDF">
                    PDF
                </a>
                <a href="{{ route($docsRoutes['pdf']) }}"
                   class="inline-flex items-center rounded-md border border-sky-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-sky-700 transition hover:bg-sky-50"
                   title="Download all documentation as PDF">
                    Download all
                </a>
            </div>
        </div>
        <div class="crm-docs-content px-4 py-5 sm:px-6 sm:py-6">
            {!! $html !!}
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.crm-docs-content pre code.language-mermaid').forEach(function (block) {
            var pre = block.parentElement;
            var div = document.createElement('div');
            div.className = 'mermaid';
            div.textContent = block.textContent;
            pre.replaceWith(div);
        });
        if (window.mermaid) {
            mermaid.initialize({ startOnLoad: true, theme: 'neutral' });
        }
    });
</script>
