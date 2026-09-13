<style>
    .crm-docs-content { line-height: 1.6; }
    .crm-docs-content h1, .crm-docs-content h2, .crm-docs-content h3 { margin-top: 1.25rem; }
    .crm-docs-content pre { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: .25rem; padding: .75rem; overflow-x: auto; }
    .crm-docs-content code { font-size: .9em; }
    .crm-docs-content table { width: 100%; margin-bottom: 1rem; }
    .crm-docs-content table th, .crm-docs-content table td { border: 1px solid #dee2e6; padding: .4rem .6rem; }
    .crm-docs-content blockquote { border-left: 4px solid #17a2b8; padding-left: .75rem; color: #555; }
</style>

@php
    $docsRoutes = $docsRoutes ?? [
        'index' => 'docs.index',
        'show' => 'docs.show',
        'pdf' => 'docs.pdf',
        'pdfPage' => 'docs.pdf.page',
    ];
@endphp

<div class="row">
    <div class="col-lg-3 mb-3">
        <div class="card card-outline card-secondary">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Contents</strong>
                <a href="{{ route($docsRoutes['pdf']) }}" class="btn btn-xs btn-outline-primary" title="Download all documentation as PDF">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
            </div>
            <div class="list-group list-group-flush small" style="max-height: 70vh; overflow-y: auto;">
                @foreach ($nav as $group)
                    <div class="list-group-item bg-light text-uppercase text-muted font-weight-bold"
                         style="letter-spacing: .03em; font-size: .72rem;">
                        {{ $group['section'] }}
                    </div>
                    @foreach ($group['items'] as $item)
                        <a href="{{ $item['url'] }}"
                           class="list-group-item list-group-item-action {{ $path === $item['path'] ? 'active' : '' }}">
                            {{ $item['title'] }}
                        </a>
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-lg-9 mb-3">
        <div class="card card-outline card-primary">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap: .5rem;">
                <strong>{{ $title }}</strong>
                <div class="d-flex align-items-center flex-wrap" style="gap: .5rem;">
                    <code class="small text-muted">docs/{{ $path === 'README' ? 'README.md' : $path.'.md' }}</code>
                    <a href="{{ route($docsRoutes['pdfPage'], ['path' => $path === 'README' ? 'README' : $path]) }}"
                       class="btn btn-sm btn-outline-secondary"
                       title="Download this page as PDF">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                    <a href="{{ route($docsRoutes['pdf']) }}"
                       class="btn btn-sm btn-outline-primary"
                       title="Download all documentation as PDF">
                        <i class="fas fa-download"></i> Download all
                    </a>
                </div>
            </div>
            <div class="card-body crm-docs-content">
                {!! $html !!}
            </div>
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
