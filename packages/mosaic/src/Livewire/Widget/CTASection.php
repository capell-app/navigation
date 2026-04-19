<?php

declare(strict_types=1);

namespace Capell\Mosaic\Livewire\Widget;

/**
 * CTA Section Widget - Architectural Precision
 *
 * Call-to-action section with headline, description, and button
 * Gold gradient buttons with sharp edges (zero-radius mandate)
 */
class CTASection extends AbstractWidget
{
    protected static string $defaultView = 'capell-mosaic::widgets.cta-section';

    public function getHeadline(): string
    {
        return $this->widgetData['headline'] ?? 'Ready to get started?';
    }

    public function getDescription(): string
    {
        return $this->widgetData['description'] ?? '';
    }

    public function getPrimaryButtonText(): string
    {
        return $this->widgetData['primary_button_text'] ?? 'Get Started';
    }

    public function getPrimaryButtonUrl(): string
    {
        return $this->widgetData['primary_button_url'] ?? '#';
    }

    public function getSecondaryButtonText(): ?string
    {
        return $this->widgetData['secondary_button_text'] ?? null;
    }

    public function getSecondaryButtonUrl(): ?string
    {
        return $this->widgetData['secondary_button_url'] ?? null;
    }

    public function hasSecondaryButton(): bool
    {
        return ! empty($this->getSecondaryButtonText());
    }

    protected function mountWidget(): void
    {
        // Initialize CTA data
    }
}
