<?php

declare(strict_types=1);

use Capell\Core\Actions\GetComponentClassAction;
use Capell\Mosaic\View\Components\Widget\Hero;
use Capell\Tests\Support\Concerns\TestingFrontend;

uses(TestingFrontend::class);

it('returns component class for blade component', function (): void {
    $component = 'capell-hero::widget.hero';

    $componentClass = GetComponentClassAction::run($component);

    expect($componentClass)
        ->toBe(Hero::class);
});
