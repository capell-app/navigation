<?php

declare(strict_types=1);

namespace Capell\Redirects\Filament\Resources\Redirects\Pages;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Actions\CreateAction;
use Capell\Redirects\Filament\Exports\RedirectExporter;
use Capell\Redirects\Filament\Imports\RedirectImporter;
use Capell\Redirects\Filament\Resources\Redirects\RedirectResource;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Gate;
use Override;

class ManageRedirects extends ManageRecords
{
    /** @return class-string<RedirectResource> */
    #[Override]
    public static function getResource(): string
    {
        return CapellAdmin::getResource('Redirect');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('redirects::hints.redirects');
    }

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
            ImportAction::make()
                ->authorize(fn (): bool => Gate::allows('import', RedirectResource::getModel()))
                ->importer(RedirectImporter::class),
            ExportAction::make()
                ->authorize(fn (): bool => Gate::allows('export', RedirectResource::getModel()))
                ->exporter(RedirectExporter::class),
        ];
    }
}
