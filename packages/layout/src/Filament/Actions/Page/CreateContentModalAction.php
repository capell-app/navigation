<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Actions\Page;

use Capell\Admin\Filament\Actions\CreateModalAction;
use Capell\Layout\Actions\MutateContentDataBeforeCreateAction;
use Illuminate\Database\Eloquent\Model;
use Override;

class CreateContentModalAction extends CreateModalAction
{
    #[Override]
    protected function mutateRecordBeforeSave(Model $record, array $data): array
    {
        if (! empty($data['is_published']) && $data['is_published'] === true) {
            $record->forceFill([
                'is_published' => true,
                'is_current' => true,
            ]);
        }

        return $data;
    }

    #[Override]
    protected function mutateFormData(array $data): array
    {
        return MutateContentDataBeforeCreateAction::run($data);
    }
}
