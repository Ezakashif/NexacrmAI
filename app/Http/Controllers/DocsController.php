<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Symfony\Component\Finder\Finder;

class DocsController extends Controller
{
    public function index(): View
    {
        return $this->show('README');
    }

    public function show(string $path = 'README'): View
    {
        $relative = $this->normalizeDocPath($path);
        $absolute = $this->resolveDocFile($relative);
        $markdown = File::get($absolute);
        $html = $this->renderMarkdown($markdown, $relative);

        return view($this->viewName(), [
            'title' => $this->titleFromPath($relative),
            'path' => $relative,
            'html' => $html,
            'nav' => $this->navigation(),
            'docsRoutes' => $this->docsRoutes(),
        ]);
    }

    public function download(string $path): Response
    {
        $relative = $this->normalizeDocPath($path);
        $absolute = $this->resolveDocFile($relative);
        $title = $this->titleFromPath($relative);
        $html = $this->renderMarkdownForPdf(File::get($absolute), $relative);

        $pdf = Pdf::loadView('docs.pdf', [
            'documentTitle' => $title,
            'scopeLabel' => 'Single page',
            'generatedAt' => now(),
            'toc' => [],
            'sections' => [[
                'title' => $title,
                'path' => $relative,
                'html' => $html,
            ]],
        ])->setPaper('a4', 'portrait');

        $slug = Str::slug(str_replace('/', '-', $relative)) ?: 'documentation';

        return $pdf->download('crm-docs-'.$slug.'-'.now()->format('Y-m-d').'.pdf');
    }

    public function downloadAll(): Response
    {
        $nav = $this->navigation();
        $sections = [];

        foreach ($nav as $group) {
            foreach ($group['items'] as $item) {
                $relative = $item['path'];
                $absolute = $this->resolveDocFile($relative);
                $sections[] = [
                    'title' => ($group['section'] === 'General' ? '' : $group['section'].' · ').$item['title'],
                    'path' => $relative,
                    'html' => $this->renderMarkdownForPdf(File::get($absolute), $relative),
                ];
            }
        }

        $pdf = Pdf::loadView('docs.pdf', [
            'documentTitle' => 'CRM Documentation',
            'scopeLabel' => 'Full documentation',
            'generatedAt' => now(),
            'toc' => $nav,
            'sections' => $sections,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('crm-documentation-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * @return array{index: string, show: string, pdf: string, pdfPage: string}
     */
    protected function docsRoutes(): array
    {
        return [
            'index' => 'docs.index',
            'show' => 'docs.show',
            'pdf' => 'docs.pdf',
            'pdfPage' => 'docs.pdf.page',
        ];
    }

    protected function viewName(): string
    {
        return auth()->user()?->isSuperAdmin() === true
            ? 'docs.sa-show'
            : 'docs.show';
    }

    protected function normalizeDocPath(string $path): string
    {
        $path = str_replace('\\', '/', rawurldecode($path));
        $path = trim($path, '/');

        if ($path === '' || $path === 'index') {
            $path = 'README';
        }

        if (str_ends_with(Str::lower($path), '.md')) {
            $path = substr($path, 0, -3);
        }

        if (
            $path === ''
            || str_contains($path, '..')
            || str_contains($path, "\0")
            || preg_match('/[^A-Za-z0-9_\\-\\/]/', $path)
        ) {
            abort(404);
        }

        return $path;
    }

    protected function resolveDocFile(string $relativeWithoutExtension): string
    {
        $base = realpath(base_path('docs'));

        if ($base === false) {
            abort(404);
        }

        $candidate = $base.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativeWithoutExtension).'.md';
        $absolute = realpath($candidate);

        if ($absolute === false || ! is_file($absolute) || ! str_starts_with($absolute, $base)) {
            abort(404);
        }

        return $absolute;
    }

    protected function renderMarkdown(string $markdown, string $currentRelative): string
    {
        $converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $html = $converter->convert($markdown)->getContent();

        return $this->rewriteDocLinks($html, $currentRelative);
    }

    /**
     * Render Markdown for DomPDF: omit Mermaid (JS-only) and keep doc paths readable offline.
     */
    protected function renderMarkdownForPdf(string $markdown, string $currentRelative): string
    {
        $markdown = (string) preg_replace(
            '/```mermaid\s*\n.*?```/is',
            "\n\n*[Diagram omitted in PDF — open this page in the online docs to view it.]*\n\n",
            $markdown
        );

        $converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $html = $converter->convert($markdown)->getContent();

        return (string) preg_replace_callback(
            '/href="([^"]+\.md)(#[^"]*)?"/i',
            function (array $matches) use ($currentRelative) {
                $target = str_replace('\\', '/', $matches[1]);

                if (preg_match('#^(https?:)?//#i', $target) || str_starts_with($target, 'mailto:')) {
                    return $matches[0];
                }

                $resolved = $this->resolveRelativeMarkdownPath($currentRelative, $target);

                return 'href="#doc-'.e(Str::slug($resolved)).'"';
            },
            $html
        );
    }

    protected function rewriteDocLinks(string $html, string $currentRelative): string
    {
        $routes = $this->docsRoutes();

        return (string) preg_replace_callback(
            '/href="([^"]+\.md)(#[^"]*)?"/i',
            function (array $matches) use ($currentRelative, $routes) {
                $target = str_replace('\\', '/', $matches[1]);
                $hash = $matches[2] ?? '';

                if (preg_match('#^(https?:)?//#i', $target) || str_starts_with($target, 'mailto:')) {
                    return $matches[0];
                }

                $resolved = $this->resolveRelativeMarkdownPath($currentRelative, $target);
                $url = ($resolved === '' || $resolved === 'README')
                    ? route($routes['index'])
                    : route($routes['show'], ['path' => $resolved]);

                return 'href="'.e($url.$hash).'"';
            },
            $html
        );
    }

    protected function resolveRelativeMarkdownPath(string $currentRelative, string $href): string
    {
        $href = Str::before($href, '.md');
        $href = trim($href, '/');

        if (str_starts_with($href, '/')) {
            return trim($href, '/');
        }

        $parts = (! str_contains($currentRelative, '/'))
            ? []
            : explode('/', Str::beforeLast($currentRelative, '/'));

        foreach (explode('/', $href) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($parts);

                continue;
            }

            $parts[] = $segment;
        }

        return implode('/', $parts);
    }

    /**
     * Build the sidebar navigation grouped by top-level section so each
     * folder (e.g. "User Manual", "Channels") is clearly labelled instead
     * of showing a flat list of repeated "Overview" entries.
     *
     * @return array<int, array{section: string, items: array<int, array{title: string, path: string, url: string}}}>
     */
    protected function navigation(): array
    {
        $base = realpath(base_path('docs'));

        if ($base === false) {
            return [];
        }

        $finder = Finder::create()
            ->files()
            ->in($base)
            ->name('*.md')
            ->sortByName();

        $groups = [];
        $routes = $this->docsRoutes();

        foreach ($finder as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $path = Str::beforeLast($relative, '.md');

            $section = str_contains($path, '/')
                ? Str::headline(str_replace(['-', '_'], ' ', Str::before($path, '/')))
                : 'General';

            $groups[$section][] = [
                'title' => $this->navTitle($path),
                'path' => $path,
                'url' => $path === 'README'
                    ? route($routes['index'])
                    : route($routes['show'], ['path' => $path]),
            ];
        }

        // Keep "General" (top-level pages) first, then the rest alphabetically.
        uksort($groups, function (string $a, string $b): int {
            if ($a === 'General') {
                return -1;
            }

            if ($b === 'General') {
                return 1;
            }

            return strcmp($a, $b);
        });

        $navigation = [];

        foreach ($groups as $section => $items) {
            $navigation[] = [
                'section' => $section,
                'items' => $items,
            ];
        }

        return $navigation;
    }

    /**
     * Title for a nav item within its section (the section header already
     * conveys the folder, so we only need the page name).
     */
    protected function navTitle(string $path): string
    {
        if ($path === 'README') {
            return 'Documentation home';
        }

        $name = Str::afterLast($path, '/');

        if (Str::lower($name) === 'readme') {
            return 'Overview';
        }

        return Str::headline(str_replace(['-', '_'], ' ', $name));
    }

    protected function titleFromPath(string $path): string
    {
        if ($path === 'README') {
            return 'Documentation home';
        }

        $name = Str::afterLast($path, '/');
        $section = str_contains($path, '/')
            ? Str::headline(str_replace(['-', '_'], ' ', Str::before($path, '/')))
            : null;

        if (Str::lower($name) === 'readme' || Str::lower($name) === 'overview') {
            return $section ?? Str::headline(str_replace(['-', '_'], ' ', $name));
        }

        $page = Str::headline(str_replace(['-', '_'], ' ', $name));

        return $section ? $section.' · '.$page : $page;
    }
}
