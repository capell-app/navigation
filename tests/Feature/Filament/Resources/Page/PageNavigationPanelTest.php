<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Pages\PageResource;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Filament\Components\Forms\NavigationSelect;
use Capell\Navigation\Filament\Components\Forms\Page\Tab\NavigationTab;
use Capell\Navigation\Models\Navigation;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Component;

use function Pest\Laravel\get;

uses(CreatesAdminUser::class)
    ->group('navigation');

test('page edit renders linked navigations with language flags without requiring flag blade components', function (): void {
    test()->actingAsAdmin();

    $language = Language::factory()->create([
        'name' => 'French',
        'code' => 'fr',
        'flag' => 'fr',
    ]);

    $site = Site::factory()->language($language)->withTranslations()->create();
    $page = Page::factory()->site($site)->create();

    $navigation = Navigation::factory()
        ->site($site)
        ->language($language)
        ->items([
            [
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'pageable_id' => $page->getKey(),
                    'pageable_type' => $page->getMorphClass(),
                ],
                'children' => [],
            ],
        ])
        ->create([
            'name' => 'Main menu',
        ]);

    get(PageResource::getUrl('edit', ['record' => $page]))
        ->assertOk()
        ->assertSee('Main menu')
        ->assertSee('FR');
});

test('page navigation authoring components require a page record', function (): void {
    $page = Page::factory()->createOne();
    $tab = NavigationTab::make();
    $schema = Schema::make()->model(Page::class)->components([$tab]);
    $schema->getComponents(withHidden: true);

    expect($tab->isVisible())->toBeFalse();

    $schema->record($page);
    expect($tab->isVisible())->toBeTrue();
});

/**
 * @return array{NavigationSelect, Schema}
 */
function pageNavigationReferenceSelect(Page $page): array
{
    $host = new class extends Component implements HasSchemas
    {
        use InteractsWithSchemas;

        /** @var array<string, mixed> */
        public array $data = [];
    };
    $schema = Schema::make($host)->model(Page::class)->record($page)->statePath('data')->operation('edit')->components([NavigationTab::make()]);
    $schema->fill();

    $select = collect($schema->getFlatComponents(withHidden: true))
        ->first(fn (mixed $component): bool => $component instanceof NavigationSelect);

    assert($select instanceof NavigationSelect);

    return [$select, $schema];
}

test('page navigation selector reflects only the menus that already contain the page and is read only', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $page = Page::factory()->site($site)->create();
    $otherPage = Page::factory()->site($site)->create();

    $containing = Navigation::factory()
        ->site($site)
        ->items([[
            'type' => NavigationItemType::Page->value,
            'data' => ['pageable_id' => $page->getKey(), 'pageable_type' => $page->getMorphClass()],
            'children' => [],
        ]])
        ->create(['name' => 'Containing menu']);
    $unrelated = Navigation::factory()
        ->site($site)
        ->items([[
            'type' => NavigationItemType::Page->value,
            'data' => ['pageable_id' => $otherPage->getKey(), 'pageable_type' => $otherPage->getMorphClass()],
            'children' => [],
        ]])
        ->create(['name' => 'Unrelated menu']);

    [$select, $schema] = pageNavigationReferenceSelect($page);

    expect($select->isDisabled())->toBeTrue()
        ->and($select->getOptions())->toHaveCount(1)
        ->and(array_values($select->getOptions())[0])->toContain('Containing menu')->not->toContain('Unrelated menu')
        ->and($select->getState())->toBe([$containing->getKey()])
        ->and($select->getState())->not->toContain($unrelated->getKey())
        ->and($schema->getState())->not->toHaveKey('page_navigations');
});

test('page navigation selector describes existing references rather than promising to add the page', function (): void {
    $page = Page::factory()->createOne();

    [$select] = pageNavigationReferenceSelect($page);
    $helperComponents = $select->getChildSchema(NavigationSelect::BELOW_CONTENT_SCHEMA_KEY)?->getComponents() ?? [];
    $helper = collect($helperComponents)
        ->map(function (mixed $component): string {
            $content = $component instanceof Text ? $component->getContent() : null;

            return $content instanceof Htmlable ? $content->toHtml() : (string) $content;
        })
        ->implode(' ');

    expect($helper)->not->toBe('')
        ->and($helper)->not->toContain('Add page to Navigation')
        ->and($helper)->toBe(__('capell-navigation::generic.page_references_info'))
        ->and($select->getLabel())->toBe(__('capell-navigation::generic.page_references'))
        ->and($select->getPlaceholder())->toBe(__('capell-navigation::generic.page_references_empty'))
        ->and($select->getState())->toBe([]);
});
