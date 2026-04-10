<?php

namespace App\Http\Controllers\Admin\HotelManagement;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\HotelService;
use App\Models\Seller;
use App\Models\HotelBooking;
use App\Models\RoomType;
use App\Models\Amenities;
use App\Utils\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;

class HotelController extends Controller
{
    /**
     * Show create hotel form
     */
    public function create()
    {

        $sellers = Seller::approved()->get();
        
        return view('admin-views.hotel.hotel-create', compact('sellers'));
    }

    /**
     * View all hotels (Admin)
     */
    public function all(Request $request)
    {
        $query = Hotel::with(['seller', 'roomTypes']);

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('city', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('address', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by seller
        if ($request->has('seller_id') && $request->seller_id != '') {
            $query->where('seller_id', $request->seller_id);
        }

        // Filter by city
        if ($request->has('city') && $request->city != '') {
            $query->where('city', $request->city);
        }

        // Filter by star rating
        if ($request->has('star_rating') && $request->star_rating != '') {
            $query->where('star_rating', $request->star_rating);
        }

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        $hotels = $query->latest()->paginate(10)->withQueryString();
        
        // Get approved sellers that have hotels
        $sellerIds = Hotel::distinct()->pluck('seller_id');
        $sellers = Seller::approved()
                        ->whereIn('id', $sellerIds)
                        ->select(['id', 'f_name', 'l_name', 'email'])
                        ->get();
        
        // Get unique cities from hotels
        $cities = Hotel::distinct()
                       ->whereNotNull('city')
                       ->where('city', '!=', '')
                       ->pluck('city');
        
        $starRatings = [1, 2, 3, 4, 5];
        
        // Counts for badges
        $totalHotels = Hotel::count();
        $pendingCount = Hotel::where('status', 0)->count();
        $approvedCount = Hotel::where('status', 1)->count();
        $rejectedCount = Hotel::where('status', 2)->count();

        return view('admin-views.hotel.all-hotels', compact(
            'hotels', 
            'sellers', 
            'cities', 
            'starRatings',
            'totalHotels',
            'pendingCount',
            'approvedCount',
            'rejectedCount'
        ));
    }

    /**
     * View pending hotels (for approval/rejection)
     */
    public function pending(Request $request)
    {
        $query = Hotel::with(['seller', 'roomTypes'])
                      ->where('status', 0); // 0 = pending

        if ($request->has('search') && !empty($request->search)) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $hotels = $query->latest()->paginate(10)->withQueryString();
        $pendingCount = Hotel::where('status', 0)->count();
        $approvedCount = Hotel::where('status', 1)->count();
        $rejectedCount = Hotel::where('status', 2)->count();
        $totalHotels = Hotel::count();

        return view('admin-views.hotel.pending-hotels', compact(
            'hotels', 
            'pendingCount',
            'approvedCount',
            'rejectedCount',
            'totalHotels'
        ));
    }

    /**
     * View approved hotels
     */
    public function approved(Request $request)
    {
        $query = Hotel::with(['seller', 'roomTypes'])
                      ->where('status', 1); // 1 = approved

        if ($request->has('search') && !empty($request->search)) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $hotels = $query->latest()->paginate(10)->withQueryString();
        $approvedCount = Hotel::where('status', 1)->count();
        $pendingCount = Hotel::where('status', 0)->count();
        $rejectedCount = Hotel::where('status', 2)->count();
        $totalHotels = Hotel::count();

        return view('admin-views.hotel.approved-hotels', compact(
            'hotels', 
            'approvedCount',
            'pendingCount',
            'rejectedCount',
            'totalHotels'
        ));
    }

    /**
     * View rejected hotels
     */
    public function rejected(Request $request)
    {
        $query = Hotel::with(['seller', 'roomTypes'])
                      ->where('status', 2); // 2 = rejected

        if ($request->has('search') && !empty($request->search)) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $hotels = $query->latest()->paginate(10)->withQueryString();
        $rejectedCount = Hotel::where('status', 2)->count();
        $pendingCount = Hotel::where('status', 0)->count();
        $approvedCount = Hotel::where('status', 1)->count();
        $totalHotels = Hotel::count();

        return view('admin-views.hotel.rejected-hotels', compact(
            'hotels', 
            'rejectedCount',
            'pendingCount',
            'approvedCount',
            'totalHotels'
        ));
    }

    /**
     * View single hotel details
     */
    public function view($id)
    {
        $hotel = Hotel::with(['seller', 'roomTypes' => function($q) {
            $q->withCount('bookings');
        }])->findOrFail($id);

        $hotelServices = HotelService::where('hotel_id', $id)
            ->latest('sort_order')
            ->latest('id')
            ->get();

        $recentBookings = HotelBooking::with('customer')
                                      ->where('hotel_id', $id)
                                      ->latest()
                                      ->take(5)
                                      ->get();

        $totalBookings = HotelBooking::where('hotel_id', $id)->count();
        $totalRevenue = HotelBooking::where('hotel_id', $id)
                                    ->where('payment_status', 'paid')
                                    ->sum('total_price');
        $cancelledBookings = HotelBooking::where('hotel_id', $id)
                                        ->where('booking_status', 'cancelled')
                                        ->count();

        return view('admin-views.hotel.hotel-details', compact(
            'hotel', 
            'hotelServices',
            'recentBookings', 
            'totalBookings', 
            'totalRevenue', 
            'cancelledBookings'
        ));
    }

    /**
     * Store new hotel
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'seller_id' => 'required|exists:sellers,id',
            'email' => 'required|email|unique:hotels',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'star_rating' => 'required|integer|min:1|max:5',
            'total_rooms' => 'integer|min:0',
            'base_price' => 'numeric|min:0',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg|max:51200',
        ]);

        $hotel = new Hotel();
        $hotel->name = $request->name;
        $hotel->slug = Str::slug($request->name) . '-' . uniqid();
        $hotel->seller_id = $request->seller_id;
        $hotel->email = $request->email;
        $hotel->phone = $request->phone;
        $hotel->address = $request->address;
        $hotel->city = $request->city;
        $hotel->state = $request->state;
        $hotel->country = $request->country;
        $hotel->postal_code = $request->postal_code;
        $hotel->description = $request->description;
        $hotel->star_rating = $request->star_rating;
        $hotel->total_rooms = $request->total_rooms ?? 0;
        $hotel->check_in_time = $request->check_in_time ?? '14:00:00';
        $hotel->check_out_time = $request->check_out_time ?? '12:00:00';
        $hotel->status = 1; // Auto-approved when added by admin
        
        // Handle image upload
        if ($request->hasFile('featured_image')) {
            $hotel->featured_image = $this->uploadHotelImage($request->file('featured_image'));
        }
        
        $hotel->save();

        Toastr::success(translate('hotel_added_successfully'));
        return redirect()->route('admin.hotels.all');
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $hotel = Hotel::findOrFail($id);
        $sellers = Seller::approved()->get();
        return view('admin-views.hotel.hotel-edit', compact('hotel', 'sellers'));
    }

    /**
     * Update hotel
     */
    public function update(Request $request, $id)
    {
        $hotel = Hotel::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'seller_id' => 'required|exists:sellers,id',
            'email' => 'required|email|unique:hotels,email,' . $id,
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'star_rating' => 'required|integer|min:1|max:5',
            'total_rooms' => 'integer|min:0',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg|max:51200',
        ]);

        $hotel->name = $request->name;
        $hotel->seller_id = $request->seller_id;
        $hotel->email = $request->email;
        $hotel->phone = $request->phone;
        $hotel->website = $request->website;
        $hotel->address = $request->address;
        $hotel->city = $request->city;
        $hotel->state = $request->state;
        $hotel->country = $request->country;
        $hotel->postal_code = $request->postal_code;
        $hotel->latitude = $request->latitude;
        $hotel->longitude = $request->longitude;
        $hotel->description = $request->description;
        $hotel->star_rating = $request->star_rating;
        $hotel->total_rooms = $request->total_rooms ?? 0;
        $hotel->check_in_time = $request->check_in_time;
        $hotel->check_out_time = $request->check_out_time;
        $hotel->image_alt_text = $request->image_alt_text;
        $hotel->commission_rate = $request->commission_rate ?? $hotel->commission_rate;
        
        // Handle image upload
        if ($request->hasFile('featured_image')) {
            if ($hotel->featured_image) {
                Helpers::delete($this->buildHotelImageDeletePath($hotel->featured_image));
            }

            $hotel->featured_image = $this->uploadHotelImage($request->file('featured_image'));
        }
        
        $hotel->save();

        Toastr::success(translate('hotel_updated_successfully'));
        return redirect()->route('admin.hotels.all');
    }

    /**
     * Approve hotel listing
     */
    public function approve($id)
    {
        $hotel = Hotel::findOrFail($id);
        
        if ($hotel->status == 1) {
            Toastr::info(translate('hotel_already_approved'));
            return back();
        }

        $hotel->status = 1; // Approved
        $hotel->approved_by = auth('admin')->id();
        $hotel->approved_at = now();
        $hotel->rejection_reason = null;
        $hotel->save();

        Toastr::success(translate('hotel_approved_successfully'));
        return back();
    }

    /**
     * Reject hotel listing with reason
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        $hotel = Hotel::findOrFail($id);
        
        $hotel->status = 2; // Rejected
        $hotel->rejection_reason = $request->rejection_reason;
        $hotel->save();

        Toastr::success(translate('hotel_rejected_successfully'));
        return back();
    }

    /**
     * Update hotel status (featured, etc.)
     */
    public function updateStatus(Request $request, $id)
    {
        $hotel = Hotel::findOrFail($id);
        
        if ($request->has('is_featured')) {
            $hotel->is_featured = $request->is_featured;
        }

        $hotel->save();
        
        Toastr::success(translate('status_updated_successfully'));
        return response()->json(['success' => true]);
    }

    /**
     * Export hotels list
     */
    public function export($type)
    {
        // Implementation for Excel/PDF export
        if ($type == 'excel') {
            // Export to Excel
        } elseif ($type == 'pdf') {
            // Export to PDF
        }
    }

    /**
     * Delete hotel
     */
    public function delete($id)
    {
        $hotel = Hotel::findOrFail($id);
        
        // Check if hotel has active bookings
        $activeBookings = HotelBooking::where('hotel_id', $id)
                                      ->whereIn('booking_status', ['pending', 'confirmed', 'checked_in'])
                                      ->count();
        
        if ($activeBookings > 0) {
            Toastr::warning(translate('cannot_delete_hotel_with_active_bookings'));
            return back();
        }

        HotelService::where('hotel_id', $id)->each(function ($service) {
            if ($service->image) {
                Helpers::delete('hotel/services/' . $service->image);
            }
        });

        HotelService::where('hotel_id', $id)->delete();

        // Delete room types first
        $hotel->roomTypes()->delete();
        
        // Delete hotel
        $hotel->delete();

        Toastr::success(translate('hotel_deleted_successfully'));
        return back();
    }

    public function services(Request $request, $hotelId)
    {
        $hotel = Hotel::findOrFail($hotelId);

        $query = HotelService::where('hotel_id', $hotelId);

        if ($request->filled('searchValue')) {
            $query->where(function ($builder) use ($request) {
                $builder->where('title', 'like', '%' . $request->searchValue . '%')
                    ->orWhere('short_description', 'like', '%' . $request->searchValue . '%')
                    ->orWhere('service_type', 'like', '%' . $request->searchValue . '%');
            });
        }

        $services = $query->orderBy('sort_order')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin-views.hotel.all-services', compact('hotel', 'services'));
    }

    public function servicesIndex(Request $request)
    {
        $query = Hotel::query();

        if ($request->filled('searchValue')) {
            $query->where(function ($builder) use ($request) {
                $builder->where('name', 'like', '%' . $request->searchValue . '%')
                    ->orWhere('city', 'like', '%' . $request->searchValue . '%')
                    ->orWhere('email', 'like', '%' . $request->searchValue . '%')
                    ->orWhere('address', 'like', '%' . $request->searchValue . '%');
            });
        }

        $hotels = $query->latest('id')
            ->paginate(10)
            ->withQueryString();

        $serviceStats = HotelService::selectRaw('hotel_id, COUNT(*) as total_services, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active_services')
            ->whereIn('hotel_id', $hotels->pluck('id'))
            ->groupBy('hotel_id')
            ->get()
            ->keyBy('hotel_id');

        return view('admin-views.hotel.service-hotels', compact('hotels', 'serviceStats'));
    }

    public function addService(Request $request, $hotelId)
    {
        $hotel = Hotel::findOrFail($hotelId);

        $request->validate([
            'title' => 'required|string|max:255',
            'short_description' => 'nullable|string|max:1000',
            'service_type' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:51200',
        ]);

        $service = new HotelService();
        $service->hotel_id = $hotel->id;
        $service->title = $request->title;
        $service->short_description = $request->short_description;
        $service->service_type = $request->service_type ?? 'highlight';
        $service->sort_order = $request->sort_order ?? 0;
        $service->status = 1;

        if ($request->hasFile('image')) {
            $service->image = $this->uploadServiceImage($request->file('image'));
        }

        $service->save();

        Toastr::success(translate('hotel_service_added_successfully'));
        return redirect()->route('admin.hotels.services', $hotelId);
    }

    public function editService(Request $request, $id)
    {
        $service = HotelService::with('hotel')->findOrFail($id);

        return view('admin-views.hotel.service-edit', compact('service'));
    }

    public function updateService(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'short_description' => 'nullable|string|max:1000',
            'service_type' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:51200',
        ]);

        $service = HotelService::findOrFail($id);
        $service->title = $request->title;
        $service->short_description = $request->short_description;
        $service->service_type = $request->service_type ?? 'highlight';
        $service->sort_order = $request->sort_order ?? 0;

        if ($request->hasFile('image')) {
            if ($service->image) {
                Helpers::delete('hotel/services/' . $service->image);
            }

            $service->image = $this->uploadServiceImage($request->file('image'));
        }

        $service->save();

        Toastr::success(translate('hotel_service_updated_successfully'));
        return redirect()->route('admin.hotels.services', $service->hotel_id);
    }

    public function updateServiceStatus(Request $request, $id)
    {
        $service = HotelService::findOrFail($id);
        $service->status = $request->status ?? !$service->status;
        $service->save();

        Toastr::success(translate('status_updated_successfully'));
        return response()->json(['success' => true]);
    }

    public function deleteService(Request $request, $id)
    {
        $service = HotelService::findOrFail($id);
        $hotelId = $service->hotel_id;

        if ($service->image) {
            Helpers::delete('hotel/services/' . $service->image);
        }

        $service->delete();

        Toastr::success(translate('hotel_service_deleted_successfully'));
        return redirect()->route('admin.hotels.services', $hotelId);
    }

    protected function uploadServiceImage($image)
    {
        $directory = public_path('storage/app/hotel/services');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $extension = $image->getClientOriginalExtension() ?: 'png';
        $fileName = Str::uuid() . '.' . strtolower($extension);

        $image->move($directory, $fileName);

        return $fileName;
    }

    protected function uploadHotelImage($image)
    {
        $directory = public_path('storage/app/hotel/hotel');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $extension = strtolower($image->getClientOriginalExtension() ?: 'png');
        $fileName = Str::random(20) . '.' . $extension;

        $image->move($directory, $fileName);

        return $fileName;
    }

    protected function extractHotelImageName(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $path = trim((string) (parse_url($value, PHP_URL_PATH) ?: $value), '/');

        return basename($path);
    }

    protected function buildHotelImageDeletePath(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        return 'hotel/hotel/' . $this->extractHotelImageName($value);
    }

    // amenties routes (rahul 03-03-2026)

    public function amenities(Request $request)
    {
        $query = Amenities::query();

        if ($request->filled('searchValue')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->searchValue . '%')
                ->orWhere('category', 'like', '%' . $request->searchValue . '%');
            });
        }

        $amenities = $query->latest()->paginate(10)->withQueryString();

        return view('admin-views.hotel.all-amenities', compact('amenities'));
    }

    public function addAmenities(Request $request)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $amenities = new Amenities();
        $amenities->name = $request->name;
        $amenities->category = $request->category;
        $amenities->status = 1; 
        
        if ($request->hasFile('icon')) {
            $amenities->icon = Helpers::upload( 'png', $request->file('icon'));
        }
        
        
        $amenities->save();

        Toastr::success(translate('amenity_added_successfully'));
        return redirect()->route('admin.hotels.all-amenities');

    }

    public function editAmenities(Request $request, $id)
    {
        $amenity = Amenities::findOrFail($id);

        return view('admin-views.hotel.amenities-edit', compact('amenity'));
    }

    public function updateAmenities(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $amenity = Amenities::findOrFail($id);

        $amenity->name = $request->name;
        $amenity->category = $request->category;

        if ($request->hasFile('icon')) {

            if ($amenity->icon) {
                Helpers::delete('hotel/' . $amenity->icon);
            }

            $amenity->icon = Helpers::upload('hotel/', 'png', $request->file('icon'));
        }

        $amenity->save();

        Toastr::success(translate('amenity_updated_successfully'));
        return redirect()->route('admin.hotels.all-amenities');
    }

    public function deleteAmenities(Request $request, $id)
    {
        $amenity = Amenities::findOrFail($id);

        if ($amenity->icon) {
            Helpers::delete('hotel/' . $amenity->icon);
        }

        $amenity->delete();

        Toastr::success(translate('amenity_deleted_successfully'));
        return redirect()->route('admin.hotels.all-amenities');
    }
}