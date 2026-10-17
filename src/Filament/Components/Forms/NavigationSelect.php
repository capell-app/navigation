<?php

declare(strict_types=1);

namespace Capell\Navigation\Filament\Components\Forms;

use Capell\Admin\Filament\Actions\HintEditAction;
use Capell\Admin\Filament\Concerns\HasCustomSelectOption;
use Capell\Core\Contracts\Pageable;
use Capell\Navigation\Actions\BuildPageNavigationReferencesAction;
use Capell\Navigation\Filament\Resources\Navigations\NavigationResource;
use Capell\Navigation\Models\Navigation;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Route;
use Override;

class NavigationSelect extends Select
{
    use HasCustomSelectOption;

    protected bool $usesKeyAsValue = false;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::form.navigation'))
            ->searchable()
            ->allowHtml()
            ->preload()
            ->dehydrateStateUsing(fn (self $component): mixed => $component->getState())
            ->options(function (Get $get): array {
                $navigations = $this->getEligibleNavigations($get);

                $selectedValues = $this->usesKeyAsValue ? (array) $this->getState() : [];
                $legacyValues = array_filter(
                    $selectedValues,
                    fn (mixed $value): bool => ! is_string($value) || $navigations->whereStrict('key', $value)->isEmpty(),
                );

                return $navigations->mapWithKeys(
                    function (Navigation $navigation) use ($legacyValues): array {
                        $data = [
                            'label' => $navigation->name ?? '',
                            'icon' => $navigation->language !== null ? 'flag-4x3-' . $navigation->language->flag : '',
                            'description' => $navigation->site->name ?? '',
                        ];

                        // Keep selected legacy IDs unchanged while offering portable keys for new choices.
                        $legacyId = in_array($navigation->id, $legacyValues, true)
                            || in_array((string) $navigation->id, $legacyValues, true);
                        $value = $this->usesKeyAsValue && ! $legacyId ? $navigation->key : $navigation->getKey();

                        return [$value => static::getSelectOption($navigation, $data)];
                    },
                )
                    ->all();
            })
            ->hintAction(
                HintEditAction::make('edit-navigation')
                    ->record(
                        function (self $component, Get $get, null|int|string $state): ?Navigation {
                            if ($state === null || $state === '') {
                                return null;
                            }

                            $navigations = $component->getEligibleNavigations($get);
                            $matches = is_string($state) ? $navigations->whereStrict('key', $state) : new Collection;

                            if (is_numeric($state) && (! $component->usesKeyAsValue || $matches->isEmpty())) {
                                $matches = $navigations->where('id', $state);
                            }

                            // A portable key may identify several site or language variants.
                            return $matches->count() === 1 ? $matches->first() : null;
                        },
                    )
                    ->url(
                        function (HintEditAction $action): string {
                            /** @var Navigation $record */
                            $record = $action->getRecord();

                            if (! Route::has(NavigationResource::getRouteBaseName() . '.edit')) {
                                return '#';
                            }

                            return NavigationResource::getUrl('edit', ['record' => $record->id]);
                        },
                    )
                    ->visible(function (self $component, string $operation, HintEditAction $action): bool {
                        if ($component->isMultiple()) {
                            return false;
                        }

                        /** @var Navigation $record */
                        $record = $action->getRecord();

                        return $record instanceof Navigation;
                    }),
            );
    }

    public function useKeyAsValue(bool $condition = true): static
    {
        $this->usesKeyAsValue = $condition;

        return $this;
    }

    #[Override]
    public function getState(): mixed
    {
        $state = parent::getState();

        if (! $this->usesKeyAsValue || collect((array) $state)->doesntContain(fn (mixed $value): bool => is_int($value))) {
            return $state;
        }

        $navigations = $this->getEligibleNavigations($this->makeGetUtility());
        // Filament casts numeric option strings to integers; portable keys must stay strings.
        $restoreKey = fn (mixed $value): mixed => is_int($value) && $navigations->whereStrict('key', (string) $value)->isNotEmpty()
            ? (string) $value
            : $value;

        return is_array($state) ? array_map($restoreKey, $state) : $restoreKey($state);
    }

    /**
     * @return Collection<int, Navigation>
     */
    private function getEligibleNavigations(Get $get): Collection
    {
        $record = $this->getRecord();

        if ($record instanceof Pageable) {
            return BuildPageNavigationReferencesAction::run($record);
        }

        /** @var class-string<Navigation> $model */
        $model = Navigation::class;
        $siteId = $get('../site_id');

        return $model::query()
            ->when($siteId, fn (Builder $query): Builder => $query->where(
                fn (Builder $query): Builder => $query->where('site_id', $siteId)
                    ->orWhereNull('site_id'),
            ))
            ->with(['language', 'site'])
            ->orderBy('site_id')
            ->orderBy('name')
            ->orderBy('language_id')
            ->get();
    }
}
