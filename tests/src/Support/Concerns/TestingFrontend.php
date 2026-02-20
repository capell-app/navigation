<?php

declare(strict_types=1);

namespace Capell\Tests\Support\Concerns;

use Illuminate\Support\Facades\App;

trait TestingFrontend
{
    public function setUpTestingFrontend(): void
    {
        if (! App::environment('testing')) {
        }
    }
}
