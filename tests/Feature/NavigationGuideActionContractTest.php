<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Filament\Resources\Navigations\Pages\EditNavigation;
use Capell\Navigation\Models\Navigation;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Dom\HTMLDocument;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Str;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)->group('navigation');

it('renders and performs the documented default navigation nesting actions', function (): void {
    $exceptions = json_decode((string) file_get_contents(dirname(__DIR__, 4) . '/scripts/admin-guide-label-exceptions.json'), true, flags: JSON_THROW_ON_ERROR);
    if (! is_array($exceptions) || ! is_array($exceptions['packages/navigation/docs/admin-guide.md'] ?? null)) {
        throw new UnexpectedValueException('Navigation guide label exceptions must be an object.');
    }

    expect(array_keys($exceptions))->toBe(['packages/navigation/docs/admin-guide.md']);
    $labels = array_keys($exceptions['packages/navigation/docs/admin-guide.md'] ?? []);
    expect($labels)->toBe(['Indent', 'Dedent']);

    Language::factory()->default()->create();
    test()->actingAsAdmin();
    $first = (string) Str::uuid();
    $second = (string) Str::uuid();
    $child = (string) Str::uuid();
    $item = fn (string $label): array => [
        'type' => NavigationItemType::Link,
        'label' => $label,
        'data' => ['url' => 'https://example.com'],
    ];
    $navigation = Navigation::factory()->items([
        $first => [...$item('First'), 'children' => [$child => $item('Child')]],
        $second => $item('Second'),
    ])->create();

    $component = livewire(EditNavigation::class, ['record' => $navigation->getRouteKey()])->assertSuccessful();
    $dom = HTMLDocument::createFromString($component->html(), LIBXML_NOERROR, 'UTF-8');
    $assertActionButton = function (HTMLDocument $document, string $label, string $action, string $key): void {
        $matchingButtons = [];
        foreach ($document->querySelectorAll('button[aria-label="' . $label . '"]') as $button) {
            if (str_contains($button->getAttribute('wire:click') ?? '', $key)) {
                $matchingButtons[] = $button;
            }
        }

        expect($matchingButtons)->toHaveCount(1);
        expect($matchingButtons[0]->getAttribute('wire:click'))->toContain("mountAction('" . $action . "'");
    };
    $assertActionButton($dom, 'Indent', 'indent', $second);

    $component->callAction(TestAction::make('indent')->schemaComponent('items', schema: 'form')
        ->arguments(['cachedRecordKey' => $second, 'statePath' => 'data.items.' . $second]))
        ->assertHasNoFormErrors();
    expect($component->get('data.items.' . $first . '.children'))->toHaveKey($second);
    $component->call('save')->assertHasNoFormErrors();
    expect($navigation->refresh()->items[$first]->children->toCollection()->has($second))->toBeTrue();

    $component = livewire(EditNavigation::class, ['record' => $navigation->getRouteKey()])->assertSuccessful();
    $dom = HTMLDocument::createFromString($component->html(), LIBXML_NOERROR, 'UTF-8');
    $assertActionButton($dom, 'Dedent', 'dedent', $second);
    $component->callAction(TestAction::make('dedent')->schemaComponent('items', schema: 'form')
        ->arguments(['cachedRecordKey' => $second, 'statePath' => 'data.items.' . $first . '.children.' . $second]))
        ->assertHasNoFormErrors()
        ->call('save')
        ->assertHasNoFormErrors();
    expect($navigation->refresh()->items)->toHaveKey($second);
});
