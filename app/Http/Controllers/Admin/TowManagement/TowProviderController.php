<?php

namespace App\Http\Controllers\Admin\TowManagement;

use App\Contracts\Repositories\TowProviderRepositoryInterface;
// use App\Contracts\Repositories\UserRepositoryInterface;
use App\Enums\ExportFileNames\Admin\TowProvider as TowProviderExport;
use App\Enums\ViewPaths\Admin\TowProvider;
use App\Exports\TowProviderListExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\TowProviderAddRequest;
use App\Http\Requests\Admin\TowProviderUpdateRequest;
use App\Http\Requests\Admin\TowProviderStatusRequest;
use App\Services\TowProviderService;
use App\Traits\PaginatorTrait;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TowProviderController extends BaseController
{
    use PaginatorTrait;

    public function __construct(
        private readonly TowProviderRepositoryInterface $providerRepo,
        // private readonly UserRepositoryInterface           $userRepo,
        private readonly TowProviderService $providerService,
    ) {
    }

    /**
     * @param Request|null $request
     * @param string|null $type
     * @return View
     */
    public function index(Request|null $request, string $type = null): View
    {
        return $this->getListView($request);
    }

    public function getListView(Request $request): View
    {
        $filters = [
            'status' => $request->get('status', 'all'),
            'rating' => $request->get('rating', 'all'),
            'service_area' => $request->get('service_area'),
        ];

        $providers = $this->providerRepo->getListWhere(
            orderBy: ['rating' => 'desc', 'id' => 'desc'],
            searchValue: $request->get('searchValue'),
            filters: $filters,
            relations: ['user'],
            dataLimit: getWebConfig(name: 'pagination_limit')
        );

        $statistics = $this->providerService->getStatistics();


        // return view(TowProvider::LIST[VIEW], [
        //     'providers' => $providers,
        //     'statistics' => $statistics,
        //     'statuses' => ['available', 'busy', 'offline', 'on_break'],
        //     'filters' => $filters,
        //     'providerService' => $this->providerService, 
        // ]);
        // Define variables first
        $statuses = ['available', 'busy', 'offline', 'on_break'];
        $providerService = $this->providerService;

        // Then pass them using compact
        return view('admin-views.tow-management.providers.list', compact(
            'providers',
            'statistics',
            'statuses',
            'filters',
            'providerService'
        ));
    }

    public function getAddView(): View
    {
        $users = $this->providerRepo->getListWhere(
            filters: ['is_active' => 1],
            dataLimit: 'all'
        )->filter(function($user) {
            return !$user->towProvider()->exists();
        });
    
        // return view(TowProvider::ADD[VIEW], [
        //     'users' => $users,
        // ]);
        return view('admin-views.tow-management.providers.add', compact(
            'users',
            
        ));
    }

    public function add(TowProviderAddRequest $request, TowProviderService $providerService): RedirectResponse
    {
        $dataArray = $this->providerService->getAddData(request: $request);
        $this->providerRepo->add(data: $dataArray);

        // Update user role or type if needed
        // $this->userRepo->update(id: $request['user_id'], data: ['role' => 'provider']);

        Toastr::success(translate('provider_added_successfully'));
        return redirect()->route('admin.tow-management.providers.list');
    }

    public function getUpdateView(string|int $id): View|RedirectResponse
    {
        $provider = $this->providerRepo->getFirstWhere(
            params: ['id' => $id],
            relations: ['user']
        );

        if (!$provider) {
            Toastr::error(translate('provider_not_found'));
            return redirect()->route('admin.tow-management.providers.list');
        }

        return view(TowProvider::UPDATE[VIEW], [
            'provider' => $provider,
            'providerService' => $this->providerService,
        ]);
    }

    public function update(TowProviderUpdateRequest $request, TowProviderService $providerService): RedirectResponse
    {
        $provider = $this->providerRepo->getFirstWhere(params: ['id' => $request['id']]);

        if (!$provider) {
            Toastr::error(translate('provider_not_found'));
            return back();
        }

        $dataArray = $providerService->getUpdateData(request: $request, data: $provider);
        $this->providerRepo->update(id: $request['id'], data: $dataArray);

        Toastr::success(translate('provider_updated_successfully'));
        return redirect()->route('admin.tow-management.providers.list');
    }

    public function updateStatus(TowProviderStatusRequest $request): JsonResponse
    {
        $provider = $this->providerRepo->getFirstWhere(params: ['id' => $request['id']]);

        if (!$provider) {
            return response()->json(['success' => 0, 'message' => translate('provider_not_found')], 404);
        }

        $updateData = [
            'status' => $request->get('status'),
        ];

        if ($request->get('status') == 'available') {
            $updateData['last_location_update'] = now();
        }

        $this->providerRepo->update(id: $request['id'], data: $updateData);

        return response()->json(['success' => 1, 'message' => translate('status_updated_successfully')], 200);
    }

    public function updateAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer',
            'is_available' => 'required|boolean',
        ]);

        $provider = $this->providerRepo->getFirstWhere(params: ['id' => $request['id']]);

        if (!$provider) {
            return response()->json(['success' => 0, 'message' => translate('provider_not_found')], 404);
        }

        $status = $request['is_available'] ? 'available' : 'offline';
        $this->providerRepo->update(id: $request['id'], data: ['status' => $status]);

        return response()->json(['success' => 1, 'message' => translate('availability_updated')], 200);
    }

    public function getProviderTrips(string|int $id, Request $request): View|RedirectResponse
    {
        $provider = $this->providerRepo->getFirstWhere(params: ['id' => $id], relations: ['user']);

        if (!$provider) {
            Toastr::error(translate('provider_not_found'));
            return redirect()->route('admin.tow-management.providers.list');
        }

        $trips = $provider->tripHistory()
            ->with(['request.customer'])
            ->orderBy('created_at', 'desc')
            ->paginate(getWebConfig(name: 'pagination_limit'));

        return view(TowProvider::TRIPS[VIEW], [
            'provider' => $provider,
            'trips' => $trips,
        ]);
    }

    public function updateLocation(Request $request): JsonResponse
    {
        $request->validate([
            'provider_id' => 'required|integer',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $provider = $this->providerRepo->getFirstWhere(params: ['id' => $request['provider_id']]);

        if (!$provider) {
            return response()->json(['success' => 0, 'message' => translate('provider_not_found')], 404);
        }

        if (!$provider->updateLocation($request['latitude'], $request['longitude'])) {
            return response()->json(['success' => 0, 'message' => translate('location_update_failed')], 500);
        }

        return response()->json(['success' => 1, 'message' => translate('location_updated')], 200);
    }

    public function getExportList(Request $request): BinaryFileResponse
    {
        $filters = [
            'status' => $request->get('status', 'all'),
            'rating' => $request->get('rating', 'all'),
            'service_area' => $request->get('service_area'),
        ];

        $providers = $this->providerRepo->getListWhere(
            orderBy: ['rating' => 'desc'],
            searchValue: $request->get('searchValue'),
            filters: $filters,
            relations: ['user'],
            dataLimit: 'all'
        );

        $statistics = $this->providerService->getStatistics();

        return Excel::download(
            new TowProviderListExport([
                'providers' => $providers,
                'filters' => $filters,
                'statistics' => $statistics,
                'search' => $request['searchValue'],
            ]),
            TowProviderExport::PROVIDER_LIST_XLSX
        );
    }

    public function delete(Request $request): RedirectResponse
    {
        $provider = $this->providerRepo->getFirstWhere(params: ['id' => $request['id']]);

        if (!$provider) {
            Toastr::error(translate('provider_not_found'));
            return redirect()->back();
        }

        // Check if provider has active trips
        if ($provider->activeTrips()->count() > 0) {
            Toastr::warning(translate('cannot_delete_provider_with_active_trips'));
            return redirect()->back();
        }

        // Revert user role if needed
        // $this->userRepo->update(id: $provider->user_id, data: ['role' => 'customer']);

        $this->providerRepo->delete(params: ['id' => $request['id']]);
        Toastr::success(translate('provider_deleted_successfully'));
        return redirect()->back();
    }


    public function getDetailsView(string|int $id): View|RedirectResponse
    {
        $provider = $this->providerRepo->getFirstWhere(
            params: ['id' => $id],
            relations: ['user', 'activeTrips']
        );

        if (!$provider) {
            Toastr::error(translate('provider_not_found'));
            return redirect()->route('admin.tow-management.providers.list');
        }

        return view(TowProvider::DETAILS[VIEW], [
            'provider' => $provider,
            'statistics' => [
                'monthly_trips' => $provider->tripHistory()
                    ->whereMonth('created_at', now()->month)
                    ->count(),
                'avg_response_time' => $this->providerService->getAvgResponseTime($provider),
                'earnings' => $this->providerService->getTotalEarnings($provider),
            ]
        ]);
    }


}