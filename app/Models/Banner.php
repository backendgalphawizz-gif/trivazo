<?php

namespace App\Models;

use App\Traits\StorageTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Class YourModel
 *
 * @property int $id Primary
 * @property string $photo
 * @property string $media_type
 * @property string $video
 * @property string $thumbnail
 * @property string $banner_type
 * @property string $theme
 * @property int $published
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $url
 * @property string $resource_type
 * @property int $resource_id
 * @property string $title
 * @property string $sub_title
 * @property string $button_text
 * @property string $background_color
 *
 * @package App\Models
 */
class Banner extends Model
{
    use StorageTrait;

    private function extractStorageUrl(string|array|null $value): ?string
    {
        if (is_string($value) || $value === null) {
            return $value;
        }

        foreach (['url', 'path', 'src', 'source', 'image_url', 'download_url', 'original_url'] as $key) {
            $candidate = data_get($value, $key);
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        $firstString = null;
        array_walk_recursive($value, function ($item) use (&$firstString) {
            if ($firstString === null && is_string($item) && $item !== '') {
                $firstString = $item;
            }
        });

        return $firstString;
    }

    private function normalizeBannerMediaName(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $path = parse_url($value, PHP_URL_PATH) ?: $value;

        return basename($path);
    }

    protected $casts = [
        'id' => 'integer',
        'published' => 'integer',
        'resource_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'photo',
        'media_type',
        'video',
        'thumbnail',
        'banner_type',
        'theme',
        'published',
        'url',
        'resource_type',
        'resource_id',
        'title',
        'sub_title',
        'button_text',
        'background_color',
    ];

    protected $appends = ['photo_full_url', 'video_full_url', 'thumbnail_full_url'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'resource_id');
    }

    public function getPhotoFullUrlAttribute(): string|null|array
    {
        $value = $this->normalizeBannerMediaName($this->photo);
        if (count($this->storage) > 0) {
            $storage = $this->storage->where('key', 'photo')->first();
        }
        return $this->storageLink('banner', $value, $storage['value'] ?? 'public');
    }

    public function getVideoFullUrlAttribute(): ?string
    {
        $value = $this->normalizeBannerMediaName($this->video);
        if (count($this->storage) > 0) {
            $storage = $this->storage->where('key', 'video')->first();
        }
        return $this->extractStorageUrl($this->storageLink('banner', $value, $storage['value'] ?? 'public'));
    }

    public function getThumbnailFullUrlAttribute(): ?string
    {
        $value = $this->normalizeBannerMediaName($this->thumbnail);
        if (count($this->storage) > 0) {
            $storage = $this->storage->where('key', 'thumbnail')->first();
        }
        return $this->extractStorageUrl($this->storageLink('banner', $value, $storage['value'] ?? 'public'));
    }

    protected static function boot(): void
    {
        parent::boot();
        static::saved(function ($model) {
            cacheRemoveByType(type: 'banners');

            $storage = config('filesystems.disks.default') ?? 'public';
            foreach (['photo', 'video', 'thumbnail'] as $file) {
                if ($model->isDirty($file)) {
                    $value = $storage;
                    DB::table('storages')->updateOrInsert([
                        'data_type' => get_class($model),
                        'data_id' => $model->id,
                        'key' => $file,
                    ], [
                        'value' => $value,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        static::deleted(function ($model) {
            cacheRemoveByType(type: 'banners');
        });
    }
}
