<?php

declare(strict_types=1);

namespace Capell\Layout\Actions;

use Capell\Admin\Actions\BuilderDefaultTranslationsAction;
use Capell\Core\Facades\CapellCore;
use Capell\Layout\Enums\LayoutModelEnum;
use Capell\Layout\Models\Content;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static array run(array $data = [])
 */
class MutateContentDataBeforeFillAction
{
    use AsObject;

    public function handle(array $data = []): array
    {
        $data = MutateContentDataBeforeCreateAction::run($data);

        if (empty($data['translations'])) {
            $data['translations'] = BuilderDefaultTranslationsAction::run($data['site_id'] ?? null);
        }

        return $data;
    }
}
