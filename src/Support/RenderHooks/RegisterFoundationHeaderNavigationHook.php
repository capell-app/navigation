<?php

declare(strict_types=1);

namespace Capell\Navigation\Support\RenderHooks;

use Capell\Frontend\Contracts\RenderHookExtensionInterface;
use Capell\Frontend\Data\RenderHookContext;
use Livewire\Blaze\Blaze;

final class RegisterFoundationHeaderNavigationHook implements RenderHookExtensionInterface
{
    public const string DefaultScenario = 'frontend-default-primary-navigation';

    public const string FoundationScenario = 'theme-foundation-primary-navigation';

    public const string Target = 'capell::header.index';

    public function render(RenderHookContext $context): string
    {
        $view = view('capell-navigation::components.header.main-navigation', [
            'itemClass' => is_array($context->item) && is_string($context->item['menuItemClass'] ?? null)
                ? $context->item['menuItemClass']
                : null,
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
