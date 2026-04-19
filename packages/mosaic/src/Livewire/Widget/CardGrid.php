<?php

declare(strict_types=1);

namespace Capell\Mosaic\Livewire\Widget;

/**
 * Card Grid Widget - Architectural Precision
 *
 * Responsive grid of cards with titles, descriptions, and optional images
 * Uses architectural-style borders (1px ghost borders) and tonal layering
 */
class CardGrid extends AbstractWidget
{
    protected static string $defaultView = 'capell-mosaic::widgets.card-grid';

    public function getCards(): array
    {
        return $this->widgetData['cards'] ?? [];
    }

    public function getColumns(): int
    {
        return $this->widgetData['columns'] ?? 3;
    }

    public function getCardCount(): int
    {
        return count($this->getCards());
    }

    public function getGridClass(): string
    {
        $cols = $this->getColumns();

        return "grid grid-cols-1 md:grid-cols-{$cols} gap-mosaic-lg";
    }

    protected function mountWidget(): void
    {
        // Initialize card data
    }
}
