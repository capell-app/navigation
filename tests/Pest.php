<?php

declare(strict_types=1);

$testsRoot = __DIR__ . DIRECTORY_SEPARATOR . 'src';

// Safety: ensure root exists before iterating
if (is_dir($testsRoot)) {
    foreach (new DirectoryIterator($testsRoot) as $info) {
        if (! $info->isDir() || $info->isDot()) {
            continue;
        }

        $name = $info->getBasename();

        // Skip non test suite directories
        if (in_array($name, ['Fixtures'], true)) {
            continue;
        }

        $testCaseClass = "Capell\\Tests\\{$name}\\{$name}TestCase"; // Convention: Capell\Tests\{Dir}\{Dir}TestCase

        if (class_exists($testCaseClass)) {
            pest()->extends($testCaseClass)
                ->in($testsRoot . DIRECTORY_SEPARATOR . $name);
        }
    }
}
