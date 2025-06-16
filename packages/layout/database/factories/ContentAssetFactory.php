<?php

declare(strict_types=1);

namespace Capell\Layout\Database\Factories;

use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\ContentAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentAsset>
 */
class ContentAssetFactory extends Factory
{
    protected $model = ContentAsset::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['content', 'page', 'media']);

        return [
            'content_id' => Content::factory(),
            'order' => $this->faker->numberBetween(1, 10),
            'asset_type' => $type,
            'asset_id' => fn ($state): string => (match ($type) {
                'content' => Content::factory()->create()->uuid,
                'page' => Page::factory()->create()->uuid,
                'media' => Media::factory()->create()->uuid,
            }),
        ];
    }

    public function media(array $state = []): static
    {
        return $this->state([
            'asset_type' => 'media',
            'asset_id' => Media::factory($state)->create()->uuid,
        ]);
    }

    public function page(array $state = []): static
    {
        return $this->state([
            'asset_type' => 'page',
            'asset_id' => Page::factory($state)->create()->uuid,
        ]);
    }

    public function content(array $state = []): static
    {
        return $this->state([
            'asset_type' => 'content',
            'asset_id' => Content::factory($state)->create()->uuid,
        ]);
    }
}
