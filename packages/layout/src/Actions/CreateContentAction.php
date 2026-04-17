<?php

declare(strict_types=1);

namespace Capell\Layout\Actions;

use Capell\Core\Facades\CapellCore;
use Capell\Layout\Enums\ModelEnum;
use Capell\Layout\Models\Collection;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static Content run(array $data)
 */
class CreateContentAction
{
    use AsObject;

    public function createTranslations(Collection $content, array $translations): void
    {
        foreach ($translations as $translation) {
            $content->translations()->create([
                'language_id' => $translation['language_id'],
                'title' => $translation['title'],
                'content' => $translation['content'],
            ]);
        }
    }

    public function handle(array $data): Collection
    {
        /** @var class-string<Content> $model */
        $model = CapellCore::getModel(ModelEnum::Content->name);

        if (! isset($data['name']) && blank($data['name']) && isset($data['translations'])) {
            $data['name'] = collect($data['translations'])->first()['title'];
        }

        $content = $model::create($data);

        if (isset($data['translations'])) {
            $this->createTranslations($content, $data['translations']);
        }

        return $content;
    }
}
