<?php

declare(strict_types=1);

namespace Capell\Navigation\View\Components\Header;

use Capell\Navigation\Enums\NavigationHandle;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\View\Component;

final class MainNavigation extends Component
{
    private const string DefaultItemClass = 'nav-item font-heading group flex w-full cursor-pointer items-center justify-between gap-x-2 px-6 py-3 text-sm font-medium hover:bg-gray-50 focus-visible:bg-gray-50 lg:!bg-transparent lg:px-4 lg:py-1 dark:hover:bg-gray-800 dark:focus-visible:bg-gray-800';

    public string $itemClass;

    public function __construct(?string $itemClass = null)
    {
        $this->itemClass = $itemClass ?? self::DefaultItemClass;
    }

    public function render(): ViewContract
    {
        return view('capell-navigation::components.header.navigation', [
            'itemClass' => $this->itemClass,
            'navigationKey' => NavigationHandle::Main,
            'fallbackWithoutLanguage' => true,
        ]);
    }
}
