<?php

namespace App\Http\Controllers\Admin\TowManagement;

use App\Contracts\Repositories\ActiveTripRepositoryInterface;
use App\Contracts\Repositories\TowRequestRepositoryInterface;
use App\Contracts\Repositories\TowProviderRepositoryInterface;
use App\Enums\ViewPaths\Admin\ActiveTrip;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\AssignProviderRequest;
use App\Http\Requests\Admin\ReassignProviderRequest;
use App\Services\ActiveTripService;
use App\Traits\PaginatorTrait;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActiveTripController extends BaseController
{
    use PaginatorTrait;

    public function __construct(
        private readonly ActiveTripRepositoryInterface     $activeTripRepo,
        private readonly TowRequestRepositoryInterface     $towRequestRepo,
        private readonly TowProviderRepositoryInterface    $providerRepo,
        private readonly ActiveTripService                 $activeTripService,
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
            'provider_id' => $request->get('provider_id'),
            'date_range' => $request->get('date_range'),
        ];

        $activeTrips = $this->activeTripRepo->getListWhere(
            orderBy: ['created_at' => 'desc'],
            searchValue: $request->get('searchValue'),
            filters: $filters,
            relations: ['request.customer', 'provider.user', 'dispatcher'],
            dataLimit: getWebConfig(name: 'pagination_limit')
        );

        $statistics = $this->activeTripService->getStatistics();

        // return view(ActiveTrip::LIST[VIEW], [
        //     'activeTrips' => $activeTrips,
        //     'statistics' => $statistics,
        //     'statuses' => ['assigned', 'accepted', 'en_route', 'arrived', 'in_progress'],
        //     'providers' => $this->providerRepo->getListWhere(dataLimit: 'all'),
        //     'filters' => $filters,
        // ]);
        return view('admin-views.tow-management.active-trips.list', [
            'activeTrips' => $activeTrips,
            'statistics' => $statistics,
            'statuses' => ['assigned', 'accepted', 'en_route', 'arrived', 'in_progress'],
            'providers' => $this->providerRepo->getListWhere(dataLimit: 'all'),
            'filters' => $filters,
        ]);
    }

    public function getMapView(Request $request): View
    {
        $activeTrips = $this->activeTripRepo->getListWhere(
            orderBy: ['created_at' => 'desc'],
            filters: ['status' => ['assigned', 'accepted', 'en_route', 'arrived', 'in_progress']],
            relations: ['request.customer', 'provider.user'],
            dataLimit: 'all'
        );

        return view('admin-views.tow-management.active-trips.map-view', [
            'activeTrips' => $activeTrips,
            'activeTripService' => $this->activeTripService,
        ]);
    }

    public function getDetailsView(string|int $id): View|RedirectResponse
    {
        $activeTrip = $this->activeTripRepo->getFirstWhere(
            params: ['id' => $id],
            relations: ['request.customer', 'provider.user', 'dispatcher', 'trackingLocations' => function($query) {
                $query->orderBy('recorded_at', 'desc')->limit(100);
            }]
        );

        if (!$activeTrip) {
            Toastr::error(translate('active_trip_not_found'));
            return redirect()->route('admin.tow-management.active-trips.list');
        }

        return view(ActiveTrip::DETAILS[VIEW], [
            'activeTrip' => $activeTrip,
            'trackingData' => $this->activeTripService->formatTrackingData($activeTrip->trackingLocations),
        ]);
    }

    public function assignProvider(AssignProviderRequest $request): RedirectResponse
    {
        $towRequest = $this->towRequestRepo->getFirstWhere(params: ['id' => $request['request_id']]);

        if (!$towRequest) {
            Toastr::error(translate('tow_request_not_found'));
            return back();
        }

        if ($towRequest->status != 'pending') {
            Toastr::warning(translate('request_already_assigned'));
            return back();
        }

        $provider = $this->providerRepo->getFirstWhere(params: ['id' => $request['provider_id']]);

        if (!$provider || !$provider->is_available) {
            Toastr::error(translate('provider_not_available'));
            return back();
        }

        $activeTripData = $this->activeTripService->getAssignData(
            request: $request,
            dispatcherId: auth('admin')->id()
        );

        $activeTrip = $this->activeTripRepo->add(data: $activeTripData);

        // Update request status
        $this->towRequestRepo->update(id: $request['request_id'], data: ['status' => 'assigned']);

        // Update provider's current trips count
        $this->providerRepo->update(
            id: $request['provider_id'],
            data: ['current_trips_count' => $provider->current_trips_count + 1]
        );

        Toastr::success(translate('provider_assigned_successfully'));
        return redirect()->route('admin.tow-management.active-trips.details', ['id' => $activeTrip->id]);
    }

    public function reassignProvider(ReassignProviderRequest $request): RedirectResponse
    {
        $activeTrip = $this->activeTripRepo->getFirstWhere(params: ['id' => $request['trip_id']]);

        if (!$activeTrip) {
            Toastr::error(translate('active_trip_not_found'));
            return back();
        }

        $newProvider = $this->providerRepo->getFirstWhere(params: ['id' => $request['new_provider_id']]);

        if (!$newProvider || !$newProvider->is_available) {
            Toastr::error(translate('new_provider_not_available'));
            return back();
        }

        // Update old provider's count
        $oldProvider = $this->providerRepo->getFirstWhere(params: ['id' => $activeTrip->provider_id]);
        $this->providerRepo->update(
            id: $oldProvider->id,
            data: ['current_trips_count' => $oldProvider->current_trips_count - 1]
        );

        // Update new provider's count
        $this->providerRepo->update(
            id: $newProvider->id,
            data: ['current_trips_count' => $newProvider->current_trips_count + 1]
        );

        // Update active trip
        $updateData = [
            'provider_id' => $newProvider->id,
            'current_status' => 'assigned',
            'cancellation_reason' => $request['reassign_reason'],
        ];

        $this->activeTripRepo->update(id: $activeTrip->id, data: $updateData);

        Toastr::success(translate('provider_reassigned_successfully'));
        return redirect()->back();
    }

    public function updateTripStatus(Request $request): JsonResponse
    {
        $request->validate([
            'trip_id' => 'required|integer',
            'status' => 'required|in:assigned,accepted,en_route,arrived,in_progress,completed',
        ]);

        $activeTrip = $this->activeTripRepo->getFirstWhere(params: ['id' => $request['trip_id']]);

        if (!$activeTrip) {
            return response()->json(['success' => 0, 'message' => translate('trip_not_found')], 404);
        }

        $timeField = $this->activeTripService->getTimeFieldForStatus($request['status']);
        $updateData = [
            'current_status' => $request['status'],
        ];

        if ($timeField) {
            $updateData[$timeField] = now();
        }

        if ($request['status'] == 'completed') {
            // Update provider stats on completion
            $provider = $this->providerRepo->getFirstWhere(params: ['id' => $activeTrip->provider_id]);
            $this->providerRepo->update(
                id: $provider->id,
                data: [
                    'current_trips_count' => $provider->current_trips_count - 1,
                    'total_completed_trips' => $provider->total_completed_trips + 1,
                ]
            );
        }

        $this->activeTripRepo->update(id: $activeTrip->id, data: $updateData);

        // Update related tow request status
        $this->towRequestRepo->update(id: $activeTrip->request_id, data: ['status' => $request['status']]);

        return response()->json(['success' => 1, 'message' => translate('trip_status_updated')], 200);
    }

    public function getLiveTrackingData(Request $request): JsonResponse
    {
        $request->validate([
            'trip_id' => 'required|integer',
        ]);

        $activeTrip = $this->activeTripRepo->getFirstWhere(
            params: ['id' => $request['trip_id']],
            relations: ['provider', 'trackingLocations' => function($query) {
                $query->orderBy('recorded_at', 'desc')->limit(1);
            }]
        );

        if (!$activeTrip) {
            return response()->json(['success' => 0, 'message' => translate('trip_not_found')], 404);
        }

        $data = [
            'trip_id' => $activeTrip->id,
            'status' => $activeTrip->current_status,
            'provider_location' => [
                'lat' => $activeTrip->provider->current_latitude,
                'lng' => $activeTrip->provider->current_longitude,
                'last_update' => $activeTrip->provider->last_location_update,
            ],
            'pickup_location' => [
                'lat' => $activeTrip->request->pickup_latitude,
                'lng' => $activeTrip->request->pickup_longitude,
            ],
            'destination' => [
                'lat' => $activeTrip->request->destination_latitude,
                'lng' => $activeTrip->request->destination_longitude,
            ],
            'estimated_arrival' => $activeTrip->estimated_arrival_minutes,
        ];

        return response()->json(['success' => 1, 'data' => $data], 200);
    }
}