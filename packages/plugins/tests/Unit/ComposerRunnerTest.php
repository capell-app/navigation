<?php

declare(strict_types=1);

namespace Capell\Plugins\Tests\Unit;

use Capell\Plugins\Services\ComposerResult;
use Capell\Plugins\Services\ComposerRunner;
use PHPUnit\Framework\TestCase;

class ComposerRunnerTest extends TestCase
{
    public function test_composer_result_successful_returns_true_for_exit_code_zero(): void
    {
        $result = new ComposerResult(exitCode: 0, stdout: 'Success', stderr: '');

        $this->assertTrue($result->successful());
    }

    public function test_composer_result_successful_returns_false_for_non_zero_exit_code(): void
    {
        $result = new ComposerResult(exitCode: 1, stdout: '', stderr: 'Error');

        $this->assertFalse($result->successful());
    }

    public function test_composer_runner_accepts_constructor_parameters(): void
    {
        $runner = new ComposerRunner(
            binary: '/usr/local/bin/composer',
            timeoutSeconds: 300,
            workingDirectory: '/home/user/project',
        );

        $this->assertInstanceOf(ComposerRunner::class, $runner);
    }

    public function test_composer_runner_defaults_to_composer_binary(): void
    {
        $runner = new ComposerRunner;

        $this->assertInstanceOf(ComposerRunner::class, $runner);
    }

    public function test_composer_runner_with_default_timeout(): void
    {
        $runner = new ComposerRunner(timeoutSeconds: 600);

        $this->assertInstanceOf(ComposerRunner::class, $runner);
    }
}
