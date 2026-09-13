<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            ['loc' => route('marketing.home'), 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['loc' => route('marketing.features'), 'changefreq' => 'weekly', 'priority' => '0.9'],
            ['loc' => route('marketing.pricing'), 'changefreq' => 'weekly', 'priority' => '0.9'],
            ['loc' => route('marketing.documentation'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => route('marketing.about'), 'changefreq' => 'monthly', 'priority' => '0.7'],
            ['loc' => route('marketing.watch-demo'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => route('marketing.contact'), 'changefreq' => 'monthly', 'priority' => '0.8'],
        ];

        $xml = view('marketing.sitemap', ['urls' => $urls])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
