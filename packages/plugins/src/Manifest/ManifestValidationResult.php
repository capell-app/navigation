<?php

declare(strict_types=1);

namespace Capell\Plugins\Manifest;

final class ManifestValidationResult
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(
        public readonly bool $isValid,
        public readonly array $errors = [],
    ) {}
}
