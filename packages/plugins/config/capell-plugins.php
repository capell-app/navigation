<?php

declare(strict_types=1);

return [
    /*
     * When false, the legacy Capell\Admin\Filament\Pages\PluginsPage remains active
     * and this package registers nothing. Flip to true after migrations run.
     */
    'enabled' => env('CAPELL_PLUGINS_ENABLED', false),

    'anystack' => [
        'base_url' => env('CAPELL_ANYSTACK_BASE_URL', 'https://api.anystack.sh/v1'),
        'composer_repo_url' => env('CAPELL_ANYSTACK_COMPOSER_REPO', 'https://repo.anystack.sh'),
        'timeout_seconds' => 15,
    ],

    'license_heartbeat' => [
        'cache_ttl_hours' => 24,
        'offline_grace_days' => 14,
    ],

    'composer' => [
        'binary' => env('CAPELL_COMPOSER_BINARY', 'composer'),
        'timeout_seconds' => 600,
    ],
];
