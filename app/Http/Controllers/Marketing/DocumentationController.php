<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\DocsController;

class DocumentationController extends DocsController
{
    /**
     * @return array{index: string, show: string, pdf: string, pdfPage: string}
     */
    protected function docsRoutes(): array
    {
        return [
            'index' => 'marketing.documentation',
            'show' => 'marketing.documentation.show',
            'pdf' => 'marketing.documentation.pdf',
            'pdfPage' => 'marketing.documentation.pdf.page',
        ];
    }

    protected function viewName(): string
    {
        return 'docs.public-show';
    }
}
