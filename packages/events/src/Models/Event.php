<?php

declare(strict_types=1);

namespace Capell\Events\Models;

use Bkwld\Cloner\Cloneable;
use Capell\Core\Concerns\HasCapellMedia;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Contracts\PageCacheable;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Enums\PageOrderEnum;
use Capell\Core\Models\Concerns\CloneableExcept;
use Capell\Core\Models\Concerns\HasAssets;
use Capell\Core\Models\Concerns\HasMetaData;
use Capell\Core\Models\Concerns\HasMorphModelRelations;
use Capell\Core\Models\Concerns\HasPageOrdering;
use Capell\Core\Models\Concerns\HasPublishDates;
use Capell\Core\Models\Concerns\HasTranslations;
use Capell\Core\Models\Concerns\HasType;
use Capell\Core\Models\Concerns\HasTypes;
use Capell\Core\Models\Concerns\HasUserstamps;
use Capell\Core\Models\Contracts\Publishable;
use Capell\Core\Models\Contracts\Translatable;
use Capell\Core\Models\Contracts\Typeable;
use Capell\Core\Models\Contracts\Userstampable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Events\Database\Factories\EventFactory;
use Capell\Events\Enums\EventOccurrenceStatusEnum;
use Capell\Events\Enums\EventPageTypeEnum;
use Capell\Events\Observers\EventObserver;
use Capell\Workspaces\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

#[ObservedBy(EventObserver::class)]
class Event extends Model implements HasMedia, Pageable, PageCacheable, Publishable, Translatable, Typeable, Userstampable
{
    use BelongsToWorkspace;
    use Cloneable;
    use CloneableExcept;
    use HasAssets;
    use HasCapellMedia;

    /** @use HasFactory<EventFactory> */
    use HasFactory;

    use HasJsonRelationships;
    use HasMetaData;
    use HasMorphModelRelations;
    use HasPageOrdering;
    use HasPublishDates;
    use HasTranslations;
    use HasType;
    use HasTypes;
    use HasUserstamps;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'events';

    /** @var array<string> */
    protected $fillable = [
        'layout_id',
        'meta',
        'name',
        'order',
        'uuid',
        'visible_from',
        'visible_until',
        'site_id',
        'type_id',
    ];

    protected array $clone_exempt_attributes = [
        'hidden',
    ];

    protected static string $factory = EventFactory::class;

    public static function getDefaultType(?string $group): ?Type
    {
        return Type::query()
            ->pageType()
            ->when($group !== null, fn (Builder $query): Builder => $query->adminResource($group))
            ->where('key', EventPageTypeEnum::Event->value)
            ->ordered()
            ->first();
    }

    public static function hasPageHierarchy(): bool
    {
        return false;
    }

    public static function defaultOrdering(): PageOrderEnum
    {
        return PageOrderEnum::Latest;
    }

    public function shouldLogVisit(): bool
    {
        return (bool) ($this->type?->meta['disable_visit_logs'] ?? true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('event')
            ->logAll()
            ->logExcept([
                'updated_at',
                'created_at',
                'deleted_at',
                'workspace_id',
                'shadowed_by_workspace_id',
                'created_by',
                'updated_by',
                'deleted_by',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollectionEnum::Image->value)->singleFile();
    }

    public function getParentUrl(Language $language, bool $fullUrl = false): string
    {
        $baseUrl = $fullUrl ? $this->site->getSiteDomainUrl($language) : '';

        return $baseUrl . '/events';
    }

    public function layout(): BelongsTo
    {
        return $this->belongsTo(Layout::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /** @return MorphOne<PageUrl, self> */
    public function pageUrl(): MorphOne
    {
        return $this->morphOne(PageUrl::class, 'pageable')->withDefault(['site_id' => $this->site_id]);
    }

    /** @return MorphMany<PageUrl, self> */
    public function pageUrls(): MorphMany
    {
        $model = $this->morphMany(PageUrl::class, 'pageable');

        if (method_exists($model, 'chaperone')) {
            $model->chaperone('pageable');
        }

        return $model;
    }

    public function image(): MorphOne
    {
        return $this->morphOneMedia(MediaCollectionEnum::Image->value);
    }

    /** @return MorphMany<Event, self> */
    public function canonicalPages(): MorphMany
    {
        return $this->morphMany(
            self::class,
            'canonical_pageable',
            'meta->canonical_pageable_type',
            'meta->canonical_pageable_id',
        );
    }

    public function canonicalPage(): MorphTo
    {
        return $this->morphTo(type: 'meta->canonical_pageable_type', id: 'meta->canonical_pageable_id');
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(EventOccurrence::class);
    }

    public function nextOccurrence(): HasOne
    {
        return $this->hasOne(EventOccurrence::class)
            ->where('starts_at', '>=', now())
            ->where('is_cancelled', false)
            ->where('status', '!=', EventOccurrenceStatusEnum::Cancelled->value)
            ->ofMany('starts_at', 'min');
    }

    protected static function booted(): void
    {
        static::creating(function (self $event): void {
            if ($event->uuid === null || $event->uuid === '') {
                $event->uuid = Str::uuid()->toString();
            }
        });
    }

    /** @return array<string, mixed>|null */
    protected function getUrlParamsAttribute(): ?array
    {
        return $this->type->meta['url_params'] ?? null;
    }

    protected function casts(): array
    {
        return [
            'meta' => 'json',
            'visible_from' => 'datetime',
            'visible_until' => 'datetime',
        ];
    }
}
