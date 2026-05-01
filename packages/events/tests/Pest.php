<?php

declare(strict_types=1);

use Capell\Events\Tests\EventsTestCase;

pest()->extend(EventsTestCase::class)->in('Feature', 'Integration', 'Unit');
