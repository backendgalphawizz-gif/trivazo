<?php

namespace App\Http\Controllers\Admin\CarPool;

use App\Http\Controllers\Controller;
use App\Models\CarPoolDriver;
use App\Models\CarPoolVehicleCategory;
use App\Models\CarPoolRoute;
use App\Models\CarPoolBooking;
use App\User;
use App\Repositories\CarPoolBookingRepository;
use App\Repositories\CarPoolDriverRepository;
use App\Repositories\CarPoolRouteRepository;
use App\Services\CarPoolDriverCustomerSyncService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminCarPoolWebController extends Controller
{
    public function __construct(
        private readonly CarPoolDriverRepository $driverRepo,
        private readonly CarPoolRouteRepository $routeRepo,
        private readonly CarPoolBookingRepository $bookingRepo,
        private readonly CarPoolDriverCustomerSyncService $driverCustomerSync,
    ) {}

    // ─── DRIVERS ────────────────────────────────────────────────────────────

    public function driversList(Request $request): View
    {
        $filters = [
            'status'      => $request->get('status', 'all'),
            'is_verified' => $request->has('is_verified') ? (int) $request->is_verified : null,
        ];

        $drivers = $this->driverRepo->getListWhere(
            orderBy: ['created_at' => 'desc'],
            searchValue: $request->get('search'),
            filters: array_filter($filters, fn($v) => $v !== null && $v !== 'all'),
            relations: ['wallet'],
            dataLimit: (int) getWebConfig(name: 'pagination_limit')
        );

        $statistics = [
            'total'    => CarPoolDriver::count(),
            'active'   => CarPoolDriver::where('status', 'active')->count(),
            'verified' => CarPoolDriver::where('is_verified', true)->count(),
            'online'   => CarPoolDriver::where('is_online', 1)->count(),
        ];

        return view('admin-views.carpool.drivers.list', compact('drivers', 'filters', 'statistics'));
    }

    public function driversAdd(): View
    {
        $vehicleCategories = CarPoolVehicleCategory::activeOrdered();

        return view('admin-views.carpool.drivers.add', compact('vehicleCategories'));
    }

    public function driversStore(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name'            => 'required|string|max:191',
            'phone'           => 'required|string|max:20|unique:carpool_drivers,phone',
            'country_code'    => 'nullable|string|max:12',
            'email'           => 'nullable|email|max:191|unique:carpool_drivers,email',
            'gender'          => 'required|in:male,female,other',
            'password'            => 'required|string|min:6|confirmed',
            'vehicle_category_id' => [
                'required',
                'integer',
                Rule::exists('carpool_vehicle_categories', 'id')->where('is_active', 1),
            ],
            'vehicle_number'      => 'required|string|max:50',
            'vehicle_model'   => 'nullable|string|max:100',
            'vehicle_color'   => 'nullable|string|max:50',
            'vehicle_capacity'=> 'required|integer|min:1|max:20',
            'license_number'  => 'required|string|max:100',
            'license_doc'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'profile_image'   => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'vehicle_image'   => 'nullable|image|mimes:jpg,jpeg,png|max:3072',
        ]);

        if ($validator->fails()) {
            Toastr::error(translate('Please fix the validation errors.'));
            return back()->withErrors($validator)->withInput();
        }

        $category = CarPoolVehicleCategory::query()
            ->where('id', $request->vehicle_category_id)
            ->where('is_active', true)
            ->firstOrFail();

        $data = $request->only([
            'name', 'phone', 'email', 'gender',
            'vehicle_number', 'vehicle_model', 'vehicle_color', 'vehicle_capacity', 'license_number',
        ]);
        $data['country_code']         = $request->input('country_code', '+91');
        $data['vehicle_category_id']  = $category->id;
        $data['vehicle_type']        = $category->name;
        $data['password']            = Hash::make($request->password);
        $data['status']              = 'active';
        $data['is_verified']         = false;

        if ($request->hasFile('license_doc')) {
            $data['license_doc'] = $request->file('license_doc')
                ->store('carpool/drivers/docs', 'public');
        }

        if ($request->hasFile('profile_image')) {
            $data['profile_image'] = $request->file('profile_image')
                ->store('carpool/drivers/photos', 'public');
        }

        if ($request->hasFile('vehicle_image')) {
            $data['vehicle_image'] = $request->file('vehicle_image')
                ->store('carpool/drivers/vehicles', 'public');
        }

        DB::transaction(function () use ($data) {
            $driver = $this->driverRepo->add($data);
            $this->driverCustomerSync->ensureCustomerUserFromDriver($driver);
        });

        Toastr::success(translate('Driver added successfully.'));
        return redirect()->route('admin.carpool.drivers.list');
    }

    public function driversEdit(int $id): View|RedirectResponse
    {
        $driver = CarPoolDriver::find($id);
        if (!$driver) {
            Toastr::error(translate('Driver not found.'));
            return redirect()->route('admin.carpool.drivers.list');
        }

        $vehicleCategories = CarPoolVehicleCategory::activeOrdered();
        $suggestedVehicleCategoryId = old(
            'vehicle_category_id',
            $driver->vehicle_category_id
                ?? CarPoolVehicleCategory::query()
                    ->whereRaw('LOWER(name) = LOWER(?)', [$driver->vehicle_type ?? ''])
                    ->value('id')
        );

        return view(
            'admin-views.carpool.drivers.edit',
            compact('driver', 'vehicleCategories', 'suggestedVehicleCategoryId')
        );
    }

    public function driversUpdate(Request $request, int $id): RedirectResponse
    {
        $driver = CarPoolDriver::find($id);
        if (!$driver) {
            Toastr::error(translate('Driver not found.'));
            return redirect()->route('admin.carpool.drivers.list');
        }

        $validator = Validator::make($request->all(), [
            'name'            => 'required|string|max:191',
            'phone'           => 'required|string|max:20|unique:carpool_drivers,phone,' . $id,
            'country_code'    => 'nullable|string|max:12',
            'email'           => 'nullable|email|max:191|unique:carpool_drivers,email,' . $id,
            'gender'          => 'required|in:male,female,other',
            'password'        => 'nullable|string|min:6|confirmed',
            'vehicle_category_id' => [
                'required',
                'integer',
                Rule::exists('carpool_vehicle_categories', 'id')->where('is_active', 1),
            ],
            'vehicle_number'      => 'required|string|max:50',
            'vehicle_model'       => 'nullable|string|max:100',
            'vehicle_color'       => 'nullable|string|max:50',
            'vehicle_capacity'    => 'required|integer|min:1|max:20',
            'license_number'      => 'required|string|max:100',
            'license_doc'         => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'profile_image'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'vehicle_image'       => 'nullable|image|mimes:jpg,jpeg,png|max:3072',
            'status'              => 'nullable|in:active,inactive,suspended',
        ]);

        if ($validator->fails()) {
            Toastr::error(translate('Please fix the validation errors.'));
            return back()->withErrors($validator)->withInput();
        }

        $category = CarPoolVehicleCategory::query()
            ->where('id', $request->vehicle_category_id)
            ->where('is_active', true)
            ->firstOrFail();

        $data = $request->only([
            'name', 'phone', 'email', 'gender', 'vehicle_number',
            'vehicle_model', 'vehicle_color', 'vehicle_capacity', 'license_number', 'status',
        ]);
        $data['country_code']        = $request->input('country_code', '+91');
        $data['vehicle_category_id'] = $category->id;
        $data['vehicle_type']        = $category->name;

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('license_doc')) {
            if ($driver->license_doc) {
                Storage::disk('public')->delete($driver->license_doc);
            }
            $data['license_doc'] = $request->file('license_doc')
                ->store('carpool/drivers/docs', 'public');
        }

        if ($request->hasFile('profile_image')) {
            if ($driver->profile_image) {
                Storage::disk('public')->delete($driver->profile_image);
            }
            $data['profile_image'] = $request->file('profile_image')
                ->store('carpool/drivers/photos', 'public');
        }

        if ($request->hasFile('vehicle_image')) {
            if ($driver->vehicle_image) {
                Storage::disk('public')->delete($driver->vehicle_image);
            }
            $data['vehicle_image'] = $request->file('vehicle_image')
                ->store('carpool/drivers/vehicles', 'public');
        }

        $this->driverRepo->update($id, $data);
        $this->driverCustomerSync->syncLinkedCustomerFromDriver($driver->fresh());

        Toastr::success(translate('Driver updated successfully.'));
        return redirect()->route('admin.carpool.drivers.list');
    }

    public function driversVerify(int $id): RedirectResponse
    {
        $driver = CarPoolDriver::find($id);
        if (!$driver) {
            Toastr::error(translate('Driver not found.'));
            return back();
        }
        $this->driverRepo->update($id, ['is_verified' => true]);
        Toastr::success(translate('Driver verified successfully.'));
        return back();
    }

    public function driversStatus(Request $request, int $id): RedirectResponse
    {
        $driver = CarPoolDriver::find($id);
        if (!$driver) {
            Toastr::error(translate('Driver not found.'));
            return back();
        }
        $status = $request->get('status', 'active');
        $this->driverRepo->update($id, ['status' => $status]);
        Toastr::success(translate('Driver status updated.'));
        return back();
    }

    // ─── TRIPS / ROUTES ─────────────────────────────────────────────────────

    public function tripsList(Request $request): View
    {
        $filters = [
            'route_status' => $request->get('status', 'all'),
            'ride_type'    => $request->get('ride_type'),
            'date_from'    => $request->get('date_from'),
            'date_to'      => $request->get('date_to'),
        ];

        $trips = $this->routeRepo->getListWhere(
            orderBy: ['departure_at' => 'desc'],
            searchValue: $request->get('search'),
            filters: array_filter($filters, fn($v) => $v !== null && $v !== 'all'),
            relations: ['driver', 'bookings'],
            dataLimit: (int) getWebConfig(name: 'pagination_limit')
        );

        $statistics = [
            'total'     => CarPoolRoute::count(),
            'open'      => CarPoolRoute::where('route_status', 'open')->count(),
            'completed' => CarPoolRoute::where('route_status', 'completed')->count(),
            'cancelled' => CarPoolRoute::where('route_status', 'cancelled')->count(),
        ];

        $statuses  = ['open', 'full', 'departed', 'completed', 'cancelled'];
        $rideTypes = CarPoolRoute::distinct()->pluck('ride_type')->filter()->values()->toArray();

        return view('admin-views.carpool.trips.list', compact('trips', 'filters', 'statistics', 'statuses', 'rideTypes'));
    }

    public function tripsAdd(): View
    {
        $drivers = CarPoolDriver::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'vehicle_number', 'vehicle_type', 'vehicle_capacity']);

        return view('admin-views.carpool.trips.add', compact('drivers'));
    }

    public function tripsStore(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'driver_id'              => 'required|exists:carpool_drivers,id',
            'origin_name'            => 'required|string|max:191',
            'origin_lat'             => 'required|numeric|between:-90,90',
            'origin_lng'             => 'required|numeric|between:-180,180',
            'destination_name'       => 'required|string|max:191',
            'destination_lat'        => 'required|numeric|between:-90,90',
            'destination_lng'        => 'required|numeric|between:-180,180',
            'departure_at'           => 'required|date|after:now',
            'total_seats'            => 'required|integer|min:1|max:50',
            'price_per_seat'         => 'required|numeric|min:0',
            'ride_type'              => 'required|in:instant,scheduled',
            'estimated_duration_min' => 'nullable|integer|min:1',
            'estimated_distance_km'  => 'nullable|numeric|min:0',
            'note'                   => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            Toastr::error(translate('Please fix the validation errors.'));
            return back()->withErrors($validator)->withInput();
        }

        $data = $request->only([
            'driver_id', 'origin_name', 'origin_lat', 'origin_lng',
            'destination_name', 'destination_lat', 'destination_lng',
            'departure_at', 'total_seats', 'price_per_seat', 'ride_type',
            'estimated_duration_min', 'estimated_distance_km', 'note',
        ]);
        $data['available_seats'] = $request->total_seats;
        $data['route_status']    = 'open';
        $data['currency']        = config('carpool.currency', 'INR');

        $this->routeRepo->add($data);

        Toastr::success(translate('Trip added successfully.'));
        return redirect()->route('admin.carpool.trips.list');
    }

    public function tripsEdit(int $id): View|RedirectResponse
    {
        $trip = CarPoolRoute::with(['driver', 'bookings'])->find($id);
        if (!$trip) {
            Toastr::error(translate('Trip not found.'));
            return redirect()->route('admin.carpool.trips.list');
        }

        $drivers = CarPoolDriver::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'vehicle_number', 'vehicle_type', 'vehicle_capacity']);

        $bookedSeats        = $trip->total_seats - $trip->available_seats;
        $trip->bookings_count = $trip->bookings->count();

        return view('admin-views.carpool.trips.edit', compact('trip', 'drivers', 'bookedSeats'));
    }

    public function tripsUpdate(Request $request, int $id): RedirectResponse
    {
        $trip = CarPoolRoute::find($id);
        if (!$trip) {
            Toastr::error(translate('Trip not found.'));
            return redirect()->route('admin.carpool.trips.list');
        }

        $bookedSeats = $trip->total_seats - $trip->available_seats;

        $validator = Validator::make($request->all(), [
            'driver_id'              => 'required|exists:carpool_drivers,id',
            'origin_name'            => 'required|string|max:191',
            'origin_lat'             => 'required|numeric|between:-90,90',
            'origin_lng'             => 'required|numeric|between:-180,180',
            'destination_name'       => 'required|string|max:191',
            'destination_lat'        => 'required|numeric|between:-90,90',
            'destination_lng'        => 'required|numeric|between:-180,180',
            'departure_at'           => 'required|date',
            'total_seats'            => 'required|integer|min:' . max(1, $bookedSeats) . '|max:50',
            'price_per_seat'         => 'required|numeric|min:0',
            'ride_type'              => 'required|in:instant,scheduled',
            'route_status'           => 'nullable|in:open,full,departed,completed,cancelled',
            'estimated_duration_min' => 'nullable|integer|min:1',
            'estimated_distance_km'  => 'nullable|numeric|min:0',
            'note'                   => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            Toastr::error(translate('Please fix the validation errors.'));
            return back()->withErrors($validator)->withInput();
        }

        $data = $request->only([
            'driver_id', 'origin_name', 'origin_lat', 'origin_lng',
            'destination_name', 'destination_lat', 'destination_lng',
            'departure_at', 'total_seats', 'price_per_seat', 'ride_type',
            'estimated_duration_min', 'estimated_distance_km', 'note', 'route_status',
        ]);

        // Recalculate available seats based on new total
        $data['available_seats'] = (int) $request->total_seats - $bookedSeats;

        $this->routeRepo->update($id, $data);

        Toastr::success(translate('Trip updated successfully.'));
        return redirect()->route('admin.carpool.trips.list');
    }

    public function tripsUpdateStatus(Request $request, int $id): RedirectResponse
    {
        $route = CarPoolRoute::find($id);
        if (!$route) {
            Toastr::error(translate('Trip not found.'));
            return back();
        }
        $this->routeRepo->update($id, ['route_status' => $request->get('status')]);
        Toastr::success(translate('Trip status updated.'));
        return back();
    }

    // ─── BOOKINGS ───────────────────────────────────────────────────────────

    public function bookingsList(Request $request): View
    {
        $routeId = $request->get('route_id') ? (int) $request->get('route_id') : null;

        $filters = [
            'status'         => $request->get('status', 'all'),
            'payment_status' => $request->get('payment_status', 'all'),
            'date_from'      => $request->get('date_from'),
            'date_to'        => $request->get('date_to'),
            'route_id'       => $routeId,
        ];

        $bookings = $this->bookingRepo->getListWhere(
            orderBy: ['created_at' => 'desc'],
            searchValue: $request->get('search'),
            filters: array_filter($filters, fn($v) => $v !== null && $v !== 'all'),
            relations: ['route.driver', 'passenger'],
            dataLimit: (int) getWebConfig(name: 'pagination_limit')
        );

        $baseQuery = $routeId
            ? CarPoolBooking::where('route_id', $routeId)
            : CarPoolBooking::query();

        $statistics = [
            'total'     => (clone $baseQuery)->count(),
            'confirmed' => (clone $baseQuery)->where('status', 'confirmed')->count(),
            'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
            'cancelled' => (clone $baseQuery)->where('status', 'cancelled')->count(),
        ];

        $statuses        = ['pending', 'confirmed', 'departed', 'completed', 'cancelled'];
        $paymentStatuses = ['unpaid', 'paid', 'refunded'];

        $filterRoute = $routeId
            ? CarPoolRoute::with('driver')->find($routeId)
            : null;

        return view('admin-views.carpool.bookings.list', compact(
            'bookings', 'filters', 'statistics', 'statuses', 'paymentStatuses', 'filterRoute'
        ));
    }

    public function bookingsShow(int $id): View
    {
        $booking = CarPoolBooking::with(['route.driver', 'passenger', 'passengers'])->findOrFail($id);
        return view('admin-views.carpool.bookings.show', compact('booking'));
    }

    public function bookingsUpdateStatus(Request $request, int $id): RedirectResponse
    {
        $booking = CarPoolBooking::find($id);
        if (!$booking) {
            Toastr::error(translate('Booking not found.'));
            return back();
        }
        $status = $request->get('status');
        $update = ['status' => $status];
        if ($status === 'cancelled') {
            $update['cancelled_by']        = 'admin';
            $update['cancellation_reason'] = $request->get('reason', 'Cancelled by admin');
            $update['cancelled_at']        = now();
        }
        $this->bookingRepo->update($id, $update);
        Toastr::success(translate('Booking status updated.'));
        return back();
    }

    // ─── ADD BOOKING MANUALLY ────────────────────────────────────────────────

    public function bookingsAdd(Request $request): View
    {
        // Only open trips that still have seats
        $trips = CarPoolRoute::with('driver')
            ->where('route_status', 'open')
            ->where('available_seats', '>', 0)
            ->where('departure_at', '>', now())
            ->orderBy('departure_at')
            ->get();

        // Pre-select a trip if coming from trip list
        $selectedTrip = $request->get('trip_id')
            ? CarPoolRoute::with('driver')->find($request->get('trip_id'))
            : null;

        $customers = User::orderBy('f_name')
            ->get(['id', 'f_name', 'l_name', 'phone', 'email']);

        return view('admin-views.carpool.bookings.add', compact('trips', 'customers', 'selectedTrip'));
    }

    public function bookingsStore(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'route_id'       => 'required|exists:carpool_routes,id',
            'passenger_id'   => 'required|exists:users,id',
            'pickup_name'    => 'required|string|max:191',
            'pickup_lat'     => 'required|numeric|between:-90,90',
            'pickup_lng'     => 'required|numeric|between:-180,180',
            'drop_name'      => 'required|string|max:191',
            'drop_lat'       => 'required|numeric|between:-90,90',
            'drop_lng'       => 'required|numeric|between:-180,180',
            'seat_count'     => 'required|integer|min:1|max:20',
            'payment_method' => 'nullable|string|max:50',
            'payment_status' => 'in:unpaid,paid',
        ]);

        if ($validator->fails()) {
            Toastr::error(translate('Please fix the validation errors.'));
            return back()->withErrors($validator)->withInput();
        }

        $route = CarPoolRoute::find($request->route_id);

        if (!$route || $route->route_status !== 'open') {
            Toastr::error(translate('Selected trip is not open for bookings.'));
            return back()->withInput();
        }
        if ($route->available_seats < $request->seat_count) {
            Toastr::error(translate('Not enough seats available. Only :n seats left.', ['n' => $route->available_seats]));
            return back()->withInput();
        }

        $fareTotal  = $route->price_per_seat * $request->seat_count;
        $commission = round($fareTotal * (config('carpool.admin_commission_percent', 10) / 100), 2);

        $this->bookingRepo->add([
            'route_id'                => $request->route_id,
            'passenger_id'            => $request->passenger_id,
            'pickup_name'             => $request->pickup_name,
            'pickup_lat'              => $request->pickup_lat,
            'pickup_lng'              => $request->pickup_lng,
            'drop_name'               => $request->drop_name,
            'drop_lat'                => $request->drop_lat,
            'drop_lng'                => $request->drop_lng,
            'seat_count'              => $request->seat_count,
            'booking_code'            => strtoupper(Str::random(3) . now()->format('His')),
            'status'                  => 'confirmed',
            'fare_total'              => $fareTotal,
            'admin_commission_amount' => $commission,
            'driver_amount'           => $fareTotal - $commission,
            'payment_method'          => $request->payment_method,
            'payment_status'          => $request->get('payment_status', 'unpaid'),
            'confirmed_at'            => now(),
        ]);

        // Deduct seats from route
        $route->decrement('available_seats', $request->seat_count);
        if ($route->fresh()->available_seats <= 0) {
            $route->update(['route_status' => 'full']);
        }

        Toastr::success(translate('Booking created successfully.'));
        return redirect()->route('admin.carpool.bookings.list');
    }
}
