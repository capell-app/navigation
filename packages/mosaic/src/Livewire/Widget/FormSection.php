<?php

declare(strict_types=1);

namespace Capell\Mosaic\Livewire\Widget;

/**
 * Form Section Widget - Architectural Precision
 *
 * Customizable form with various field types
 * Inputs use ghost borders, monospace labels for technical IDs
 */
class FormSection extends AbstractWidget
{
    protected static string $defaultView = 'capell-mosaic::widgets.form-section';

    public function getFormTitle(): string
    {
        return $this->widgetData['title'] ?? 'Contact Form';
    }

    public function getFormDescription(): string
    {
        return $this->widgetData['description'] ?? '';
    }

    public function getFormFields(): array
    {
        return $this->widgetData['fields'] ?? [];
    }

    public function getSubmitButtonText(): string
    {
        return $this->widgetData['submit_text'] ?? 'Submit';
    }

    public function getSubmitAction(): string
    {
        return $this->widgetData['submit_action'] ?? '#';
    }

    public function getFieldCount(): int
    {
        return count($this->getFormFields());
    }

    protected function mountWidget(): void
    {
        // Initialize form data
    }
}
