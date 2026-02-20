<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Hero\Actions\AddHeroToLayoutAction;
use Illuminate\Console\Command;

it('runs hero install command successfully', function (): void {
    $layout = Layout::factory()->create();

    $mock = AddHeroToLayoutAction::mock();
    $mock->shouldReceive('handle')->once();
    app()->instance(AddHeroToLayoutAction::class, $mock);

    // TODO check if this is the right way to assert that the action was called
    // AddHeroToLayoutAction::shouldRun()->once()->withArgs($layout);

    $this->artisan('capell-hero:install')
        ->expectsOutput('Hero package installed successfully.')
        ->assertExitCode(Command::SUCCESS);
});
