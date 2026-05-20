<?php

declare(strict_types=1);

namespace Capell\Navigation\Support\RenderHooks;

use Capell\Frontend\Data\RenderHookContext;
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Livewire\Blaze\Blaze;

final class RegisterFoundationHeaderNavigationHook
{
    private const string DefaultScenario = 'frontend-default-primary-navigation';

    private const string FoundationScenario = 'foundation-theme-primary-navigation';

    private const string Target = 'capell::header.index';

    public function __construct(private readonly RenderHookRegistry $registry) {}

    public function register(): void
    {
        $this->registerHeaderHook(self::DefaultScenario);
        $this->registerHeaderHook(self::FoundationScenario);
    }

    private function registerHeaderHook(string $scenario): void
    {
        $this->registry->register(
            RenderHookLocation::HeaderAfter,
            static function (RenderHookContext $context): string {
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
            },
            scenario: $scenario,
            target: self::Target,
        );
    }
}
