<?php

namespace App\Http\Controllers\Admin\TowManagement;

use App\Contracts\Repositories\TowRequestRepositoryInterface;
use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Contracts\Repositories\TowProviderRepositoryInterface;
use App\Enums\ExportFileNames\Admin\TowRequest as TowRequestExport;
use App\Enums\ViewPaths\Admin\TowRequest;
use App\Exports\TowRequestListExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\TowRequestStatusUpdateRequest;
use App\Services\TowRequestService;
use App\Traits\PaginatorTrait;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TowRequestController extends BaseController
{
    use PaginatorTrait;

    public function __construct(
        private readonly TowRequestRepositoryInterface     $towRequestRepo,
        private readonly CustomerRepositoryInterface       $customerRepo,
        private readonly TowProviderRepositoryInterface    $providerRepo,
        private readonly TowRequestService                 $towRequestService,
    )
    {
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
            'priority' => $request->get('priority', 'all'),
            'service_type' => $request->get('service_type', 'all'),
            'date_range' => $request->get('date_range'),
        ];

        $towRequests = $this->towRequestRepo->getListWhere(
            orderBy: ['created_at' => 'desc'],
            searchValue: $request->get('searchValue'),
            filters: $filters,
            relations: ['customer', 'activeTrip'],
            dataLimit: getWebConfig(name: 'pagination_limit')
        );

        $statistics = $this->towRequestService->getStatistics($filters);

        // return view(TowRequest::LIST[VIEW], [
        //     'towRequests' => $towRequests,
        //     'statistics' => $statistics,
        //     'statuses' => ['pending', 'assigned', 'accepted', 'en_route', 'arrived', 'in_progress', 'completed', 'cancelled'],
        //     'priorities' => ['low', 'normal', 'high', 'emergency'],
        //     'serviceTypes' => ['emergency', 'scheduled', 'battery_jump', 'flat_tire', 'fuel_delivery'],
        //     'filters' => $filters,
        // ]);

        return view('admin-views.tow-management.requests.list', [
            'towRequests' => $towRequests,
            'statistics' => $statistics,
            'statuses' => ['pending', 'assigned', 'accepted', 'en_route', 'arrived', 'in_progress', 'completed', 'cancelled'],
            'priorities' => ['low', 'normal', 'high', 'emergency'],
            'serviceTypes' => ['emergency', 'scheduled', 'battery_jump', 'flat_tire', 'fuel_delivery'],
            'filters' => $filters,
        ]);
    }

    public function getDetailsView(string|int $id): View|RedirectResponse
    {
        $towRequest = $this->towRequestRepo->getFirstWhere(
            params: ['id' => $id],
            relations: ['customer', 'activeTrip.provider', 'activeTrip.trackingLocations']
        );

        if (!$towRequest) {
            Toastr::error(translate('tow_request_not_found'));
            return redirect()->route('admin.tow-management.requests.list');
        }

        $nearbyProviders = $this->providerRepo->getNearbyAvailableProviders(
            latitude: $towRequest->pickup_latitude,
            longitude: $towRequest->pickup_longitude,
            radius: 10
        );

        return view(TowRequest::DETAILS[VIEW], [
            'towRequest' => $towRequest,
            'nearbyProviders' => $nearbyProviders,
        ]);
    }

    public function updateStatus(Request $request, TowRequestService $towRequestService): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer',
            'status' => 'required|in:pending,assigned,accepted,en_route,arrived,in_progress,completed,cancelled',
            'cancellation_reason' => 'required_if:status,cancelled|string|nullable',
        ]);

        $towRequest = $this->towRequestRepo->getFirstWhere(params: ['id' => $request['id']]);

        if (!$towRequest) {
            return response()->json(['success' => 0, 'message' => translate('request_not_found')], 404);
        }

        $updateData = $towRequestService->getStatusUpdateData($request, $towRequest);
        $this->towRequestRepo->update(id: $request['id'], data: $updateData);

        if ($towRequest->activeTrip) {
            $towRequest->activeTrip->update(['current_status' => $request['status']]);
        }

        return response()->json(['success' => 1, 'message' => translate('status_updated_successfully')], 200);
    }

    public function getExportList(Request $request): BinaryFileResponse
    {
        $filters = [
            'status' => $request->get('status', 'all'),
            'priority' => $request->get('priority', 'all'),
            'service_type' => $request->get('service_type', 'all'),
            'date_range' => $request->get('date_range'),
        ];

        $towRequests = $this->towRequestRepo->getListWhere(
            orderBy: ['created_at' => 'desc'],
            searchValue: $request->get('searchValue'),
            filters: $filters,
            relations: ['customer', 'activeTrip.provider'],
            dataLimit: 'all'
        );

        $statistics = $this->towRequestService->getStatistics($filters);

        return Excel::download(
            new TowRequestListExport([
                'towRequests' => $towRequests,
                'filters' => $filters,
                'statistics' => $statistics,
                'search' => $request['searchValue'],
            ]),
            TowRequestExport::TOW_REQUEST_LIST_XLSX
        );
    }

    public function delete(Request $request): RedirectResponse
    {
        $towRequest = $this->towRequestRepo->getFirstWhere(params: ['id' => $request['id']]);

        if (!$towRequest) {
            Toastr::error(translate('tow_request_not_found'));
            return redirect()->back();
        }

        if ($towRequest->activeTrip && !in_array($towRequest->activeTrip->current_status, ['completed', 'cancelled'])) {
            Toastr::warning(translate('cannot_delete_request_with_active_trip'));
            return redirect()->back();
        }

        $this->towRequestRepo->delete(params: ['id' => $request['id']]);
        Toastr::success(translate('request_deleted_successfully'));
        return redirect()->back();
    }
}