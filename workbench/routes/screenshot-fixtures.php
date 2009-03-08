<?php

declare(strict_types=1);

use Capell\Navigation\Actions\SeedNavigationScreenshotFixtureAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->get('/screenshot-fixtures/navigation/{screen}', static function (string $screen): RedirectResponse {
    abort_unless(in_array($screen, ['page', 'site'], true), 404);
    $page = app(SeedNavigationScreenshotFixtureAction::class)->handle();
    $routeKey = $page->getRouteKey();
    throw_unless(is_int($routeKey) || is_string($routeKey), RuntimeException::class, 'The Navigation screenshot page route key must be scalar.');

    if ($screen === 'page') {
        return redirect('/admin/pages/' . $routeKey . '/edit');
    }

    $siteId = $page->site_id;
    throw_unless(is_int($siteId) || is_string($siteId), RuntimeException::class, 'The Navigation screenshot site route key must be scalar.');

    return redirect('/admin/sites/' . $siteId . '/edit');
});
