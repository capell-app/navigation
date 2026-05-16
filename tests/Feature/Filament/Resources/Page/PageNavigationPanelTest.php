<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Pages\PageResource;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

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
