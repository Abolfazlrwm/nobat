<?php

namespace App\Http\Controllers\PublicApi;

use App\Http\Controllers\Controller;
use App\Support\ApiSpec;
use Inertia\Inertia;
use Inertia\Response;

class DocsController extends Controller
{
    /**
     * The human reference page — rendered from the very same spec the
     * JSON endpoint serves, so prose can never drift from the contract.
     */
    public function page(): Response
    {
        return Inertia::render('public/ApiDocs', [
            'spec' => ApiSpec::toArray(),
        ]);
    }

    /**
     * The machine-readable contract itself, at a stable URL any client
     * code generator can consume.
     */
    public function spec(): \Illuminate\Http\JsonResponse
    {
        return response()
            ->json(ApiSpec::toArray())
            ->header('Content-Disposition', 'inline; filename="openapi.json"');
    }
}
