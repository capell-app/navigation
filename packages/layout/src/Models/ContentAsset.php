<?php

declare(strict_types=1);

namespace Capell\Layout\Models;

use Capell\Admin\Concerns\HasResources;
use Capell\Core\Contracts\PageCacheable;
use Capell\Core\Enums\TypeEnum;
use Capell\Core\Models\Concerns\HasPageCache;
use Capell\Layout\Database\Factories\ContentAssetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Wildside\Userstamps\Userstamps;

class ContentAsset extends Model implements PageCacheable
{
    /** @use HasFactory<ContentAssetFactory> */
    use HasFactory;

    use HasPageCache;
    use HasResources;
    use Userstamps;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'content_id',
        'order',
        'asset_id',
        'asset_type',
    ];

    protected static string $factory = ContentAssetFactory::class;

    public static function getTypes(): array
    {
        return TypeEnum::getResourceTypes();
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function asset(): MorphTo
    {
        return $this->morphTo('asset', 'asset_type', 'asset_id', 'uuid');
    }

    public function getAssetKeyAttribute(): string
    {
        return $this->asset_type.'.'.$this->asset_id;
    }
}
