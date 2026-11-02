<?php

declare(strict_types=1);

namespace Capell\Navigation\Support;

use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Compilers\ComponentTagCompiler;
use InvalidArgumentException;

/**
 * Navigation renders some components that another package provides, such as Theme Foundation's
 * dropdown. This resolves a component exactly as <x-dynamic-component> will, so a view can fall
 * back to its own markup instead of failing when the providing package is absent.
 */
final readonly class OptionalBladeComponent
{
    public function __construct(private BladeCompiler $blade) {}

    public function resolves(string $component): bool
    {
        $compiler = new ComponentTagCompiler(
            $this->blade->getClassComponentAliases(),
            $this->blade->getClassComponentNamespaces(),
            $this->blade,
        );

        try {
            $compiler->componentClass($component);
        } catch (InvalidArgumentException) {
            return false;
        }

        return true;
    }
}
