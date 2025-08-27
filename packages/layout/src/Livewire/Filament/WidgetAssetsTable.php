<?php

declare(strict_types=1);

namespace Capell\Layout\Livewire\Filament;

use Capell\Admin\Filament\Components\Tables\Actions\CreateAction;
use Capell\Core\Data\AssetData;
use Capell\Core\Facades\CapellCore;
use Capell\Layout\Models\Widget;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;
use Livewire\Component;

class WidgetAssetsTable extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public ?Widget $record = null;

    private ?Schema $schema = null;

    private bool $withHeading = false;

    public function mount(Schema $schema, bool $withHeading = true): void
    {
        throw_if(
            ! Filament::auth()->check(),
            AuthenticationException::class
        );

        if (! $this->record instanceof Widget) {
            throw new InvalidArgumentException('The record must be an instance of Widget.');
        }

        $this->schema = $schema;

        $this->withHeading = $withHeading;
    }

    public function table(Table $table): Table
    {
        return $table->query(fn (): Builder => $this->getRelationship()->getQuery()->with(['asset']))
            ->columns([
                TextColumn::make('id'),
                TextColumn::make('asset_type'),
                TextColumn::make('asset_id'),
            ])
            ->when(
                $this->withHeading,
                fn (Table $table): Table => $table->heading(__('capell-admin::generic.assets'))
                    ->description(__('capell-admin::generic.widget_assets_description'))
            )
            ->headerActions([
                CreateAction::make()
                    ->label(__('capell-layout::button.new_asset'))
                    ->icon('heroicon-o-plus'),
                ActionGroup::make(
                    collect(CapellCore::getAssets())
                        ->map(fn (AssetData $asset): Action => static::assetTableAction($this->record, $asset))
                        ->all()
                )
                    ->label(__('capell-layout::button.select_assets'))
                    ->button()
                    ->dropdownPlacement('bottom-end'),
            ])
            ->recordActions([
                DissociateAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                ]),
            ]);
    }

    public function render(): string
    {
        return <<<'blade'
            <div wire:ignore>
                {{ $this->table }}
            </div>
        blade;
    }

    /**
     * @return HasMany
     */
    protected function getRelationship(): Relation
    {
        return $this->record->assets();
    }

    private static function assetTableAction(?Widget $record, AssetData $asset): Action
    {
        return Action::make("associate_asset_{$asset->name}")
            ->icon($asset->getIcon())
            ->label($asset->getLabel())
            ->modalContent(function (Action $action) use ($record, $asset): HtmlString {
                /** @var self $livewire */
                $livewire = $action->getLivewire();

                $componentName = 'capell-layout::livewire.assets.table.' . strtolower($asset->name);

                $existingRecords = $record->assets()->where('asset_type', $asset->name)->get()->toArray();

                return new HtmlString(Blade::render(<<<'blade'
                <livewire:is
                    :$actionId
                    :component="$componentName"
                    :$existingRecords
                 />
            blade, [
                    'actionId' => $livewire->getId() . '-associate-action-' . $asset->name,
                    'componentName' => $componentName,
                    'existingRecords' => $existingRecords,
                ]));
            })
            ->submit(null)
            ->modalSubmitAction(false)
            ->modalCancelAction(false);
    }
}
