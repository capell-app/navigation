<?php

declare(strict_types=1);

use Capell\Tests\Blog\BlogTestCase;
use Capell\Tests\Layout\LayoutTestCase;

pest()->extends(BlogTestCase::class)
    ->in(__DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Blog');

pest()->extends(LayoutTestCase::class)
    ->in(__DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Layout');
