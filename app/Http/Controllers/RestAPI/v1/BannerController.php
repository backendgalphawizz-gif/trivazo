<?php

namespace App\Http\Controllers\RestAPI\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BannerController extends Controller
{
    private function normalizeBannerType(?string $bannerType): string
    {
        return strtolower(trim((string) $bannerType));
    }

    private function getBannerValue(mixed $banner, string $key, mixed $default = null): mixed
    {
        $sources = [
            $banner,
            is_object($banner) && method_exists($banner, 'toArray') ? $banner->toArray() : null,
            is_object($banner) && method_exists($banner, 'getAttributes') ? $banner->getAttributes() : null,
            is_array($banner) ? ($banner['original'] ?? null) : data_get($banner, 'original'),
            is_array($banner) ? ($banner['attributes'] ?? null) : data_get($banner, 'attributes'),
        ];

        foreach ($sources as $source) {
            if ($source === null) {
                continue;
            }

            $value = data_get($source, $key);

            if ($value !== null) {
                return $value;
            }
        }

        return $default;
    }

    private function extractUrlFromValue(mixed $value): ?string
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

    private function buildBannerMediaUrl(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        return asset('storage/app/public/banner/' . basename(parse_url($value, PHP_URL_PATH) ?: $value));
    }

    private function resolveBannerCategory(mixed $banner): string
    {
        $bannerCategory = strtolower((string) $this->getBannerValue($banner, 'banner_category', ''));

        if (in_array($bannerCategory, ['normal', 'food'], true)) {
            return $bannerCategory;
        }

        return str_contains(strtolower((string) $this->getBannerValue($banner, 'banner_type', '')), 'food') ? 'food' : 'normal';
    }

    private function getRequestedType(Request $request): string
    {
        return strtolower((string) (
            $request->query('type')
            ?? $request->query('banner_category')
            ?? $request->query('main_type')
            ?? $request->input('type')
            ?? $request->input('banner_category')
            ?? $request->input('main_type')
            ?? ''
        ));
    }

    private function getImageBannerBucket(?string $bannerType): string
    {
        return match ($this->normalizeBannerType($bannerType)) {
            'main banner' => 'main_banner',
            'middle banner' => 'middle_banners',
            'bottom banner' => 'bottom_banner',
            default => 'other_banners',
        };
    }

    private function formatBanner(mixed $banner): array
    {
        $bannerCategory = $this->resolveBannerCategory($banner);
        $bannerType = (string) $this->getBannerValue($banner, 'banner_type');
        $photoUrl = $this->extractUrlFromValue($this->getBannerValue($banner, 'photo_full_url'));
        $videoUrl = $this->extractUrlFromValue($this->getBannerValue($banner, 'video_full_url'));
        $photo = $this->getBannerValue($banner, 'photo');
        $video = $this->getBannerValue($banner, 'video');
        $hasVideo = $bannerCategory === 'normal' && $this->normalizeBannerType($bannerType) === 'main banner' && ($video || $videoUrl);
        $mediaType = $hasVideo ? 'video' : 'image';

        return [
            'id' => $this->getBannerValue($banner, 'id'),
            'banner_type' => $bannerType,
            'banner_category' => $bannerCategory,
            'media_type' => $mediaType,
            'image' => $photoUrl ?: $this->buildBannerMediaUrl($photo),
            'video' => $hasVideo ? ($videoUrl ?: $this->buildBannerMediaUrl($video)) : null,
            'url' => $this->getBannerValue($banner, 'url'),
            'title' => $this->getBannerValue($banner, 'title'),
            'sub_title' => $this->getBannerValue($banner, 'sub_title'),
            'button_text' => $this->getBannerValue($banner, 'button_text'),
            'background_color' => $this->getBannerValue($banner, 'background_color'),
        ];
    }

    private function formatBannerMedia(mixed $banner): array
    {
        $formattedBanner = $this->formatBanner($banner);

        return [
            'banner_type' => $formattedBanner['banner_type'],
            'media_type' => $formattedBanner['media_type'],
            'image' => $formattedBanner['image'],
            'video' => $formattedBanner['video'],
        ];
    }

    public function getBannerList(Request $request): JsonResponse
    {
        $requestedType = $this->getRequestedType($request);

        if ($requestedType !== '' && !in_array($requestedType, ['normal', 'food'], true)) {
            return response()->json([
                'message' => 'The type field must be either normal or food.',
            ], 422);
        }

        $banners = DB::table('banners')
            ->select([
                'id',
                'photo',
                'media_type',
                'video',
                'banner_type',
                'banner_category',
                'published',
                'url',
                'title',
                'sub_title',
                'button_text',
                'background_color',
            ])
            ->where('published', 1)
            ->when($requestedType !== '', function ($query) use ($requestedType) {
                $query->where('banner_category', $requestedType);
            })
            ->orderByDesc('id')
            ->get();

        $bannerData = [
            'main_banner' => [
                'image' => [],
                'video' => null,
            ],
            'middle_banners' => [],
            'bottom_banner' => null,
        ];

        foreach ($banners as $banner) {
            $formattedBanner = $this->formatBannerMedia($banner);

            if ($formattedBanner['media_type'] === 'video') {
                if ($bannerData['main_banner']['video'] === null) {
                    $bannerData['main_banner']['video'] = $formattedBanner['video'];
                }

                continue;
            }

            $bucket = $this->getImageBannerBucket($formattedBanner['banner_type']);

            if ($bucket === 'main_banner') {
                $bannerData['main_banner']['image'][] = $formattedBanner['image'];
                continue;
            }

            if ($bucket === 'bottom_banner') {
                if ($bannerData['bottom_banner'] === null) {
                    $bannerData[$bucket] = $formattedBanner['image'];
                }

                continue;
            }

            if (!array_key_exists($bucket, $bannerData)) {
                $bannerData[$bucket] = [];
            }

            $bannerData[$bucket][] = $formattedBanner['image'];
        }

        return response()->json($bannerData, 200);
    }
}
