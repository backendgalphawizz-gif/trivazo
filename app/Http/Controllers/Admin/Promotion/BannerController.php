<?php

namespace App\Http\Controllers\Admin\Promotion;

use App\Contracts\Repositories\BannerRepositoryInterface;
use App\Contracts\Repositories\BrandRepositoryInterface;
use App\Contracts\Repositories\CategoryRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Contracts\Repositories\ShopRepositoryInterface;
use App\Enums\ViewPaths\Admin\Banner;
use App\Http\Controllers\BaseController;
use App\Services\BannerService;
use App\Traits\FileManagerTrait;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BannerController extends BaseController
{
    use FileManagerTrait {
        delete as deleteFile;
        update as updateFile;
    }

    private function getAllowedBannerTypes(): array
    {
        return [
            'Main Banner' => 'Main Banner',
            'Middle Banner' => 'Middle Banner',
            'Bottom Banner' => 'Bottom Banner',
        ];
    }

    private function normalizeBannerType(?string $bannerType): string
    {
        return Str::lower(trim((string) $bannerType));
    }

    private function isMainBannerType(?string $bannerType): bool
    {
        return $this->normalizeBannerType($bannerType) === 'main banner';
    }

    private function isFoodBannerType(?string $bannerType): bool
    {
        $bannerType = Str::lower((string) $bannerType);

        return $bannerType !== '' && str_contains($bannerType, 'food');
    }

    private function isFoodBannerSelection(Request $request, $banner = null): bool
    {
        $bannerCategory = Str::lower((string) $request->input('banner_category', data_get($banner, 'banner_category', '')));

        if ($bannerCategory !== '') {
            return $bannerCategory === 'food';
        }

        return $this->isFoodBannerType((string) $request->input('banner_type', data_get($banner, 'banner_type', '')));
    }

    private function hasAnotherVideoBanner(?int $excludeId = null): bool
    {
        $query = DB::table('banners')->where(function ($builder) {
            $builder->whereNotNull('video')->where('video', '!=', '')
                ->orWhere('media_type', 'video');
        });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    private function hasAnotherBannerType(string $bannerType, ?int $excludeId = null): bool
    {
        $query = DB::table('banners')
            ->whereRaw('LOWER(banner_type) = ?', [$this->normalizeBannerType($bannerType)]);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    private function allowsVideoBanner(Request $request, $banner = null): bool
    {
        $bannerType = (string) $request->input('banner_type', data_get($banner, 'banner_type', ''));

        return !$this->isFoodBannerSelection($request, $banner) && $this->isMainBannerType($bannerType);
    }

    private function validateBannerRestrictions(Request $request, $banner = null): ?RedirectResponse
    {
        $bannerType = (string) $request->input('banner_type', data_get($banner, 'banner_type', ''));
        $isFoodBanner = $this->isFoodBannerSelection($request, $banner);
        $requestedMediaType = $request->hasFile('video')
            ? 'video'
            : $request->input('media_type', data_get($banner, 'media_type', 'image'));

        if (!array_key_exists($bannerType, $this->getAllowedBannerTypes())) {
            Toastr::error('Please select a valid banner type.');
            return back()->withInput();
        }

        if ($this->normalizeBannerType($bannerType) === 'bottom banner' && $this->hasAnotherBannerType($bannerType, data_get($banner, 'id'))) {
            Toastr::error('Only one bottom banner is allowed.');
            return back()->withInput();
        }

        if ($isFoodBanner && !$this->isMainBannerType($bannerType)) {
            Toastr::error('Food banner category supports only Main Banner.');
            return back()->withInput();
        }

        if (!$this->allowsVideoBanner($request, $banner) && $requestedMediaType === 'video') {
            Toastr::error('Video is allowed only for normal main banner.');
            return back()->withInput();
        }

        if ($requestedMediaType === 'video' && $this->hasAnotherVideoBanner(data_get($banner, 'id'))) {
            Toastr::error('Only one video banner is allowed.');
            return back()->withInput();
        }

        return null;
    }

    private function deleteBannerMedia(?string $file, string $defaultDirectory = '/banner/'): void
    {
        if (!$file) {
            return;
        }

        $fileName = basename(parse_url($file, PHP_URL_PATH) ?: $file);

        $publicStoragePath = public_path('storage/banner/' . $fileName);
        $laravelStoragePath = storage_path('app/public/banner/' . $fileName);

        if (File::exists($publicStoragePath)) {
            File::delete($publicStoragePath);
        }

        if (File::exists($laravelStoragePath)) {
            File::delete($laravelStoragePath);
        }

        $filePath = str_contains($file, '/')
            ? '/' . ltrim($file, '/')
            : rtrim($defaultDirectory, '/') . '/' . ltrim($file, '/');

        $this->deleteFile(filePath: $filePath);
    }

    private function uploadBannerMedia(UploadedFile $file, string $directory): string
    {
        $directory = trim($directory, '/');
        $uploadPath = public_path($directory);
        $storagePath = storage_path('app/public/' . trim(str_replace('storage/', '', $directory), '/'));

        if (!File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true);
        }

        if (!File::exists($storagePath)) {
            File::makeDirectory($storagePath, 0755, true);
        }

        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->move($uploadPath, $fileName);

        $publicFilePath = $uploadPath . DIRECTORY_SEPARATOR . $fileName;
        $storageFilePath = $storagePath . DIRECTORY_SEPARATOR . $fileName;

        if (File::exists($publicFilePath) && !File::exists($storageFilePath)) {
            File::copy($publicFilePath, $storageFilePath);
        }

        return $fileName;
    }

    private function getBannerMediaValidationRules(bool $isUpdate = false): array
    {
        $rules = [
            'media_type' => 'nullable|in:image,video',
            'image' => 'nullable|mimes:jpg,jpeg,png,gif,bmp,webp|max:5120',
            'video' => 'nullable|mimes:mp4,webm,ogg,mov|max:512000',
        ];

        if (!$isUpdate) {
            $rules['image'] = 'nullable|mimes:jpg,jpeg,png,gif,bmp,webp|max:5120|required_without:video';
            $rules['video'] = 'nullable|mimes:mp4,webm,ogg,mov|max:512000|required_without:image';
        }

        return $rules;
    }

    private function processBannerMedia(Request $request, array $data, $banner = null): array
    {
        $currentMediaType = data_get($banner, 'media_type', data_get($banner, 'video') ? 'video' : 'image');
        $isFoodBanner = $this->isFoodBannerSelection($request, $banner);
        $allowsVideoBanner = $this->allowsVideoBanner($request, $banner);

        $data['banner_category'] = $isFoodBanner ? 'food' : 'normal';

        if ($allowsVideoBanner && $request->hasFile('video')) {
            $mediaType = 'video';
        } elseif ($request->hasFile('image')) {
            $mediaType = 'image';
        } else {
            $mediaType = $request->input('media_type', $currentMediaType ?: 'image');
        }

        if (!$allowsVideoBanner) {
            $mediaType = 'image';
        }

        $data['media_type'] = $mediaType;

        $data['resource_type'] = 'category';
        $data['resource_id'] = $request->category_id;

        if ($mediaType === 'video') {
            if ($request->hasFile('video')) {
                $this->deleteBannerMedia(file: data_get($banner, 'video'), defaultDirectory: '/storage/banner/');
                $data['video'] = $this->uploadBannerMedia($request->file('video'), 'storage/banner');
            } else {
                $data['video'] = data_get($banner, 'video');
            }

            if ($currentMediaType !== 'video' || $request->hasFile('video')) {
                $this->deleteBannerMedia(file: data_get($banner, 'photo'), defaultDirectory: '/storage/banner/');
            }

            $data['photo'] = null;
        } else {
            if ($request->hasFile('image')) {
                $this->deleteBannerMedia(file: data_get($banner, 'photo'), defaultDirectory: '/storage/banner/');
                $data['photo'] = $this->uploadBannerMedia($request->file('image'), 'storage/banner');
            } else {
                $data['photo'] = data_get($banner, 'photo', data_get($data, 'photo'));
            }

            if ($currentMediaType !== 'image' || $request->hasFile('image')) {
                $this->deleteBannerMedia(file: data_get($banner, 'video'), defaultDirectory: '/storage/banner/');
            }

            $data['video'] = null;
        }

        return $data;
    }

    public function __construct(
        private readonly BannerRepositoryInterface        $bannerRepo,
        private readonly CategoryRepositoryInterface      $categoryRepo,
        private readonly ShopRepositoryInterface          $shopRepo,
        private readonly BrandRepositoryInterface         $brandRepo,
        private readonly ProductRepositoryInterface       $productRepo,
        private readonly BannerService       $bannerService,
    )
    {
    }

    /**
     * @param Request|null $request
     * @param string|null $type
     * @return View Index function is the starting point of a controller
     * Index function is the starting point of a controller
     */
    public function index(Request|null $request, string $type = null): View
    {
        return $this->getListView($request);
    }

    public function getListView(Request $request): View
    {
        $bannerTypes = $this->getAllowedBannerTypes();
        $banners = $this->bannerRepo->getListWhereIn(
            orderBy: ['id'=>'desc'],
            searchValue: $request['searchValue'],
            filters: ['theme'=>theme_root_path()],
            whereInFilters: ['banner_type' => array_keys($bannerTypes)],
            dataLimit: getWebConfig(name: 'pagination_limit'),
        );

        $categories = $this->categoryRepo->getListWhere(filters: ['position'=>0], dataLimit: 'all');
        $shops = $this->shopRepo->getListWithScope(scope:'active', dataLimit: 'all');
        $brands = $this->brandRepo->getListWhere(dataLimit: 'all');
        $products = $this->productRepo->getListWithScope(scope:'active', dataLimit: 'all');
        return view(Banner::LIST[VIEW],  compact('banners', 'categories','shops', 'brands', 'products', 'bannerTypes'));
    }

    public function add(Request $request): RedirectResponse
    {
        $request->validate($this->getBannerMediaValidationRules());

        if ($redirectResponse = $this->validateBannerRestrictions($request)) {
            return $redirectResponse;
        }

        $data = $this->bannerService->getProcessedData(request: $request);
        $data = $this->processBannerMedia(request: $request, data: $data);
        $this->bannerRepo->add(data:$data);
        Toastr::success(translate('banner_added_successfully'));
        return redirect()->route('admin.banner.list');
    }

    public function getUpdateView($id): View
    {
        $bannerTypes = $this->getAllowedBannerTypes();
        $banner = $this->bannerRepo->getFirstWhere(params: ['id'=>$id]);
        $categories = $this->categoryRepo->getListWhere(filters: ['position'=>0], dataLimit: 'all');
        $shops = $this->shopRepo->getListWithScope(scope:'active', dataLimit: 'all');
        $brands = $this->brandRepo->getListWhere(dataLimit: 'all');
        $products = $this->productRepo->getListWithScope(scope:'active', dataLimit: 'all');
        return view(Banner::UPDATE[VIEW], compact('banner', 'categories','shops', 'brands', 'products', 'bannerTypes'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $banner = $this->bannerRepo->getFirstWhere(params: ['id'=>$id]);
        $request->validate($this->getBannerMediaValidationRules(isUpdate: true));

        if ($redirectResponse = $this->validateBannerRestrictions($request, $banner)) {
            return $redirectResponse;
        }

        $data = $this->bannerService->getProcessedData(request: $request, image: $banner['photo']);
        $data = $this->processBannerMedia(request: $request, data: $data, banner: $banner);
        $this->bannerRepo->update(id:$banner['id'], data:$data);
        Toastr::success(translate('banner_updated_successfully'));
        return redirect()->route(Banner::UPDATE[ROUTE]);
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $status = $request->get('status', 0);
        $this->bannerRepo->update(id:$request['id'], data:['published'=>$status]);
        return response()->json([
            'message' => $status == 1 ? translate("banner_published_successfully") : translate("banner_unpublished_successfully"),
        ]);
    }

    public function delete(Request $request): JsonResponse
    {
        $banner = $this->bannerRepo->getFirstWhere(params: ['id' => $request['id']]);
        $this->deleteBannerMedia(file: data_get($banner, 'photo'), defaultDirectory: '/storage/banner/');
        $this->deleteBannerMedia(file: data_get($banner, 'video'), defaultDirectory: '/storage/banner/');
        $this->bannerRepo->delete(params: ['id' => $request['id']]);
        return response()->json(['message' => translate('banner_deleted_successfully')]);
    }
}
