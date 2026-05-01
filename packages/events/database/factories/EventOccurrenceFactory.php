<?php

declare(strict_types=1);

namespace Capell\Events\Database\Factories;

use Capell\Core\Models\Site;
use Capell\Events\Enums\EventOccurrenceStatusEnum;
use Capell\Events\Models\Event;
use Capell\Events\Models\EventOccurrence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventOccurrence>
 */
class EventOccurrenceFactory extends Factory
{
    protected $model = EventOccurrence::class;

    public function definition(): array
    {
        $startsAt = $this->faker->dateTimeBetween('+1 week', '+2 months');

        return [
            'event_id' => Event::factory(),
            'site_id' => Site::factory()->withTranslations(),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+1 hour'),
            'timezone' => 'Europe/London',
            'status' => EventOccurrenceStatusEnum::Scheduled->value,
            'location' => [],
            'booking' => [],
            'schema' => [],
            'is_cancelled' => false,
        ];
    }

    public function event(Event $event): static
    {
        return $this->set('event_id', $event->id);
    }

    public function site(Site $site): static
    {
        return $this->set('site_id', $site->id);
    }
}
