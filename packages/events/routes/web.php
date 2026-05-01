<?php

declare(strict_types=1);

use Capell\Core\Models\Site;
use Capell\Events\Actions\BuildIcsFeedAction;
use Illuminate\Support\Facades\Route;

Route::get('/events/feed.ics', function () {
    /** @var Site $site */
    $site = Site::query()->default()->firstOrFail();

    return response(BuildIcsFeedAction::run($site), 200, [
        'Content-Type' => 'text/calendar; charset=UTF-8',
    ]);
})->name('capell-events.feed');
