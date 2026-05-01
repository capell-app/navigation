<?php

declare(strict_types=1);

use Capell\Core\Contracts\Pageable;
use Capell\Core\Contracts\PageCacheable;
use Capell\Core\Models\Contracts\Publishable;
use Capell\Core\Models\Contracts\Translatable;
use Capell\Core\Models\Contracts\Typeable;
use Capell\Core\Models\Contracts\Userstampable;
use Capell\Core\Models\Site;
use Capell\Events\Models\Event;
use Spatie\MediaLibrary\HasMedia;

it('implements Capell page contracts', function (): void {
    $event = new Event;

    expect($event)->toBeInstanceOf(Pageable::class)
        ->and($event)->toBeInstanceOf(PageCacheable::class)
        ->and($event)->toBeInstanceOf(Publishable::class)
        ->and($event)->toBeInstanceOf(Translatable::class)
        ->and($event)->toBeInstanceOf(Typeable::class)
        ->and($event)->toBeInstanceOf(Userstampable::class)
        ->and($event)->toBeInstanceOf(HasMedia::class);
});

it('has event occurrences', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $event = Event::factory()->site($site)->create();

    $occurrence = $event->occurrences()->create([
        'site_id' => $site->id,
        'starts_at' => '2026-06-01 09:00:00',
        'ends_at' => '2026-06-01 10:00:00',
        'timezone' => 'Europe/London',
        'status' => 'scheduled',
        'location' => [],
        'booking' => [],
        'schema' => [],
        'is_cancelled' => false,
    ]);

    expect($event->occurrences()->first()?->is($occurrence))->toBeTrue();
});
