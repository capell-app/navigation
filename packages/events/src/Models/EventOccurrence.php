<?php

declare(strict_types=1);

namespace Capell\Events\Models;

use Capell\Core\Models\Site;
use Capell\Events\Database\Factories\EventOccurrenceFactory;
use Capell\Events\Enums\EventOccurrenceStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventOccurrence extends Model
{
    /** @use HasFactory<EventOccurrenceFactory> */
    use HasFactory;

    protected $table = 'event_occurrences';

    /** @var array<string> */
    protected $fillable = [
        'event_id',
        'site_id',
        'starts_at',
        'ends_at',
        'timezone',
        'status',
        'location',
        'booking',
        'schema',
        'is_cancelled',
    ];

    protected static string $factory = EventOccurrenceFactory::class;

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    protected function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now());
    }

    protected function scopeBetween(Builder $query, mixed $startsAt, mixed $endsAt): Builder
    {
        return $query->whereBetween('starts_at', [$startsAt, $endsAt]);
    }

    protected function scopeNotCancelled(Builder $query): Builder
    {
        return $query->where('is_cancelled', false)
            ->where('status', '!=', EventOccurrenceStatusEnum::Cancelled->value);
    }

    protected function scopePublished(Builder $query): Builder
    {
        return $query->whereHas('event', function (Builder $query): void {
            $query->where(function (Builder $query): void {
                $query->whereNull('visible_from')
                    ->orWhere('visible_from', '<=', now());
            })->where(function (Builder $query): void {
                $query->whereNull('visible_until')
                    ->orWhere('visible_until', '>=', now());
            });
        });
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'location' => 'array',
            'booking' => 'array',
            'schema' => 'array',
            'is_cancelled' => 'boolean',
        ];
    }
}
