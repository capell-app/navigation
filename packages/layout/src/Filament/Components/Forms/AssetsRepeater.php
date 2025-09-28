<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms;

use Capell\Admin\Actions\GetAssetResourceUrlAction;
use Capell\Admin\Actions\ModifyCreateAction;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Data\AssetData;
use Capell\Core\Enums\TypeGroupEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Database\Query\Builder as BuilderContract;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Kalnoy\Nestedset\NestedSet;

class AssetsRepeater extends Repeater
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->relationship()
            ->orderColumn('order')
            ->defaultItems(1)
            ->table([
                TableColumn::make(__('capell-admin::form.asset')),
            ])
            ->addAction(self::modifyAddAction(...))
            ->schema(self::getFormSchema())
            ->extraItemActions([
                Action::make('edit_asset')
                    ->visible(
                        fn (array $arguments, Repeater $component): bool => ! empty(
                            $component->getRawItemState($arguments['item'])['asset_id']
                        )
                    )
                    ->tooltip(function (array $arguments, Repeater $component): ?string {
                        $itemData = $component->getRawItemState($arguments['item']);

                        return __(
                            'capell-admin::button.edit_asset_type',
                            ['type' => $itemData['asset_type']]
                        );
                    })
                    ->icon(Heroicon::PencilSquare)
                    ->url(
                        function (array $arguments, Repeater $component): ?string {
                            $itemData = $component->getRawItemState($arguments['item']);

                            return GetAssetResourceUrlAction::run($itemData['asset_type'], $itemData['asset_id']);
                        },
                        shouldOpenInNewTab: true
                    ),
            ])
            ->registerActions([
                fn (self $component): Action => $component->getAddAssetAction(),
            ]);
    }

    public function getAddAssetAction(): Action
    {
        return Action::make('add_asset')
            ->action(function (Repeater $component, array $arguments): void {
                $newUuid = $component->generateUuid();

                $items = $component->getRawState();

                if ($newUuid !== null && $newUuid !== '' && $newUuid !== '0') {
                    $items[$newUuid] = $arguments;
                } else {
                    $items[] = $arguments;
                }

                $component->rawState($items);

                $component->getChildSchema($newUuid ?? array_key_last($items))->fill($arguments);

                $component->collapsed(false, shouldMakeComponentCollapsible: false);

                $component->callAfterStateUpdated();

                $component->partiallyRender();
            });
    }

    protected static function getFormSchema(): array
    {
        $select = Select::make('asset_id');

        $createOptionUsing = $select->getCreateOptionUsing();

        return [
            Hidden::make('asset_type'),
            $select
                ->label(__('capell-layout::form.select_add_asset_type'))
                ->required()
                ->searchable()
                ->placeholder(
                    fn (Get $get): string => __(
                        'capell-admin::generic.select_asset_placeholder',
                        ['asset' => CapellCore::getAsset($get('asset_type'))->getLabel()]
                    )
                )
                ->prefixIcon(
                    fn (Get $get): null|string|Heroicon => CapellCore::getAsset($get('asset_type'))->getIcon()
                )
                ->selectablePlaceholder(false)
                ->getSearchResultsUsing(
                    static fn (Select $component, Get $get, string $search): array => self::getAssetOptions(
                        $component,
                        $get('asset_type'),
                        limit: $component->getOptionsLimit(),
                        search: $search
                    )
                )
                ->options(
                    fn (Select $component, Get $get): array => self::getAssetOptions(
                        $component,
                        $get('asset_type'),
                        limit: $component->getOptionsLimit()
                    )
                )
                ->createOptionForm(function (Schema $schema, Get $get): Schema {
                    $asset = CapellCore::getAsset($get('asset_type'));

                    $assetAdmin = CapellAdmin::getAsset($get('asset_type'));

                    return $assetAdmin->formClass::configure(
                        $schema->operation('createOption')->model($asset->model)
                    );
                })
                ->createOptionUsing(function (Select $component, Schema $schema, Get $get, array $data) use ($createOptionUsing): int|string {
                    $asset = CapellAdmin::getAsset($get('asset_type'));

                    $record = $asset->createAction !== null && $asset->createAction !== '' && $asset->createAction !== '0'
                        ? $asset->createAction::run($data)
                        : $component->evaluate($createOptionUsing);

                    $schema->model($record)->saveRelationships();

                    Notification::make()
                        ->title(__('capell-admin::message.page_created_successfully'))
                        ->body($record->name)
                        ->send();

                    return $record->getKey();
                })
                ->createOptionAction(function (Action $action, Get $get): Action {
                    $asset = CapellAdmin::getAsset($get('asset_type'));

                    return ModifyCreateAction::run($action)
                        ->fillForm(fn (): array => $asset->defaultDataAction !== null && $asset->defaultDataAction !== '' && $asset->defaultDataAction !== '0' ? $asset->defaultDataAction::run() : []);
                })
                ->getOptionLabelFromRecordUsing(fn (Model $record): string => $record->name),
        ];
    }

    protected static function getAssetOptionsFromResults($results, AssetData $asset): Collection
    {
        if ($asset->name === 'Page') {
            return self::getPageAssetOptions($results);
        }

        return $results->pluck('name', 'id');
    }

    protected static function getPageAssetOptions($results): Collection
    {
        $options = collect();

        $results->each(function (Page $page) use (&$options): void {
            $label = $page->site->name . ' » ';

            $ancestors = $page->ancestors()->get();

            if ($ancestors->isNotEmpty()) {
                $label .= $ancestors->pluck('name')
                    ->map(fn ($item) => Str::limit($item, 30))
                    ->implode(' » ')
                    . ' » ';
            }

            $label .= Str::limit($page->name, 40);

            $options->put($page->id, $label);
        });

        return $options;
    }

    protected static function modifyAddAction(Action $action, self $component): Action
    {
        $actions = ActionGroup::make(
            CapellCore::getAssets()
                ->sortBy('name')
                ->map(
                    fn (AssetData $asset): Action => $component->getAddAssetAction()
                        ->schemaComponent($component)
                        ->label($asset->getLabel())
                        ->icon($asset->getIcon())
                        ->arguments(['asset_type' => $asset->getKey()])
                )
                ->all()
        )
            ->dropdownPlacement('bottom')
            ->label(fn (): string|Htmlable|null => $action->getLabel())
            ->icon(Heroicon::Plus);

        return $action->group($actions)
            ->view('capell-admin::components.actions.dropdown-group');
    }

    private static function getAssetOptions(Select $component, ?string $type, int $limit = 10, ?string $search = null): array
    {
        if ($type === null || $type === '' || $type === '0') {
            return [];
        }

        $asset = CapellCore::getAsset($type);

        /* @var class-string<Model> $model */
        $model = $asset->model;

        $query = $model::query()
            ->select([
                'id',
                'id',
                'name',
            ])
            ->when(
                $asset->name === 'Page',
                fn (BuilderContract $query) => $query->with([
                    'ancestors' => fn (Relation $query) => $query->withDrafts(),
                    'site',
                ])
                    ->addSelect([
                        'pages.site_id',
                        'pages.parent_id',
                        'pages._lft',
                        'pages._rgt',
                    ])
                    ->withDrafts()
                    ->orderBy('site_id')
                    ->orderBy(NestedSet::LFT, 'DESC')
                    ->whereHas(
                        'type',
                        fn (Builder $query) => $query->where(
                            fn (Builder $query) => $query->where('group', '!=', TypeGroupEnum::System->value)
                                ->orWhereNull('group')
                        )
                    )
            )
            ->when(
                $search,
                fn (Builder $query, string $search): Builder => $query->where(
                    'name',
                    'like',
                    sprintf('%%%s%%', $search)
                )
            );

        $total = $query->count();

        $results = $query->limit($limit)->get();

        $options = self::getAssetOptionsFromResults($results, $asset);

        if ($total > $limit) {
            $options->pop();
            $options->put(null, __('capell-admin::form.more_results', ['count' => $total - $limit]));
            $component->disableOptionWhen(fn (string $value): bool => $value === '' || $value === '0');
        }

        return $options->toArray();
    }
}
