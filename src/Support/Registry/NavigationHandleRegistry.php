<?php

declare(strict_types=1);

namespace Capell\Navigation\Support\Registry;

use Capell\Frontend\Support\Application\ApplicationAreaRegistry;
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

        return self::$handles + self::applicationAreaHandles();
    }

    public static function label(string|NavigationHandle $handle): string
    {
        $value = $handle instanceof NavigationHandle ? $handle->value : $handle;
        self::bootDefaults();

        return self::$handles[$value] ?? self::applicationAreaHandles()[$value] ?? $value;
    }

    public static function flush(): void
    {
        self::$handles = [];
        self::$booted = false;
    }

    /**
     * Application areas are read live: hosts may declare them after this registry first boots,
     * and each one is offered as a menu handle so editors can build that area's navigation.
     *
     * @return array<string, string>
     */
    private static function applicationAreaHandles(): array
    {
        if (! app()->bound(ApplicationAreaRegistry::class)) {
            return [];
        }

        $handles = [];

        foreach (app(ApplicationAreaRegistry::class)->all() as $area) {
            $label = trim((string) $area->label);
            $handles[$area->key] = $label === '' ? $area->key : $label;
        }

        return $handles;
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
