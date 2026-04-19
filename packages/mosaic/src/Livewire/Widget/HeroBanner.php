<?php

declare(strict_types=1);

namespace Capell\Mosaic\Livewire\Widget;

/**
 * Hero Banner Widget - Architectural Precision
 *
 * Full-width hero section with headline, subtitle, CTA button
 * Implements gold/silver/obsidian design system with sharp edges
 */
class HeroBanner extends AbstractWidget
{
    protected static string $defaultView = 'capell-mosaic::widgets.hero-banner';

    public function getTitle(): string
    {
        return $this->widgetData['title'] ?? 'Hero Section';
    }

    public function getSubtitle(): string
    {
        return $this->widgetData['subtitle'] ?? '';
    }

    public function getCtaText(): string
    {
        return $this->widgetData['cta_text'] ?? 'Get Started';
    }

    public function getCtaUrl(): string
    {
        return $this->widgetData['cta_url'] ?? '#';
    }

    public function getBackgroundImage(): ?string
    {
        return $this->widgetData['background_image'] ?? null;
    }

    protected function mountWidget(): void
    {
        // Widget initialization logic
    }
}
