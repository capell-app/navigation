<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Support\Creator\BlueprintCreator;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Models\Navigation;
use Illuminate\Testing\PendingCommand;

use function Pest\Laravel\artisan;

it('runs setup and demo commands for filtered sites and languages', function (): void {
    $language = Language::factory()->english()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations($language)
        ->create(['name' => 'Command Navigation Site']);

    $blueprintCreator = resolve(BlueprintCreator::class);
    $navigationType = $blueprintCreator->createNavigationType();
    $homePageType = $blueprintCreator->homePageType();

    $navigationType->update([
        'default' => true,
        'status' => true,
    ]);
    $homePageType->update([
        'status' => true,
    ]);

    Page::factory()
        ->site($site)
        ->type($homePageType)
        ->withTranslations(capell_test_collect([$language]), [
            'title' => 'Home',
            'label' => 'Home',
        ])
        ->create([
            'name' => 'Home',
            'visible_from' => now()->subDay(),
        ]);

    navigationCommand('capell:navigation-setup', ['--sites' => 'Command Navigation Site'])
        ->assertSuccessful()
        ->expectsOutputToContain('Navigation setup complete.');

    navigationCommand('capell:navigation-demo', [
        '--sites' => 'Command Navigation Site',
        '--languages' => 'en',
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('Navigation demo data set up successfully.');

    expect(Navigation::query()
        ->where('site_id', $site->getKey())
        ->whereIn('key', [
            NavigationHandle::Main->value,
            NavigationHandle::Footer->value,
            NavigationHandle::SubFooter->value,
        ])
        ->count())->toBeGreaterThanOrEqual(3);
});

it('skips command navigation generation when a filtered site has no home page', function (): void {
    $language = Language::factory()->english()->create();
    Site::factory()
        ->language($language)
        ->withTranslations($language)
        ->create(['name' => 'Navigation Without Home']);

    navigationCommand('capell:navigation-setup', ['--sites' => 'Navigation Without Home'])
        ->assertSuccessful()
        ->expectsOutputToContain('Skipping site "Navigation Without Home": no home page found.');

    navigationCommand('capell:navigation-demo', [
        '--sites' => 'Navigation Without Home',
        '--languages' => 'en',
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('Skipping site "Navigation Without Home": no home page found.');
});

/**
 * @param  array<string, mixed>  $parameters
 */
function navigationCommand(string $command, array $parameters = []): PendingCommand
{
    $pendingCommand = artisan($command, $parameters);

    throw_unless($pendingCommand instanceof PendingCommand, RuntimeException::class, 'Expected navigation command helper to return a pending command.');

    return $pendingCommand;
}
