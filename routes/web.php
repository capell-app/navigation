<?php

declare(strict_types=1);

use Capell\Navigation\Http\Controllers\NavigationChildFragmentController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->prefix('_capell/navigation')
    ->name('capell-navigation.')
    ->group(function (): void {
        Route::get('children', NavigationChildFragmentController::class)
            ->middleware('throttle:capell-navigation-children')
            ->name('children');
    });
