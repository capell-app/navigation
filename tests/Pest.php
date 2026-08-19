<?php

declare(strict_types=1);

use Capell\Navigation\Tests\NavigationTestCase;

pest()->extend(NavigationTestCase::class)->group('navigation')->in('.');
