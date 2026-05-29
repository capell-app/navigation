<?php

declare(strict_types=1);

use Capell\Navigation\Health\NavigationHealthCheck;
use Capell\Navigation\Support\Registry\NavigableRegistry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

it('resolves registered navigable models and exposes health compatibility', function (): void {
    $model = new class extends Model
    {
        /** @use HasFactory<Factory<static>> */
        use HasFactory;

        public function getKey(): int
        {
            return 42;
        }

        public function getNavigationLabel(): string
        {
            return 'Documentation';
        }

        public function getNavigationUrl(): string
        {
            return '/docs';
        }
    };

    NavigableRegistry::register('docs', static fn (int $id): Model => $model);

    $result = NavigableRegistry::resolve('docs', 42);

    expect(NavigationHealthCheck::compatibleCapellApiVersion())->toBe('^4.0')
        ->and(NavigableRegistry::has('docs'))->toBeTrue()
        ->and(NavigableRegistry::has('missing'))->toBeFalse()
        ->and(NavigableRegistry::resolve('missing', 42))->toBeNull()
        ->and($result?->label)->toBe('Documentation')
        ->and($result?->url)->toBe('/docs');
});

it('falls back to model keys when navigable methods are absent', function (): void {
    $model = new class extends Model
    {
        /** @use HasFactory<Factory<static>> */
        use HasFactory;

        public function getKey(): int
        {
            return 77;
        }
    };

    NavigableRegistry::register('plain', static fn (int $id): Model => $model);

    $result = NavigableRegistry::resolve('plain', 77);

    expect($result?->label)->toBe('77')
        ->and($result?->url)->toBe('');
});
