<?php

declare(strict_types=1);

namespace Capell\Navigation\Actions;

use Capell\Navigation\Models\Navigation;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use DateTimeInterface;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use Spatie\LaravelData\DataCollection;

final class ResolveNavigationCacheExpiryAction
{
    use AsFake;
    use AsObject;

    /** @param list<string> $handles */
    public function handle(array $handles, int $ttlSeconds = 300, ?CarbonImmutable $now = null): CarbonImmutable
    {
        $items = [];
        // Scheduled records must participate even before becoming visible.
        foreach (Navigation::query()->whereIn('key', $handles)->get(['items', 'visible_from', 'visible_until']) as $navigation) {
            $payload = $navigation->items;
            $items[] = ['visible_from' => $navigation->visible_from, 'visible_until' => $navigation->visible_until, 'items' => $payload instanceof DataCollection ? $payload->toArray() : ($payload ?? [])];
        }

        return $this->forItems($items, $ttlSeconds, $now);
    }

    /** @param array<array-key, mixed> $items */
    public function forItems(array $items, int $ttlSeconds = 300, ?CarbonImmutable $now = null): CarbonImmutable
    {
        $now ??= CarbonImmutable::now();
        $expiry = $now->addSeconds(max(0, $ttlSeconds));
        foreach ($items as $key => $value) {
            if (is_array($value)) {
                $candidate = $this->forItems($value, $ttlSeconds, $now);
                if ($candidate->lt($expiry)) {
                    $expiry = $candidate;
                }
            } elseif (in_array($key, ['visible_from', 'visible_until'], true) && ($value instanceof DateTimeInterface || (is_string($value) && $value !== ''))) {
                try {
                    $date = CarbonImmutable::parse($value);
                    if ($date->gt($now) && $date->lt($expiry)) {
                        $expiry = $date;
                    }
                } catch (InvalidFormatException) {
                    // Invalid editorial dates cannot shorten the cache lifetime.
                }
            }
        }

        return $expiry;
    }
}
