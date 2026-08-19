<?php

declare(strict_types=1);

namespace Capell\Navigation\Tests\Fixtures\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class Header extends Component
{
    public function render(): View
    {
        return view('capell-navigation-test::header');
    }
}
