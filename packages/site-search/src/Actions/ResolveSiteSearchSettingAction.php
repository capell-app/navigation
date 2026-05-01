<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Actions;

use Capell\SiteSearch\Settings\SiteSearchSettings;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

final class ResolveSiteSearchSettingAction
{
    use AsAction;

    public function handle(string $settingKey, string $configKey, mixed $default): mixed
    {
        try {
            if (class_exists(SiteSearchSettings::class)) {
                $settings = resolve(SiteSearchSettings::class);
                $settingsValue = data_get($settings, $settingKey);

                if ($settingsValue !== null) {
                    return $settingsValue;
                }
            }
        } catch (Throwable) {
            //
        }

        return config($configKey, $default);
    }
}
