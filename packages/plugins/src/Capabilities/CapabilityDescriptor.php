<?php

declare(strict_types=1);

namespace Capell\Plugins\Capabilities;

use Capell\Plugins\Enums\Capability;
use Capell\Plugins\Enums\CapabilityWarningLevel;

final class CapabilityDescriptor
{
    public function __construct(
        public readonly Capability $capability,
        public readonly CapabilityWarningLevel $warningLevel,
        public readonly string $title,
        public readonly string $summary,
        public readonly ?string $parameter = null,
    ) {}

    public function toManifestString(): string
    {
        return $this->parameter === null
            ? $this->capability->value
            : "{$this->capability->value}:{$this->parameter}";
    }
}
