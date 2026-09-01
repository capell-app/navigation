<?php

declare(strict_types=1);

namespace Capell\Navigation\Filament\Configurators\Navigations;

use Capell\Admin\Contracts\ConfiguratorInterface;
use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;
use Capell\Admin\Enums\SchemaExtenderEnum;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\LanguageSelect;
use Capell\Admin\Filament\Components\Forms\NameInput;
use Capell\Admin\Filament\Components\Forms\PageMorphToOptionSelect;
use Capell\Admin\Filament\Components\Forms\PublishSchema;
use Capell\Admin\Filament\Components\Forms\SiteSelect;
use Capell\Admin\Filament\Concerns\HasConfigurator;
use Capell\Admin\Filament\Livewire\PublishStatusPanel;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\PageVariationEnum;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Support\CapellCoreHelper;
use Capell\Core\Support\Slug\SlugGenerator;
use Capell\Navigation\Actions\BuildNavigationStarterItemsAction;
use Capell\Navigation\Data\NavigationItemData;
use Capell\Navigation\Data\NavigationStarterRequestData;
use Capell\Navigation\Enums\NavigationConfiguratorTypeEnum;
use Capell\Navigation\Enums\NavigationDropdownLayout;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Enums\NavigationItemActiveMode;
use Capell\Navigation\Enums\NavigationItemTarget;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Enums\NavigationItemVisibility;
use Capell\Navigation\Enums\NavigationPurpose;
use Capell\Navigation\Filament\Components\Forms\Navigation\TypeSelect;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\Registry\NavigationHandleRegistry;
use Capell\Navigation\Support\SafeUrl;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Saade\FilamentAdjacencyList\Forms\Components\AdjacencyList;

class DefaultNavigationConfigurator implements ConfiguratorInterface
{
    use HasConfigurator;

    protected static ConfiguratorTypeEnumInterface $configuratorType = NavigationConfiguratorTypeEnum::Navigation;

    /**
     * @return iterable<int, mixed>
     */
    public static function getExtenders(): iterable
    {
        return app()->tagged(SchemaExtenderEnum::Navigation->value);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function make(Schema $configurator): array
    {
        return match ($configurator->getOperation()) {
            'createOption', 'replicate' => $this->getCreateOptionFormSchema($configurator),
            'editOption' => $this->getEditOptionFormSchema($configurator),
            default => $this->getFormSchema($configurator),
        };
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function getFormSchema(Schema $configurator): array
    {
        return [
            FixedWidthSidebar::make()
                ->mainSchema($this->getMainFormSchema())
                ->sidebarSchema(
                    [
                        ...$this->publishPanel($configurator),
                        ...$this->getSettingsFormSchema($configurator),
                    ],
                    contained: true,
                ),
        ];
    }

    /**
     * The WordPress-style publish panel, pinned to the top of the navigation
     * editor sidebar. Edit only — on create there is no record to act on yet, so
     * the slim inline publish-date field in the settings schema covers that case.
     *
     * @return array<int, Livewire>
     */
    protected function publishPanel(Schema $configurator): array
    {
        $record = $configurator->getRecord();

        if ($configurator->getOperation() !== 'edit' || ! $record instanceof Navigation) {
            return [];
        }

        $key = $record->getKey();

        return [
            Livewire::make(PublishStatusPanel::class, [
                'recordClass' => Navigation::class,
                'recordId' => is_scalar($key) ? (int) $key : 0,
            ]),
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function getMainFormSchema(): array
    {
        /** @var view-string $impactPreviewView */
        $impactPreviewView = 'capell-navigation::filament.forms.navigation-impact-preview';

        return [
            Tabs::make()
                ->tabs([
                    Tab::make(__('capell-admin::form.items'))
                        ->icon(Heroicon::Bars3)
                        ->schema([
                            $this->getStarterSection(),
                            $this->getNavigationItemsField(),
                        ]),
                    Tab::make(__('capell-admin::form.settings'))
                        ->icon(Heroicon::OutlinedCog6Tooth)
                        ->statePath('meta')
                        ->schema([
                            Section::make(__('capell-navigation::generic.component_overrides'))
                                ->description(__('capell-navigation::generic.component_overrides_info'))
                                ->columns()
                                ->columnSpanFull()
                                ->collapsible()
                                ->collapsed(fn (Get $get): bool => blank($get('component')) && blank($get('component_item')))
                                ->schema([
                                    TextInput::make('component')
                                        ->label(__('capell-admin::form.component'))
                                        ->helperText(__('capell-admin::generic.menu_component_info'))
                                        ->placeholder('capell::list'),
                                    TextInput::make('component_item')
                                        ->label(__('capell-admin::form.component_item'))
                                        ->helperText(__('capell-admin::generic.menu_component_item_info'))
                                        ->placeholder('capell::list.item'),
                                ]),
                        ]),
                ]),
            Section::make(__('capell-navigation::generic.impact_preview'))
                ->description(__('capell-navigation::generic.impact_preview_description'))
                ->columnSpanFull()
                ->hiddenOn(['create', 'createOption', 'replicate'])
                ->schema([
                    View::make($impactPreviewView)
                        ->viewData(fn (Get $get): array => [
                            'siteId' => $this->stateId($get('site_id')),
                            'languageId' => $this->stateId($get('language_id')),
                        ])
                        ->columnSpanFull(),
                ]),
        ];
    }

    /**
     * The empty-item state.
     *
     * Three starting points, none of which produces a special menu type: each
     * one leaves ordinary items the editor can rename, reorder, nest, or
     * delete. Disappears as soon as the menu has an item.
     */
    protected function getStarterSection(): Section
    {
        return Section::make(__('capell-navigation::generic.starter_empty_heading'))
            ->description(__('capell-navigation::generic.starter_empty_description'))
            ->columnSpanFull()
            ->visible(fn (Get $get): bool => $this->itemsAreEmpty($get('items')))
            ->schema([
                SchemaActions::make([
                    Action::make('start_with_page')
                        ->label(__('capell-navigation::generic.starter_with_page'))
                        ->icon(Heroicon::OutlinedDocumentText)
                        ->action(fn (Get $get, Set $set): null => $this->applyBlankStarter(
                            $set,
                            NavigationItemType::Page,
                            $get('site_id'),
                        )),

                    Action::make('start_with_link')
                        ->label(__('capell-navigation::generic.starter_with_link'))
                        ->icon(Heroicon::OutlinedLink)
                        ->color('gray')
                        ->action(fn (Set $set): null => $this->applyBlankStarter(
                            $set,
                            NavigationItemType::Link,
                            null,
                        )),

                    Action::make('start_from_published_pages')
                        ->label(__('capell-navigation::generic.starter_from_pages'))
                        ->icon(Heroicon::OutlinedSquares2x2)
                        ->color('gray')
                        ->tooltip(__('capell-navigation::generic.starter_from_pages_info'))
                        ->action(fn (Get $get, Set $set): null => $this->applyPublishedPagesStarter($get, $set)),
                ])->key('starterActions'),
            ]);
    }

    protected function applyBlankStarter(Set $set, NavigationItemType $type, mixed $siteId): null
    {
        $set('items', $this->blankStarterItems($type, $siteId));

        return null;
    }

    /**
     * Fill the item list from the site's published top-level pages.
     */
    protected function applyPublishedPagesStarter(Get $get, Set $set): null
    {
        $siteId = $this->stateId($get('site_id'));

        if ($siteId === null) {
            Notification::make()
                ->warning()
                ->title(__('capell-navigation::generic.starter_from_pages_site_required'))
                ->send();

            return null;
        }

        $starter = BuildNavigationStarterItemsAction::run(
            NavigationStarterRequestData::fromState($siteId, $get('language_id')),
        );

        if ($starter->isEmpty()) {
            Notification::make()
                ->warning()
                ->title(__('capell-navigation::generic.starter_from_pages_none'))
                ->send();

            return null;
        }

        $set('items', $starter->items);

        Notification::make()
            ->success()
            ->title(__('capell-navigation::generic.starter_from_pages_added', ['count' => $starter->pageCount]))
            ->send();

        return null;
    }

    /**
     * The navigation settings sidebar.
     *
     * Purpose leads: an editor picks Main, Footer, Sub-footer, or Custom and
     * the built-in key, suggested name, and current site/language follow from
     * that choice. The key control only surfaces when the editor actually has
     * a decision to make - a custom key, or a derived key that already exists
     * for this site and language and would otherwise fail validation silently.
     * Blueprint and scheduling sit under Advanced until their state needs
     * attention.
     *
     * @return array<array-key, mixed>
     */
    protected function getSettingsFormSchema(Schema $configurator): array
    {
        $isCreating = $this->isCreateOperation($configurator);

        return [
            ...$this->getPurposeField($configurator, $isCreating),

            NameInput::make('name')
                ->required()
                ->default($isCreating ? NavigationPurpose::Main->defaultName() : null)
                ->afterStateUpdatedJs(function (string $operation): string {
                    if (! in_array($operation, ['create', 'createOption', 'replicate'], true)) {
                        return '';
                    }

                    $slugify = SlugGenerator::slugifyState("\$state ?? ''", 'key');
                    $customPurpose = NavigationPurpose::Custom->value;

                    // Only a custom menu derives its key from the name; the
                    // built-in purposes own their key.
                    return <<<JS
                        if (\$get('purpose') === '{$customPurpose}') {
                            {$slugify}
                        }
                    JS;
                }),

            $this->getKeyField($configurator, $isCreating),

            SiteSelect::make('site_id')
                ->required()
                ->reactive()
                ->afterStateUpdated(function (mixed $state, Set $set) use ($isCreating): void {
                    if (! $isCreating) {
                        return;
                    }

                    $set('language_id', $this->defaultLanguageId($this->stateId($state)));
                }),

            LanguageSelect::make('language_id')
                ->reactive()
                ->withRelationship()
                ->default(fn (Get $get): ?int => $isCreating
                    ? $this->defaultLanguageId($this->stateId($get('site_id')))
                    : null)
                ->modifyRelationQueryUsing(
                    fn (Builder $query, Get $get): Builder => $query->when(
                        $get('site_id'),
                        fn (BuilderContract $query, int $siteId): Builder => $query->whereHas(
                            'sites',
                            fn (BuilderContract $query): BuilderContract => $query->where('sites.id', $siteId),
                        ),
                    ),
                ),

            Section::make(__('capell-navigation::generic.advanced'))
                ->description(__('capell-navigation::generic.advanced_info'))
                ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                ->collapsible()
                ->collapsed(fn (Get $get): bool => ! $this->advancedRequiresAttention($get, $configurator))
                ->schema([
                    TypeSelect::make('blueprint_id')
                        ->live()
                        ->withRelation()
                        ->when(
                            $configurator->isCreating(),
                            fn (TypeSelect $component): TypeSelect => $component->withCreateForm(),
                            fn (TypeSelect $component): TypeSelect => $component->withEditForm(),
                        ),

                    PublishSchema::make($configurator),
                ]),
        ];
    }

    /**
     * The purpose selector. Creation only - an existing navigation's key is
     * already rendered by a theme, so its purpose is not a free choice.
     *
     * @return array<int, ToggleButtons>
     */
    protected function getPurposeField(Schema $configurator, bool $isCreating): array
    {
        if (! $isCreating) {
            return [];
        }

        $record = $configurator->getRecord();
        $rawState = $configurator->getRawState();
        $state = $rawState instanceof Arrayable ? $rawState->toArray() : $rawState;
        $sourceKey = $record instanceof Navigation
            ? $record->key
            : (is_array($state) && is_string($state['key'] ?? null) ? $state['key'] : null);
        $purpose = $configurator->getOperation() === 'replicate'
            ? NavigationPurpose::fromKey($sourceKey)
            : NavigationPurpose::Main;

        return [
            ToggleButtons::make('purpose')
                ->label(__('capell-navigation::generic.purpose'))
                ->helperText(__('capell-navigation::generic.purpose_info'))
                ->options(NavigationPurpose::class)
                ->default($purpose->value)
                ->dehydrated(false)
                ->disabled($configurator->getOperation() === 'replicate')
                ->live()
                ->afterStateHydrated(fn (Set $set): mixed => $set('purpose', $purpose->value))
                ->afterStateUpdated(function (mixed $state, Get $get, Set $set): void {
                    $purpose = $this->purposeFromState($state);

                    if (! $purpose instanceof NavigationPurpose) {
                        return;
                    }

                    $handle = $purpose->handle();

                    $nameIsDerived = $this->nameIsDerived($get('name'));

                    if (! $handle instanceof NavigationHandle) {
                        $set('key', null);

                        if ($nameIsDerived) {
                            $set('name', null);
                        }

                        return;
                    }

                    $set('key', $handle->value);

                    if ($nameIsDerived) {
                        $set('name', $purpose->defaultName());
                    }
                }),
        ];
    }

    /**
     * The navigation key.
     *
     * Hidden while a built-in purpose derives it, but still dehydrated and
     * validated so the unique-per-site-and-language rule keeps working. It
     * reappears the moment the derived key would collide, so the editor sees
     * the conflict rather than an error attached to an invisible field.
     */
    protected function getKeyField(Schema $configurator, bool $isCreating): Select
    {
        return Select::make('key')
            ->required()
            ->default($isCreating ? NavigationHandle::Main->value : null)
            ->hidden(fn (Get $get): bool => $isCreating && $this->keyIsDerived($get))
            ->dehydratedWhenHidden()
            ->options(fn (Get $get, ?Model $record): array => $this->navigationKeyOptions($record, $get('key')))
            ->searchable()
            ->allowHtml(false)
            ->unique(
                column: 'key',
                ignoreRecord: $configurator->getOperation() !== 'replicate',
                modifyRuleUsing: function (Unique $rule, Get $get): Unique {
                    $languageId = $this->uniqueRuleValue($get('language_id'));

                    return $rule
                        ->withoutTrashed()
                        ->where('site_id', $this->uniqueRuleValue($get('site_id')))
                        ->when(
                            $languageId !== null,
                            fn (Unique $query): Unique => $query->where('language_id', $languageId),
                            fn (Unique $query): Unique => $query->whereNull('language_id'),
                        );
                },
            )
            ->label(__('capell-admin::table.key'))
            ->helperText(__('capell-navigation::generic.key_info'));
    }

    protected function getNavigationItemsField(string $navigationFieldsKey = 'navigationTypeFields'): AdjacencyList
    {
        return AdjacencyList::make('items')
            ->label(__('capell-admin::form.navigation_items'))
            ->hiddenLabel()
            ->labelKey('label')
            ->columnSpanFull()
            ->childrenKey('children')
            ->itemLabel($this->getItemLabel(...))
            ->itemUrl($this->getItemUrl(...))
            ->addAction(
                fn (Action $action): Action => $action
                    ->label(__('capell-admin::button.add_navigation_item'))
                    ->icon('heroicon-o-plus')
                    ->color('primary'),
            )
            ->schema(fn (): array => [
                Grid::make()
                    ->schema([
                        ToggleButtons::make('type')
                            ->label(__('capell-admin::form.type'))
                            ->required()
                            ->live()
                            ->inline()
                            ->options(NavigationItemType::class)
                            ->default(NavigationItemType::Page->value)
                            ->afterStateUpdated(
                                function (ToggleButtons $component) use ($navigationFieldsKey): ?Schema {
                                    $typeFields = $component->getRootContainer()->getComponent($navigationFieldsKey);

                                    if (! $typeFields instanceof Grid) {
                                        return null;
                                    }

                                    $schema = $typeFields->getChildSchema();
                                    $schema?->fill();

                                    return $schema;
                                },
                            ),
                        $this->getLabelField(),
                        Checkbox::make('is_visible')
                            ->label(__('capell-admin::form.visible'))
                            ->default(true),
                    ]),
                Grid::make()
                    ->key($navigationFieldsKey)
                    ->whenTruthy('type')
                    ->schema(fn (Get $get): array => $this->getNavigationItemFields($get('type'))),
            ]);
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function getCreateOptionFormSchema(Schema $configurator): array
    {
        return [
            Grid::make()
                ->gridContainer()
                ->columns(['default' => 1, '@lg' => 2])
                ->schema($this->getSettingsFormSchema($configurator)),
            ...$this->getMainFormSchema(),
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function getEditOptionFormSchema(Schema $configurator): array
    {
        return [
            Grid::make()
                ->gridContainer()
                ->columns(['default' => 1, '@lg' => 2])
                ->schema($this->getSettingsFormSchema($configurator)),
            ...$this->getSettingsFormSchema($configurator),
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function getNavigationItemFields(NavigationItemType $type): array
    {
        return match ($type) {
            NavigationItemType::Page => $this->getPageNavigationItemFields(),
            NavigationItemType::Link => $this->getLinkNavigationItemFields(),
            NavigationItemType::ExternalLink => $this->getLinkNavigationItemFields(),
            NavigationItemType::Heading => $this->getHeadingNavigationItemFields(),
        };
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function getPageNavigationItemFields(): array
    {
        return [
            Grid::make()
                ->statePath('data')
                ->columnSpanFull()
                ->schema([
                    Group::make([
                        SiteSelect::make('site_id')
                            ->dehydrated(false)
                            ->default(fn (Get $get): ?int => is_numeric($get('../../site_id')) ? (int) $get('../../site_id') : null)
                            ->modifyQueryUsing(
                                fn (Builder $query, Get $get): Builder => $query->when(
                                    $get('../../site_id'),
                                    fn (Builder $query, int $siteId): Builder => $query->whereKey($siteId),
                                ),
                            )
                            ->afterStateUpdatedJs(<<<'JS'
                                $set('pageable_id', null);
                                $set('pageable_type', null);
                            JS),
                        Checkbox::make('auto_children')
                            ->label(__('capell-admin::form.auto_children'))
                            ->helperText(__('capell-admin::generic.auto_children_info'))
                            ->visible(fn (Get $get): bool => $get('pageable_type') === PageVariationEnum::Page->value),
                    ]),
                    PageMorphToOptionSelect::make()
                        ->whenTruthy('site_id')
                        ->modifyKeySelectOptionsQueryUsing(
                            fn (Builder $query, Get $get): Builder => $query->when(
                                $get('../../site_id') ?? $get('site_id'),
                                fn (Builder $query, int $siteId): Builder => $query->where('site_id', $siteId),
                            ),
                        )
                        ->modifyTypeSelectUsing(
                            fn (ToggleButtons $select): ToggleButtons => $select->default(PageVariationEnum::Page->value),
                        )
                        ->visibleJs(<<<'JS'
                            $get('site_id')
                        JS)
                        ->required(),
                    ...$this->getExtraItemFields(),
                ]),
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function getLinkNavigationItemFields(): array
    {
        return [
            Grid::make()
                ->statePath('data')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('url')
                        ->label(__('capell-admin::form.url'))
                        ->validationAttribute(strtoupper(__('capell-admin::form.url')))
                        ->required()
                        ->rules([
                            fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                if (! is_string($value) || ! $this->isSafeNavigationUrl($value)) {
                                    $fail(__('validation.url'));
                                }
                            },
                        ])
                        ->columnSpanFull()
                        ->suffixAction(
                            fn (?string $state): ?Action => $state !== null
                                ? Action::make('open_url')
                                    ->icon(Heroicon::ArrowTopRightOnSquare)
                                    ->url($state, true)
                                    ->openUrlInNewTab()
                                : null,
                        ),
                    ...$this->getExtraItemFields(),
                ]),
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function getHeadingNavigationItemFields(): array
    {
        return [
            Grid::make()
                ->statePath('data')
                ->columnSpanFull()
                ->schema([
                    Group::make()
                        ->dense()
                        ->schema([
                            IconPicker::make('icon')
                                ->label(__('capell-admin::form.icon'))
                                ->nullable(),
                        ]),
                ]),
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function getExtraItemFields(): array
    {
        return [
            Select::make('target')
                ->label(__('capell-admin::form.url_target'))
                ->options(NavigationItemTarget::class),
            Select::make('active_mode')
                ->label(__('capell-navigation::generic.active_mode'))
                ->options(NavigationItemActiveMode::class)
                ->default(NavigationItemActiveMode::Exact->value),
            Select::make('visibility')
                ->label(__('capell-navigation::generic.visibility'))
                ->options(NavigationItemVisibility::class)
                ->default(NavigationItemVisibility::Everyone->value)
                ->live(),
            TextInput::make('ability')
                ->label(__('capell-navigation::generic.visibility_ability_name'))
                ->visible(fn (Get $get): bool => $get('visibility') === NavigationItemVisibility::Ability->value),
            TextInput::make('role')
                ->label(__('capell-navigation::generic.visibility_role_name'))
                ->visible(fn (Get $get): bool => $get('visibility') === NavigationItemVisibility::Role->value),
            TextInput::make('rel')
                ->label(__('capell-navigation::generic.rel_attribute'))
                ->helperText(__('capell-navigation::generic.rel_attribute_info'))
                ->placeholder('noopener noreferrer'),
            Select::make('dropdown_layout')
                ->label(__('capell-navigation::generic.dropdown_layout'))
                ->helperText(__('capell-navigation::generic.dropdown_layout_info'))
                ->options(NavigationDropdownLayout::class)
                ->default(NavigationDropdownLayout::Dropdown->value)
                ->live(),
            TextInput::make('mega_columns')
                ->label(__('capell-navigation::generic.mega_columns'))
                ->helperText(__('capell-navigation::generic.mega_columns_info'))
                ->numeric()
                ->minValue(1)
                ->maxValue(4)
                ->default(3)
                ->visible(fn (Get $get): bool => $get('dropdown_layout') === NavigationDropdownLayout::Mega->value),
            TextInput::make('mega_panel_heading')
                ->label(__('capell-navigation::generic.mega_panel_heading'))
                ->visible(fn (Get $get): bool => $get('dropdown_layout') === NavigationDropdownLayout::Mega->value),
            TextInput::make('mega_panel_description')
                ->label(__('capell-navigation::generic.mega_panel_description'))
                ->visible(fn (Get $get): bool => $get('dropdown_layout') === NavigationDropdownLayout::Mega->value),
            TextInput::make('mega_panel_url')
                ->label(__('capell-navigation::generic.mega_panel_url'))
                ->visible(fn (Get $get): bool => $get('dropdown_layout') === NavigationDropdownLayout::Mega->value)
                ->rules([
                    fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                        if ($value !== null && $value !== '' && (! is_string($value) || ! $this->isSafeNavigationUrl($value))) {
                            $fail(__('validation.url'));
                        }
                    },
                ]),
            Group::make()
                ->dense()
                ->schema([
                    IconPicker::make('icon')
                        ->label(__('capell-admin::form.icon'))
                        ->nullable(),
                    Checkbox::make('hide_label')
                        ->label(__('capell-admin::form.hide_label'))
                        ->hint(__('capell-admin::generic.hide_label_info'))
                        ->whenTruthy('icon')
                        ->visibleJs(<<<'JS'
                            return $get('icon') !== null;
                        JS),
                ]),
        ];
    }

    /**
     * @param  array<array-key, mixed>  $item
     * @param  array<array-key, mixed>  $pageCache
     */
    protected function getItemLabel(array $item, array &$pageCache = []): ?string
    {
        $navigationItem = NavigationItemData::from($item);

        if (filled($navigationItem->label)) {
            return $navigationItem->label;
        }

        if ($navigationItem->type === NavigationItemType::Page) {
            $page = $this->getCachedPageItem($navigationItem->data, $pageCache);

            if (! $page instanceof Pageable) {
                return null;
            }

            return $page->name;
        }

        return null;
    }

    /**
     * @param  array<array-key, mixed>  $item
     * @param  array<array-key, mixed>  $pageCache
     */
    protected function getItemUrl(array $item, Get $get, array &$pageCache = []): ?string
    {
        $navigationItem = NavigationItemData::from($item);

        $url = null;

        switch ($navigationItem->type) {
            case NavigationItemType::Page:
                $languageId = $get('language_id');
                $siteId = $get('../../site_id') ?? $get('site_id');

                if (! is_numeric($siteId)) {
                    return null;
                }

                $siteId = (int) $siteId;
                $language = $this->getLanguageById($languageId, $siteId);

                $page = $this->getCachedPageItem($navigationItem->data, $pageCache, true, $language?->id, $siteId);

                if (! $page instanceof Pageable) {
                    return null;
                }

                $url = $page->pageUrl?->full_url;
                break;
            case NavigationItemType::Link:
            case NavigationItemType::ExternalLink:
                $url = $navigationItem->data['url'];
                break;
            case NavigationItemType::Heading:
                $url = null;
                break;
        }

        return $url;
    }

    protected function getLanguageById(?int $languageId, int $siteId): ?Language
    {
        return CapellCoreHelper::getLanguageByIdOrSite($languageId, $siteId);
    }

    private function uniqueRuleValue(mixed $value): int|string|null
    {
        if (is_string($value)) {
            return $value !== '' ? $value : null;
        }

        return is_int($value) ? $value : null;
    }

    private function stateId(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT);

        return is_int($integer) ? $integer : null;
    }

    /**
     * @return array<string, string>
     */
    private function navigationKeyOptions(?Model $record, mixed $currentKey): array
    {
        $options = NavigationHandleRegistry::options();

        if ($record instanceof Navigation) {
            $this->addNavigationKeyOption($options, $record->key);
        }

        $this->addNavigationKeyOption($options, is_string($currentKey) ? $currentKey : null);

        return $options;
    }

    /**
     * @param  array<string, string>  $options
     */
    private function addNavigationKeyOption(array &$options, ?string $key): void
    {
        $normalizedKey = $key === null ? '' : trim($key);

        if ($normalizedKey === '' || array_key_exists($normalizedKey, $options)) {
            return;
        }

        $options[$normalizedKey] = NavigationHandleRegistry::label($normalizedKey);
    }

    /**
     * Get a Page model from cache, loading if necessary.
     *
     * @param  array<string, Pageable|null>  $pageCache
     * @param  array<array-key, mixed>  $data
     */
    private function getCachedPageItem(array $data, array &$pageCache, bool $withUrl = false, ?int $languageId = null, ?int $siteId = null): ?Pageable
    {
        $pageId = $data['pageable_id'] ?? null;
        $pageType = $data['pageable_type'] ?? null;

        if ($pageId === null || ! is_string($pageType)) {
            return null;
        }

        $cacheKey = implode(':', [
            $pageType,
            (string) $pageId,
            $siteId === null ? 'any-site' : (string) $siteId,
            $withUrl ? (string) ($languageId ?? 'any-language') : 'no-url',
        ]);

        if (array_key_exists($cacheKey, $pageCache)) {
            return $pageCache[$cacheKey];
        }

        /** @var class-string<Pageable&Model> $model */
        $model = Relation::getMorphedModel($pageType) ?? Page::class;

        $query = $model::query();

        if ($siteId !== null && DatabaseSchema::hasColumn((new $model)->getTable(), 'site_id')) {
            $query->where('site_id', $siteId);
        }

        if ($withUrl && $languageId !== null) {
            $page = $query->with([
                'pageUrl.siteDomain' => fn (BuilderContract $query): BuilderContract => $query->orderByRaw(
                    'CASE WHEN language_id = ? THEN 0 ELSE 1 END',
                    [$languageId],
                ),
            ])
                ->find($pageId);
        } else {
            $page = $query->find($pageId);
        }

        if (! $page instanceof Pageable) {
            $pageCache[$cacheKey] = null;

            return null;
        }

        $pageCache[$cacheKey] = $page;

        return $page;
    }

    private function isCreateOperation(Schema $configurator): bool
    {
        return in_array($configurator->getOperation(), ['create', 'createOption', 'replicate'], true);
    }

    private function purposeFromState(mixed $state): ?NavigationPurpose
    {
        if ($state instanceof NavigationPurpose) {
            return $state;
        }

        return is_string($state) ? NavigationPurpose::tryFrom($state) : null;
    }

    /**
     * True when the name is still one of the suggested purpose names, so
     * switching purpose may safely replace it.
     */
    private function nameIsDerived(mixed $name): bool
    {
        if (! is_string($name) || trim($name) === '') {
            return true;
        }

        $name = trim($name);

        return array_any(
            NavigationPurpose::cases(),
            fn (NavigationPurpose $purpose): bool => $purpose->defaultName() === $name,
        );
    }

    /**
     * True when the purpose derives the key and that key is still free for the
     * selected site and language.
     */
    private function keyIsDerived(Get $get): bool
    {
        $purpose = $this->purposeFromState($get('purpose'));

        if (! $purpose instanceof NavigationPurpose || ! $purpose->handle() instanceof NavigationHandle) {
            return false;
        }

        return ! $this->keyConflictExists($get);
    }

    private function keyConflictExists(Get $get): bool
    {
        $key = $get('key');

        if (! is_string($key) || trim($key) === '') {
            return false;
        }

        $siteId = $this->stateId($get('site_id'));
        $languageId = $this->stateId($get('language_id'));

        return Navigation::query()
            ->where('key', trim($key))
            ->where('site_id', $siteId)
            ->when(
                $languageId !== null,
                fn (Builder $query): Builder => $query->where('language_id', $languageId),
                fn (Builder $query): Builder => $query->whereNull('language_id'),
            )
            ->exists();
    }

    /**
     * The language a new navigation should default to: the selected site's own
     * language, falling back to the installation default.
     */
    private function defaultLanguageId(?int $siteId): ?int
    {
        if ($siteId === null) {
            return null;
        }

        $site = Site::query()->find($siteId);

        if (! $site instanceof Site) {
            return null;
        }

        // Only a language the site actually offers is a valid default; anything
        // else would be rejected by the language select's own options.
        $languageId = Language::query()
            ->whereHas('sites', fn (Builder $query): Builder => $query->where('sites.id', $siteId))
            ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$site->getAttribute('language_id')])
            ->orderByDesc('default')
            ->orderBy('id')
            ->value('id');

        return is_numeric($languageId) ? (int) $languageId : null;
    }

    /**
     * Advanced settings stay collapsed unless something in them already needs
     * a decision: a custom key, a schedule, or a non-default blueprint.
     */
    private function advancedRequiresAttention(Get $get, Schema $configurator): bool
    {
        if ($this->isCreateOperation($configurator)
            && $this->purposeFromState($get('purpose')) === NavigationPurpose::Custom) {
            return true;
        }

        if (filled($get('visible_from')) || filled($get('visible_until'))) {
            return true;
        }

        $blueprintId = $this->stateId($get('blueprint_id'));

        if ($blueprintId === null) {
            return false;
        }

        $defaultBlueprintId = Blueprint::query()
            ->where('type', 'navigation')
            ->orderBy('id')
            ->value('id');

        return is_numeric($defaultBlueprintId) && (int) $defaultBlueprintId !== $blueprintId;
    }

    private function itemsAreEmpty(mixed $items): bool
    {
        if ($items instanceof Arrayable) {
            $items = $items->toArray();
        }

        return ! is_array($items) || $items === [];
    }

    /**
     * One ordinary, empty navigation item of the requested type, ready to be
     * edited in the item list.
     *
     * @return array<string, array<string, mixed>>
     */
    private function blankStarterItems(NavigationItemType $type, mixed $siteId): array
    {
        $data = $type === NavigationItemType::Page
            ? array_filter(['site_id' => $this->stateId($siteId)], fn (mixed $value): bool => $value !== null)
            : ['url' => null];

        return [
            (string) Str::uuid() => [
                'label' => null,
                'type' => $type->value,
                'data' => $data,
                'children' => [],
                'is_visible' => true,
            ],
        ];
    }

    private function isSafeNavigationUrl(string $url): bool
    {
        return SafeUrl::isSafe($url);
    }

    private function getLabelField(): TextInput
    {
        return TextInput::make('label')
            ->label(__('capell-admin::form.label'))
            ->requiredIf('type', NavigationItemType::Link->value)
            ->requiredIf('type', NavigationItemType::ExternalLink->value)
            ->requiredIf('type', NavigationItemType::Heading->value)
            ->helperText(
                function (Get $get): ?string {
                    if (! in_array($get('type'), [NavigationItemType::Link->value, NavigationItemType::ExternalLink->value], true)) {
                        return null;
                    }

                    $text = __('capell-admin::generic.navigation_page_label_info');

                    return is_string($text) ? $text : null;
                },
            );
    }
}
