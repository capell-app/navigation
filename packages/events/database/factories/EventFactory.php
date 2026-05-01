<?php

declare(strict_types=1);

namespace Capell\Events\Database\Factories;

use Capell\Core\Database\Factories\Concerns\HasAdmin;
use Capell\Core\Database\Factories\Concerns\HasFactoryPublishDates;
use Capell\Core\Database\Factories\Concerns\HasMeta;
use Capell\Core\Database\Factories\Concerns\HasTranslations;
use Capell\Core\Enums\TypeEnum;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Events\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    use HasAdmin;
    use HasFactoryPublishDates;
    use HasMeta;
    use HasTranslations;

    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'name' => fn (): string => $this->faker->sentence(4),
            'layout_id' => fn (): int => Layout::factory()->create()->id,
            'type_id' => fn (): int => Type::query()->firstOrCreate([
                'key' => 'event',
                'type' => TypeEnum::Page,
            ], [
                'name' => 'Event',
            ])->id,
            'site_id' => Site::factory()->withTranslations(),
            'meta' => [],
            'created_at' => fn () => $this->faker->dateTimeBetween('-1 year', '-6 month'),
            'updated_at' => fn (array $attributes) => $this->faker->dateTimeBetween($attributes['created_at']),
        ];
    }

    public function layout(Layout $layout): static
    {
        return $this->set('layout_id', $layout->id);
    }

    public function site(int|Site $site): static
    {
        return $this->set('site_id', $site instanceof Site ? $site->id : $site);
    }

    public function type(Type $type): static
    {
        return $this->set('type_id', $type->id);
    }
}
