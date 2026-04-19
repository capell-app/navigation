<?php

declare(strict_types=1);

namespace Capell\Mosaic\Livewire\Widget;

/**
 * Image Gallery Widget - Architectural Precision
 *
 * Grid or carousel gallery with blueprint-style grid background
 * Sharp edges, coordinate markers, technical annotations
 */
class ImageGallery extends AbstractWidget
{
    public bool $showLightbox = true;

    protected static string $defaultView = 'capell-mosaic::widgets.image-gallery';

    public function getImages(): array
    {
        return $this->widgetData['images'] ?? [];
    }

    public function getLayout(): string
    {
        return $this->widgetData['layout'] ?? 'grid'; // grid | carousel
    }

    public function getColumns(): int
    {
        return $this->widgetData['columns'] ?? 3;
    }

    public function isCarousel(): bool
    {
        return $this->getLayout() === 'carousel';
    }

    public function getImageCount(): int
    {
        return count($this->getImages());
    }

    public function getGridClass(): string
    {
        $cols = $this->getColumns();

        return "grid grid-cols-1 md:grid-cols-{$cols} gap-mosaic-md";
    }

    protected function mountWidget(): void
    {
        $this->showLightbox = $this->widgetData['lightbox'] ?? true;
    }
}
