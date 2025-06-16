<?php

declare(strict_types=1);

namespace Capell\Layout\Actions;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Layout\Models\Content;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static Content run(Content $content, array $data = [])
 */
class ReplicateContentAction
{
    use AsObject;

    public function handle(Content $content, array $data = []): Content
    {
        $content->load('translations');

        $translations = [];
        if (isset($data['translations'])) {
            $translations = $data['translations'];
            unset($data['translations']);
        }

        /** @var Content $className */
        $className = $content::class;

        $model = $className::withDrafts()->find($content->getKey());

        $model->fill($data);

        $replica = $model->duplicate();

        if ($model->isClean('name')) {
            $replica->name = $this->getContentName($content);
        }

        $replica->created_at = now();
        $replica->updated_at = now();

        if ($replica->isPublished()) {
            $replica->published_at = now();
        }

        $replica->uuid = null;

        $className::setupNewModel($replica);

        $replica->save();

        if ($translations) {
            foreach ($translations as $translation) {
                $replica->translations()->create($translation);
            }

            $replica->load('translations');
        }

        return $replica;
    }

    private function getContentName(Content $content): string
    {
        $name = CapellAdmin::incrementName($content->name);

        while (CapellCore::getModel('content')::where('name', $name)->exists()) {
            $name = CapellAdmin::incrementName($name);
        }

        return $name;
    }
}
