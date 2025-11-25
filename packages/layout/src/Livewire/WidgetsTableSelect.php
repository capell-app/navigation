<?php

declare(strict_types=1);

namespace Capell\Layout\Livewire;

use Capell\Core\Facades\CapellCore;
use Capell\Layout\Enums\ModelEnum;
use Capell\Layout\Filament\Resources\Widgets\Tables\WidgetsTable;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Modelable;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use LogicException;

class WidgetsTableSelect extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;
    use WithoutUrlPagination;

    #[Locked]
    public string $actionModalId;

    #[Locked]
    public bool $isDisabled = false;

    #[Locked]
    public ?int $maxSelectableRecords = null;

    #[Locked]
    public ?string $model = null;

    #[Locked]
    public ?Model $record = null;

    #[Locked]
    public ?string $relationshipName = null;

    #[Locked]
    public string $tableConfiguration = WidgetsTable::class;

    /**
     * @var array<mixed>
     */
    #[Locked]
    public array $tableArguments = [];

    /**
     * @var string | array<string> | null
     */
    #[Modelable]
    public string|array|null $selectedRecords = null;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    /**
     * Flexible provider for table records.
     * If set, this will be used instead of relationship.
     *
     * @var (Closure(Table): Builder)|Builder|null
     */
    #[Locked]
    public $tableQuery;

    public ?Collection $containers = null;

    public ?string $containerKey = null;

    public function mount(): void
    {
        throw_if(
            ! Filament::auth()->check(),
            AuthenticationException::class,
        );
    }

    public function table(Table $table): Table
    {
        $tableConfiguration = $this->tableConfiguration;

        throw_unless(
            class_exists($tableConfiguration),
            LogicException::class,
            sprintf('Table configuration class [%s] does not exist.', $tableConfiguration))
        ;

        throw_unless(
            method_exists($tableConfiguration, 'configure'),
            LogicException::class,
            sprintf(
                'Table configuration class [%s] does not have a [configure(Table $table): Table] method.',
                $tableConfiguration
            )
        );

        $tableConfiguration::configure($table);

        $table
            ->query($this->getTableQuery())
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([])
            ->emptyStateActions([])
            ->selectable()
            ->trackDeselectedRecords(false)
            ->filtersFormWidth('4xl')
            ->filtersFormColumns([
                'sm' => 2,
                'lg' => 3,
            ])
            ->deferFilters(false)
            ->currentSelectionLivewireProperty('selectedRecords')
            ->maxSelectableRecords($this->maxSelectableRecords)
            ->deselectAllRecordsWhenFiltered(false)
            ->disabledSelection($this->isDisabled)
            ->arguments($this->getTableArguments());

        return $table;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->fill([
                'container' => $this->containerKey ?? session('layout-builder.container'),
            ])
            ->components([
                Select::make('container')
                    ->label(__('capell-admin::form.container'))
                    ->hiddenLabel()
                    ->prefix(fn (Select $component): string => $component->getLabel() . ': ')
                    ->required()
                    ->options($this->containers),
            ]);
    }

    /**
     * @return array<mixed>
     */
    public function getTableArguments(): array
    {
        return $this->tableArguments;
    }

    public function selectRecords(): void
    {
        $this->dispatch(
            'add-widgets-to-container',
            containerKey: $this->data['container'] ?? null,
            widgets: $this->selectedRecords,
        );

        $this->resetPage();

        $this->dispatch('close-modal', id: $this->actionModalId);
    }

    public function selectRecordsAction(): Action
    {
        return Action::make('selectRecords')
            ->label(__('capell-layout::button.add_widgets_container'))
            ->button()
            ->color('primary')
            ->submit('selectRecords');
    }

    public function render(): View
    {
        return view('capell-layout::livewire.widgets-table-select');
    }

    protected function getTableQuery(): Builder
    {
        /* @var class-string<\Capell\Layout\Models\Widget> $model */
        $model = CapellCore::getModel(ModelEnum::Widget->name);

        return $model::with([
            'translations.language',
            'type',
        ]);
    }
}
