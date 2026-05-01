<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Forms;

class HoneypotField
{
    public function __construct(private readonly string $fieldName = 'hp_website') {}

    public function render(): string
    {
        $name = htmlspecialchars($this->fieldName, ENT_QUOTES);

        return '<div aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;">'
            . '<label for="' . $name . '">Leave this field empty</label>'
            . '<input type="text" id="' . $name . '" name="' . $name . '" tabindex="-1" autocomplete="off" value="" />'
            . '</div>';
    }

    public function fieldName(): string
    {
        return $this->fieldName;
    }

    public function validate(array $formData): bool
    {
        if (! array_key_exists($this->fieldName, $formData)) {
            return true;
        }

        return $formData[$this->fieldName] === '';
    }
}
