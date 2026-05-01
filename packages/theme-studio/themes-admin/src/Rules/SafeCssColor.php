<?php

declare(strict_types=1);

namespace Capell\Themes\Admin\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class SafeCssColor implements ValidationRule
{
    private const PATTERN = '/\A(?:#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})|var\(--[a-zA-Z0-9_-]+\)|--[a-zA-Z0-9_-]+|[a-zA-Z][a-zA-Z0-9_-]*)\z/';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match(self::PATTERN, $value) !== 1) {
            $fail(__('capell-themes-admin::settings.validation.safe_css_color'));
        }
    }
}
