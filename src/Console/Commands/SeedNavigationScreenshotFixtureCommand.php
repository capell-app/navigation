<?php

declare(strict_types=1);

namespace Capell\Navigation\Console\Commands;

use Capell\Navigation\Actions\SeedNavigationScreenshotFixtureAction;
use Illuminate\Console\Command;

final class SeedNavigationScreenshotFixtureCommand extends Command
{
    protected $signature = 'capell:navigation:screenshot-fixture {--force}';

    protected $description = 'Prepare populated screenshot states in the disposable application.';

    public function handle(): int
    {
        if (! $this->option('force')) {
            $this->error('Refusing to seed screenshot fixtures without --force.');

            return self::FAILURE;
        }

        app(SeedNavigationScreenshotFixtureAction::class)->handle();

        return self::SUCCESS;
    }
}
