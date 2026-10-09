<?php

declare(strict_types=1);

return [
    'children' => [
        // Shared visitor IPs and menus with many lazy items need a larger allowance.
        'rate_limit_per_minute' => 300,
    ],
];
