<?php

declare(strict_types=1);

namespace Capell\Events\Console\Commands;

use Capell\Events\Actions\InstallPackageAction;
use Illuminate\Console\Command;

class SetupCommand extends Command
{
    protected $description = 'Setup events package';

    protected $signature = 'capell:events-setup
        {--user= : Ignored - accepted for compatibility with capell:install}
        {--sites= : Ignored - accepted for compatibility with capell:install}
        {--languages= : Ignored - accepted for compatibility with capell:install}
        {--url= : Ignored - accepted for compatibility with capell:install}
    ';

    public function handle(): int
    {
        InstallPackageAction::run();

        $this->newLine();
        $this->info('Capell Events setup successfully.');

        return self::SUCCESS;
    }
}
