<?php

declare(strict_types=1);

use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Navigation\Enums\NavigationCacheEnum;
use Capell\Navigation\Support\NavigationNamesResolver;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Factory;

test('resolves navigation names for site and languages', function (): void {
    [$siteId, $typeId, $languageId] = navigationResolverFixture();
    $secondLanguage = Language::factory()->french()->create();

    $nav1Id = $this->connection()->table('navigations')->insertGetId([
        'site_id' => $siteId,
        'language_id' => $languageId,
        'blueprint_id' => $typeId,
        'key' => 'main-menu',
        'name' => 'Main Menu',
        'items' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $nav2Id = $this->connection()->table('navigations')->insertGetId([
        'site_id' => $siteId,
        'language_id' => $secondLanguage->getKey(),
        'blueprint_id' => $typeId,
        'key' => 'footer-menu',
        'name' => 'Footer Menu',
        'items' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    /** @var Repository $cache */
    $cache = resolve(Factory::class)->store();
    $resolver = new NavigationNamesResolver($cache);
    $result = $resolver->resolve($siteId, [$languageId, (int) $secondLanguage->getKey()]);

    $this->assertArrayHasKey($nav1Id, $result);
    $this->assertArrayHasKey($nav2Id, $result);
    $this->assertEquals('Main Menu', $result[$nav1Id]);
    $this->assertEquals('Footer Menu', $result[$nav2Id]);
});

test('caches result with correct key', function (): void {
    [$siteId, $typeId, $languageId] = navigationResolverFixture();

    $this->connection()->table('navigations')->insert([
        'site_id' => $siteId,
        'language_id' => $languageId,
        'blueprint_id' => $typeId,
        'key' => 'cache-test',
        'name' => 'Cache Test',
        'items' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    /** @var Repository $cache */
    $cache = resolve(Factory::class)->store();
    $resolver = new NavigationNamesResolver($cache);
    $cacheKey = NavigationCacheEnum::navigationNamesKey($siteId, [$languageId]);

    $resolver->resolve($siteId, [$languageId]);

    $this->assertNotNull($cache->get($cacheKey));
});

test('includes navigations with null site id', function (): void {
    [$siteId, $typeId, $languageId] = navigationResolverFixture();

    $this->connection()->table('navigations')->insert([
        'site_id' => null,
        'language_id' => $languageId,
        'blueprint_id' => $typeId,
        'key' => 'global',
        'name' => 'Global Menu',
        'items' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->connection()->table('navigations')->insert([
        'site_id' => $siteId,
        'language_id' => $languageId,
        'blueprint_id' => $typeId,
        'key' => 'site',
        'name' => 'Site Menu',
        'items' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    /** @var Repository $cache */
    $cache = resolve(Factory::class)->store();
    $resolver = new NavigationNamesResolver($cache);
    $result = $resolver->resolve($siteId, [$languageId]);

    $this->assertCount(2, $result);
});

test('handles string site id', function (): void {
    [$siteId, $typeId, $languageId] = navigationResolverFixture();

    $this->connection()->table('navigations')->insert([
        'site_id' => $siteId,
        'language_id' => $languageId,
        'blueprint_id' => $typeId,
        'key' => 'string-test',
        'name' => 'Menu',
        'items' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    /** @var Repository $cache */
    $cache = resolve(Factory::class)->store();
    $resolver = new NavigationNamesResolver($cache);
    $result = $resolver->resolve((string) $siteId, [$languageId]);

    $this->assertCount(1, $result);
});

test('returns id name mapping', function (): void {
    [$siteId, $typeId, $languageId] = navigationResolverFixture();

    $navId = $this->connection()->table('navigations')->insertGetId([
        'site_id' => $siteId,
        'language_id' => $languageId,
        'blueprint_id' => $typeId,
        'key' => 'test',
        'name' => 'Test Menu',
        'items' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    /** @var Repository $cache */
    $cache = resolve(Factory::class)->store();
    $resolver = new NavigationNamesResolver($cache);
    $result = $resolver->resolve($siteId, [$languageId]);

    $this->assertIsArray($result);
    $this->assertArrayHasKey($navId, $result);
    $this->assertEquals('Test Menu', $result[$navId]);
});

/**
 * @return array{int, int, int}
 */
function navigationResolverFixture(): array
{
    $language = Language::factory()->english()->create();
    $site = Site::factory()->language($language)->create();
    $type = Blueprint::query()->create([
        'key' => 'navigation',
        'name' => 'Navigation',
        'type' => 'navigation',
    ]);

    return [(int) $site->getKey(), (int) $type->getKey(), (int) $language->getKey()];
}
