<?php

declare(strict_types=1);

use Capell\Core\Console\Commands\PublishMigrationsCommand;
use Capell\Core\Models\Theme;
use Capell\Core\Support\Dataset\DatasetPublisher;
use Capell\Core\Support\Migration\MigrationFileManagerInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->fakeFileManager = new class implements MigrationFileManagerInterface
    {
        public array $calls = [];

        public function fileExists(string $path): bool
        {
            $this->calls[] = ['fileExists', $path];

            return false;
        }

        public function isDir(string $path): bool
        {
            $this->calls[] = ['isDir', $path];

            return true;
        }

        public function makeDir(string $path): void
        {
            $this->calls[] = ['makeDir', $path];
        }

        public function copy(string $from, string $to): void
        {
            $this->calls[] = ['copy', $from, $to];
        }
    };
    $this->fakeDatasetPublisher = \Mockery::mock(DatasetPublisher::class);

    $this->instance(
        PublishMigrationsCommand::class,
        \Mockery::mock(new PublishMigrationsCommand($this->fakeDatasetPublisher, $this->fakeFileManager))
            ->makePartial()
            ->shouldReceive('run')->once()->andReturn(0)->getMock(),
    );

    $fakeMigrator = \Mockery::mock(Migrator::class);
    $fakeDispatcher = \Mockery::mock(Dispatcher::class);
    $this->instance(
        MigrateCommand::class,
        \Mockery::mock(new MigrateCommand($fakeMigrator, $fakeDispatcher))
            ->makePartial()
            ->shouldReceive('run')->once()->andReturn(0)->getMock(),
    );
    // If Filament AssetsCommand is not available, skip this mock
    if (class_exists('Filament\\Commands\\AssetsCommand')) {
        $this->instance(
            'Filament\Commands\AssetsCommand',
            \Mockery::mock('Filament\Commands\AssetsCommand', [])->makePartial()
                ->shouldReceive('run')->once()->andReturn(0)->getMock(),
        );
    }
    app()->instance(MigrationFileManagerInterface::class, $this->fakeFileManager);
});

afterEach(function (): void {
    \Mockery::close();
});

it('runs install command and does not publish files for capell:publish-migrations', function (): void {
    $theme = Theme::factory()->create();

    $this->artisan('capell:address-install')
        ->expectsOutput('Installing Capell Address...')
        ->doesntExpectOutput('Publishing migrations')
        ->doesntExpectOutput('Migrating')
        ->doesntExpectOutput('Building assets')
        ->expectsOutput('Capell Address installation complete.')
        ->assertExitCode(0);

    // Assert no migration files are published
    expect($this->fakeFileManager->calls)
        ->not()->toContain(fn (array $call): bool => $call[0] === 'copy')
        ->toBeArray();

    // Assert migrations directory was checked
    expect(collect($this->fakeFileManager->calls)->contains(
        fn (array $call) => $call[0] === 'isDir' && str_contains($call[1], 'database/migrations'),
    ))->toBeTrue();

    // Assert fileExists was not called (no migration file existence check)
    expect(collect($this->fakeFileManager->calls)->contains(
        fn (array $call) => $call[0] === 'fileExists',
    ))->toBeFalse();

    // Assert makeDir was not called (no directory creation)
    expect(collect($this->fakeFileManager->calls)->contains(
        fn (array $call) => $call[0] === 'makeDir',
    ))->toBeFalse();

    // Assert theme exists and has expected attributes
    $themeRow = DB::table('themes')->where('id', $theme->id)->first();
    expect($themeRow)->not()->toBeNull();
    // Optionally, check for expected attributes if your factory sets them
    // expect($themeRow->name)->toBe('Expected Name');
});
