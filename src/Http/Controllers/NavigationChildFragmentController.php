<?php

declare(strict_types=1);

namespace Capell\Navigation\Http\Controllers;

use Capell\Navigation\Actions\BuildNavigationChildFragmentAction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class NavigationChildFragmentController
{
    public function __invoke(Request $request): Response
    {
        $payload = $request->query('payload');

        abort_unless(is_string($payload) && $payload !== '', Response::HTTP_NOT_FOUND);

        $html = BuildNavigationChildFragmentAction::run($payload, strtolower($request->getHost()));

        abort_if($html === null, Response::HTTP_NOT_FOUND);

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=300, stale-while-revalidate=60')
            ->header('X-Robots-Tag', 'noindex');
    }
}
