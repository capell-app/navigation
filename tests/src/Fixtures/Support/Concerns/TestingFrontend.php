<?php

declare(strict_types=1);

namespace Capell\Tests\Fixtures\Support\Concerns;

use Illuminate\Support\Facades\App;

trait TestingFrontend
{
    public function setUpTestingFrontend(): void
    {
        if (! App::environment('testing')) {
            return;
        }

        // \Capell\Frontend\Helpers\Routes::routes();
    }
}
