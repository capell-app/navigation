<?php

declare(strict_types=1);

namespace Capell\Events\Support;

use Capell\Core\Data\PageVariationData;
use Capell\Core\Facades\CapellCore;
use Capell\Events\Models\Event;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

class EventsModelRegistrar
{
    /** @var list<class-string> */
    private const MODELS = [
        Event::class,
    ];

    public static function register(): void
    {
        CapellCore::registerModels(self::MODELS);

        CapellCore::registerPageVariation(
            new PageVariationData(
                name: 'event',
                model: Event::class,
                resourceName: 'event',
            ),
        );

        Relation::morphMap(
            collect(self::MODELS)
                ->mapWithKeys(fn (string $modelClass): array => [Str::snake(class_basename($modelClass)) => $modelClass])
                ->all(),
        );
    }
}
