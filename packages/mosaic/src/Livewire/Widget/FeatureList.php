<?php

declare(strict_types=1);

namespace Capell\Mosaic\Livewire\Widget;

/**
 * Feature List Widget - Architectural Precision
 *
 * Vertical list of features with icons, titles, and descriptions
 * No dividers - uses tonal layering and ghost borders
 */
class FeatureList extends AbstractWidget
{
    protected static string $defaultView = 'capell-mosaic::widgets.feature-list';

    public function getFeatures(): array
    {
        return $this->widgetData['features'] ?? [];
    }

    public function getLayout(): string
    {
        return $this->widgetData['layout'] ?? 'vertical'; // vertical | horizontal
    }

    public function isHorizontal(): bool
    {
        return $this->getLayout() === 'horizontal';
    }

    public function getFeatureCount(): int
    {
        return count($this->getFeatures());
    }

    protected function mountWidget(): void
    {
        // Initialize feature data
    }
}
