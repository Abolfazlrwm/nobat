<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * Privacy policy and terms of service. The copy lives in lang/{locale}/legal.php
 * so the visitor's chosen locale (nobat_locale cookie) selects the language —
 * the same machine that translates booking flow strings translates the law.
 */
class LegalController extends Controller
{
    public function privacy(): Response
    {
        return $this->render('privacy');
    }

    public function terms(): Response
    {
        return $this->render('terms');
    }

    private function render(string $doc): Response
    {
        $copy = __("legal.{$doc}");
        $copy = is_array($copy) ? $copy : ['title' => $doc, 'updated' => '', 'sections' => []];

        return Inertia::render('public/Legal', [
            'doc' => $doc,
            'title' => $copy['title'],
            'updated' => $copy['updated'],
            'sections' => array_map(
                fn (array $section) => [
                    'heading' => $section['heading'] ?? '',
                    'body' => is_array($section['body'] ?? null) ? $section['body'] : [],
                ],
                is_array($copy['sections'] ?? null) ? $copy['sections'] : [],
            ),
        ]);
    }
}
