<?php

declare(strict_types=1);

namespace Capell\Navigation\Support\RenderHooks;

use Capell\Frontend\Contracts\RenderHookExtensionInterface;
use Capell\Frontend\Data\RenderHookContext;
use Capell\Navigation\Enums\HeaderNavigationBreakpoint;
use Livewire\Blaze\Blaze;

final class RegisterFoundationHeaderNavigationHook implements RenderHookExtensionInterface
{
    public const string DefaultScenario = 'frontend-default-primary-navigation';

    public const string FoundationScenario = 'theme-foundation-primary-navigation';

    public const string Target = 'capell::header.index';

    public function __construct(
        private readonly HeaderNavigationBreakpoint $breakpoint = HeaderNavigationBreakpoint::Lg,
    ) {}

    public function render(RenderHookContext $context): string
    {
        $view = view('capell-navigation::components.header.main-navigation', [
            'itemClass' => is_array($context->item) && is_string($context->item['menuItemClass'] ?? null)
                ? $context->item['menuItemClass']
                : null,
            'breakpoint' => $this->breakpoint,
        ]);
        $wasBlazeEnabled = Blaze::isEnabled();
        Blaze::disable();

        try {
            return $view->render();
        } finally {
            if ($wasBlazeEnabled) {
                Blaze::enable();
            }
        }
    }
}
