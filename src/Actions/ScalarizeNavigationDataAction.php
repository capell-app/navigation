<?php

declare(strict_types=1);

namespace Capell\Navigation\Actions;

use BackedEnum;
use LogicException;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class ScalarizeNavigationDataAction
{
    use AsFake;
    use AsObject;

    /** @param array<array-key, mixed> $items
     * @return array<array-key, mixed>
     */
    public function handle(array $items): array
    {
        foreach ($items as $key => $value) {
            $items[$key] = match (true) {
                is_array($value) => $this->handle($value),
                $value instanceof BackedEnum => $value->value,
                $value === null, is_scalar($value) => $value,
                default => throw new LogicException('Cached navigation must contain only scalar render data.'),
            };
        }

        return $items;
    }
}
