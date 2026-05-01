<?php

declare(strict_types=1);

namespace Capell\Events\Console\Commands;

use Capell\Admin\Actions\AssignPermissionsToRole;
use Capell\Events\Enums\ResourceEnum;
use Capell\Events\Support\EventsModelRegistrar;
use Filament\Facades\Filament;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $description = 'Install events package';

    protected $signature = 'capell:events-install';

    public function handle(): int
    {
        EventsModelRegistrar::register();

        Filament::getDefaultPanel()
            ->resources(array_map(fn (ResourceEnum $resourceEnum): string => $resourceEnum->value, ResourceEnum::cases()));

        AssignPermissionsToRole::run(resources: ResourceEnum::cases());

        $this->publishMigrations();
        $this->call('migrate');
        $this->callSilent('filament:assets');

        $this->newLine();
        $this->info('Capell Events installed successfully.');

        return self::SUCCESS;
    }

    private function publishMigrations(): bool
    {
        $migrations = [
            __DIR__ . '/../../../database/migrations/create_events_table.php',
            __DIR__ . '/../../../database/migrations/create_event_occurrences_table.php',
        ];

        $this->call('capell:publish-migrations', ['--items' => $migrations]);

        return true;
    }
}
