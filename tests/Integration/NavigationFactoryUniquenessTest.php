<?php

declare(strict_types=1);

use Capell\Core\Models\Site;
use Capell\Navigation\Models\Navigation;
use Faker\Factory as FakerFactory;
use Faker\Generator;

afterEach(function (): void {
    app()->instance(Generator::class, $this->originalFaker);
});

beforeEach(function (): void {
    $this->originalFaker = resolve(Generator::class);
});

it('creates two hundred navigations on one site under any seed without a key collision', function (int $seed): void {
    $generator = FakerFactory::create(config()->string('app.faker_locale', 'en_US'));
    $generator->seed($seed);
    app()->instance(Generator::class, $generator);
    $site = Site::factory()->create();

    Navigation::factory()->count(200)->create(['site_id' => $site->id, 'language_id' => null]);

    expect(Navigation::query()->where('site_id', $site->id)->count())->toBe(200)
        ->and(Navigation::query()->where('site_id', $site->id)->distinct()->count('key'))->toBe(200);
})->with([1, 2, 20261114]);
