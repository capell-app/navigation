<?php

declare(strict_types=1);

use Capell\Core\Support\Migration\MigrationFileManagerInterface;
use Illuminate\Console\Command;

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

        public function copy(string $from, $to): void
        {
            $this->calls[] = ['copy', $from, $to];
        }
    };
    app()->instance(MigrationFileManagerInterface::class, $this->fakeFileManager);
});

afterEach(function (): void {
    \Mockery::close();
});

it('runs assistant install command successfully', function (): void {
    $this->artisan('capell:assistant-install')
        ->expectsOutput('Assistant package installed successfully.')
        ->assertExitCode(Command::SUCCESS);

    expect(collect($this->fakeFileManager->calls)->contains(
        fn (array $call) => $call[0] === 'isDir',
    ))->toBeTrue();

    expect(collect($this->fakeFileManager->calls)->contains(
        fn (array $call) => $call[0] === 'copy',
    ))->toBeFalse();
});
