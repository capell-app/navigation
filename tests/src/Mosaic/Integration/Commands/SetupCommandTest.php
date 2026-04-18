<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Mosaic\Actions\Hero\AddHeroWidgetToLayoutAction;
use Illuminate\Console\Command;

use function Pest\Laravel\artisan;

it('runs hero install command successfully', function (): void {
    AddHeroWidgetToLayoutAction::shouldRun()->once();

    Layout::factory()->default()->create();

    artisan('capell:hero-setup')
        ->expectsOutput('Capell Hero setup successfully.')
        ->assertExitCode(Command::SUCCESS);
});
