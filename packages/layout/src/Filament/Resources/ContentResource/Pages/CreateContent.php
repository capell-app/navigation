<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\ContentResource\Pages;

use Capell\Admin\Actions\MutateContentDataBeforeCreateAction;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Layout\Filament\Resources\ContentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContent extends CreateRecord
{
    /** @return class-string<ContentResource> */
    public static function getResource(): string
    {
        return CapellAdmin::getFilamentResource('content');
    }

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $this->form->fill(MutateContentDataBeforeCreateAction::run());

        $this->callHook('afterFill');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return MutateContentDataBeforeCreateAction::run(
            parent::mutateFormDataBeforeCreate($data)
        );
    }
}
