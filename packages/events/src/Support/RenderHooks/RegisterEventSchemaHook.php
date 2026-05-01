<?php

declare(strict_types=1);

namespace Capell\Events\Support\RenderHooks;

use Capell\Events\Actions\BuildEventSchemaAction;
use Capell\Events\Models\Event;
use Capell\Events\Models\EventOccurrence;
use Capell\Frontend\Data\RenderHookContext;
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Enums\RenderHookScenario;
use Capell\Frontend\Support\Render\RenderHookRegistry;

class RegisterEventSchemaHook
{
    public function __construct(private readonly RenderHookRegistry $registry) {}

    public function register(): void
    {
        $this->registry->register(
            RenderHookLocation::HeadClose,
            function (RenderHookContext $context): string {
                $item = is_array($context->item) ? $context->item : [];
                $page = $item['page'] ?? null;

                if (! $page instanceof Event) {
                    return '';
                }

                $page->loadMissing(['nextOccurrence', 'pageUrl', 'translation']);

                $occurrence = $page->nextOccurrence;

                if (! $occurrence instanceof EventOccurrence) {
                    return '';
                }

                return view('capell-events::components.event-schema', [
                    'schema' => BuildEventSchemaAction::run($page, $occurrence),
                ])->render();
            },
            scenario: RenderHookScenario::SeoMeta->value,
        );
    }
}
