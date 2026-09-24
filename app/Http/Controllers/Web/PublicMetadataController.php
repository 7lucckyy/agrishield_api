<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class PublicMetadataController extends Controller
{
    public function robots(): Response
    {
        $content = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /api/',
            'Disallow: /login',
            'Disallow: /platform/',
            'Disallow: /organization/',
            '',
            'Sitemap: '.route('sitemap'),
            '',
        ]);

        return response($content, Response::HTTP_OK, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function sitemap(): Response
    {
        return response()->view('seo.sitemap', [
            'routes' => [
                ['name' => 'home', 'changeFrequency' => 'weekly', 'priority' => '1.0'],
                ['name' => 'solutions', 'changeFrequency' => 'monthly', 'priority' => '0.9'],
                ['name' => 'field-voice', 'changeFrequency' => 'monthly', 'priority' => '0.9'],
                ['name' => 'impact', 'changeFrequency' => 'monthly', 'priority' => '0.8'],
                ['name' => 'about', 'changeFrequency' => 'monthly', 'priority' => '0.7'],
                ['name' => 'partners', 'changeFrequency' => 'monthly', 'priority' => '0.6'],
                ['name' => 'contact', 'changeFrequency' => 'yearly', 'priority' => '0.6'],
            ],
        ])->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    public function security(): Response
    {
        $content = implode("\n", [
            'Contact: mailto:security@agrishield.ai',
            'Preferred-Languages: en',
            'Canonical: '.url('/.well-known/security.txt'),
            'Expires: '.now()->addYear()->utc()->toIso8601String(),
            '',
        ]);

        return response($content, Response::HTTP_OK, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
