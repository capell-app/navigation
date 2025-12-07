<?php

declare(strict_types=1);

use Capell\Tests\Address\AddressTestCase;
use Capell\Tests\Blog\BlogTestCase;
use Capell\Tests\Hero\HeroTestCase;
use Capell\Tests\Layout\LayoutTestCase;
use Capell\Tests\Packages\PackagesTestCase;

$testsRoot = __DIR__ . DIRECTORY_SEPARATOR . 'src';

pest()->extends(PackagesTestCase::class)
    ->in($testsRoot . DIRECTORY_SEPARATOR . 'Packages');

pest()->extends(AddressTestCase::class)
    ->in($testsRoot . DIRECTORY_SEPARATOR . 'Address');

pest()->extends(BlogTestCase::class)
    ->in($testsRoot . DIRECTORY_SEPARATOR . 'Blog');

pest()->extends(HeroTestCase::class)
    ->in($testsRoot . DIRECTORY_SEPARATOR . 'Hero');

pest()->extends(LayoutTestCase::class)
    ->in($testsRoot . DIRECTORY_SEPARATOR . 'Layout');
