<?php

declare(strict_types=1);

use Capell\Core\Actions\LoadSiteDomainFromUrlAction;
use Capell\Core\Models\SiteDomain;
use Capell\Events\Actions\BuildIcsFeedAction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

Route::get('/events/feed.ics', function (Request $request): Response {
    $resolved = LoadSiteDomainFromUrlAction::run($request->fullUrl());
    $siteDomain = $resolved[0] ?? null;

    abort_if(! $siteDomain instanceof SiteDomain, 404);

    $siteDomain->loadMissing(['language', 'site']);

    return response(BuildIcsFeedAction::run($siteDomain->site, $siteDomain->language), 200, [
        'Content-Type' => 'text/calendar; charset=UTF-8',
    ]);
})->middleware(['web'])->name('capell-events.feed');
