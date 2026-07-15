<?php

declare(strict_types=1);

namespace Capell\Navigation\View\Components\Header;

use Capell\Navigation\Enums\HeaderNavigationBreakpoint;
use Capell\Navigation\Enums\NavigationHandle;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\View\Component;

final class MainNavigation extends Component
{
    public string $itemClass;

    public function __construct(
        ?string $itemClass = null,
        public HeaderNavigationBreakpoint $breakpoint = HeaderNavigationBreakpoint::Lg,
    ) {
        $this->itemClass = $itemClass ?? $this->breakpoint->defaultItemClasses();
    }

    public function render(): ViewContract
    {
        return view('capell-navigation::components.header.navigation', [
            'itemClass' => $this->itemClass,
            'navigationKey' => NavigationHandle::Main,
            'fallbackWithoutLanguage' => true,
            'breakpoint' => $this->breakpoint,
        ]);
    }
}
