<?php

declare(strict_types=1);

use Capell\Core\Console\Commands\PublishMigrationsCommand;
use Capell\Core\Support\Dataset\DatasetPublisher;
use Capell\Core\Support\Migration\MigrationFileManagerInterface;
use Capell\Layout\Actions\InstallPackageAction;
use Capell\Tests\Fixtures\FakeMigrationFileManager;
use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Migrations\Migrator;

beforeEach(function (): void {
    $this->fakeFileManager = new FakeMigrationFileManager([
        'fileExists' => [],
        'isDir' => [],
    ]);

    $fakeDatasetPublisher = Mockery::mock(DatasetPublisher::class);
    $this->instance(
        PublishMigrationsCommand::class,
        Mockery::mock(new PublishMigrationsCommand($fakeDatasetPublisher, $this->fakeFileManager))
            ->makePartial()
            ->shouldReceive('run')->once()->andReturn(0)->getMock(),
    );

    $fakeMigrator = Mockery::mock(Migrator::class);
    $fakeDispatcher = Mockery::mock(Dispatcher::class);
    $this->instance(
        MigrateCommand::class,
        Mockery::mock(new MigrateCommand($fakeMigrator, $fakeDispatcher))
            ->makePartial()
            ->shouldReceive('run')->once()->andReturn(0)->getMock(),
    );

    if (class_exists('Filament\\Commands\\AssetsCommand')) {
        $this->instance(
            'Filament\\Commands\\AssetsCommand',
            Mockery::mock('Filament\\Commands\\AssetsCommand', [])->makePartial()
                ->shouldReceive('run')->once()->andReturn(0)->getMock(),
        );
    }

    app()->instance(MigrationFileManagerInterface::class, $this->fakeFileManager);
});

afterEach(function (): void {
    Mockery::close();
});

it('runs layout install command successfully without publishing files', function (): void {
    $mock = InstallPackageAction::mock();
    $mock->shouldReceive('handle')->once();
    app()->instance(InstallPackageAction::class, $mock);

    $this->artisan('capell-layout:install')
        ->expectsOutput('Installing Capell Layout...')
        ->doesntExpectOutput('Publishing migrations')
        ->doesntExpectOutput('Migrating')
        ->doesntExpectOutput('Building assets')
        ->expectsOutput('Capell Layout installation complete.')
        ->assertExitCode(Command::SUCCESS);

    expect($this->fakeFileManager->calls)
        ->not()->toContain(fn (array $call): bool => $call[0] === 'copy')
        ->toBeArray();

    expect(collect($this->fakeFileManager->calls)->contains(
        fn (array $call): bool => $call[0] === 'isDir',
    ))->toBeTrue();
});
