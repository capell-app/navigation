<?php

declare(strict_types=1);

namespace Capell\Navigation\Support\Registry;

use Capell\Navigation\Enums\NavigationHandle;

final class NavigationHandleRegistry
{
    /** @var array<string, string> */
    private static array $handles = [];

    private static bool $booted = false;

    public static function register(string $handle, string $label): void
    {
        $normalizedHandle = trim($handle);
        $normalizedLabel = trim($label);

        if ($normalizedHandle === '' || $normalizedLabel === '') {
            return;
        }

        self::bootDefaults();

        self::$handles[$normalizedHandle] = $normalizedLabel;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        self::bootDefaults();

        return self::$handles;
    }

    public static function label(string|NavigationHandle $handle): string
    {
        $value = $handle instanceof NavigationHandle ? $handle->value : $handle;
        self::bootDefaults();

        return self::$handles[$value] ?? $value;
    }

    public static function flush(): void
    {
        self::$handles = [];
        self::$booted = false;
    }

    private static function bootDefaults(): void
    {
        if (self::$booted) {
            return;
        }

        foreach (NavigationHandle::cases() as $handle) {
            self::$handles[$handle->value] = $handle->getLabel();
        }

        self::$booted = true;
    }
}
