<?php

declare(strict_types=1);

namespace Capell\Layout\Commands;

use Capell\Layout\Actions\InstallPackageAction;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inserts capell-layout';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'capell-layout:install';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        InstallPackageAction::run();

        return Command::SUCCESS;
    }
}
