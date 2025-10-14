<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Page;

use Capell\Admin\Enums\TinyEditorProfile;
use Capell\Admin\Filament\Components\Forms\ContentEditor;
use Capell\Admin\Filament\Components\Forms\Editor\ContentBuilder;
use Capell\Admin\Filament\Components\Forms\Editor\RichEditor;
use Capell\Admin\Filament\Components\Forms\Editor\TinyEditor;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageTranslation;
use Capell\Layout\Models\Widget;
use Filament\Schemas\Components\Group;
use Illuminate\Database\Eloquent\Builder;

class HeroEditor extends Group
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->statePath('meta')
            ->visible(
                function (null|PageTranslation|Page $record): bool {
                    $layoutId = $this->getRootContainer()->getRawState()['layout_id'] ?? null;

                    if ($record !== null) {
                        $layoutId = $record instanceof PageTranslation
                            ? $record->page->layout_id
                            : $record->layout_id;
                    }

                    if (! $layoutId) {
                        return false;
                    }

                    /** @var Layout $layout */
                    $layout = $this->getLayout((int) $layoutId);

                    if (! in_array('hero', $layout->widgets, true)) {
                        return false;
                    }

                    $heroWidget = Widget::firstWhere('key', 'hero');

                    return ! $heroWidget->assets()
                        ->where(
                            fn (Builder $query): Builder => $query
                                ->where('page_id', $record?->id ?? 0)
                                ->orWhereNull('page_id')
                        )
                        ->exists();
                }
            )
            ->schema([
                ContentEditor::make('hero')
                    ->label(__('capell-layout::form.hero'))
                    ->hint(__('capell-layout::generic.hero_info'))
                    ->tap(
                        fn (ContentBuilder|RichEditor|TinyEditor $component): ContentBuilder|RichEditor|TinyEditor => $component instanceof TinyEditor
                            ? $component->profile(TinyEditorProfile::Simple->value)
                            : $component
                    ),
            ]);
    }

    protected function getLayout(int $layoutId): ?Layout
    {
        return once(fn (): ?Layout => CapellCore::getModel(ModelEnum::Layout)::find($layoutId));
    }
}
