<?php

namespace App\Http\Controllers\Admin\HotelManagement;

use App\Http\Controllers\Controller;
use App\Models\HotelBooking;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;

class HotelBookingController extends Controller
{
    /**
     * Monitor bookings & cancellations
     */
    public function index(Request $request)
    {
        $query = HotelBooking::with(['hotel', 'customer', 'roomType']);

        // Filter by date range
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('created_at', [$request->from_date, $request->to_date]);
        }

        // Filter by hotel
        if ($request->has('hotel_id') && $request->hotel_id != '') {
            $query->where('hotel_id', $request->hotel_id);
        }

        // Filter by booking status
        if ($request->has('booking_status') && $request->booking_status != '') {
            $query->where('booking_status', $request->booking_status);
        }

        // Filter by payment status
        if ($request->has('payment_status') && $request->payment_status != '') {
            $query->where('payment_status', $request->payment_status);
        }

        // Search by booking number or customer name
        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('booking_number', 'like', '%' . $request->search . '%')
                  ->orWhereHas('customer', function($customer) use ($request) {
                      $customer->where('f_name', 'like', '%' . $request->search . '%')
                               ->orWhere('l_name', 'like', '%' . $request->search . '%')
                               ->orWhere('email', 'like', '%' . $request->search . '%');
                  });
            });
        }

        $bookings = $query->latest()->paginate(15);
        
        // Statistics for monitoring
        $totalBookings = HotelBooking::count();
        $pendingBookings = HotelBooking::where('booking_status', 'pending')->count();
        $confirmedBookings = HotelBooking::where('booking_status', 'confirmed')->count();
        $cancelledBookings = HotelBooking::where('booking_status', 'cancelled')->count();
        $totalRevenue = HotelBooking::where('payment_status', 'paid')->sum('total_price');
        
        $hotels = Hotel::where('status', 1)->get();

        return view('admin-views.hotel-management.bookings.index', compact(
            'bookings', 
            'totalBookings', 
            'pendingBookings', 
            'confirmedBookings', 
            'cancelledBookings', 
            'totalRevenue',
            'hotels'
        ));
    }

    /**
     * View pending bookings
     */
    public function pending(Request $request)
    {
        $query = HotelBooking::with(['hotel', 'customer'])
                            ->where('booking_status', 'pending');

        if ($request->has('search')) {
            $query->where('booking_number', 'like', '%' . $request->search . '%');
        }

        $bookings = $query->latest()->paginate(15);
        return view('admin-views.hotel-management.bookings.pending', compact('bookings'));
    }

    /**
     * View confirmed bookings
     */
    public function confirmed(Request $request)
    {
        $query = HotelBooking::with(['hotel', 'customer'])
                            ->where('booking_status', 'confirmed');

        if ($request->has('search')) {
            $query->where('booking_number', 'like', '%' . $request->search . '%');
        }

        $bookings = $query->latest()->paginate(15);
        return view('admin-views.hotel-management.bookings.confirmed', compact('bookings'));
    }

    /**
     * View cancelled bookings
     */
    public function cancelled(Request $request)
    {
        $query = HotelBooking::with(['hotel', 'customer'])
                            ->where('booking_status', 'cancelled');

        if ($request->has('search')) {
            $query->where('booking_number', 'like', '%' . $request->search . '%');
        }

        $bookings = $query->latest()->paginate(15);
        return view('admin-views.hotel-management.bookings.cancelled', compact('bookings'));
    }

    /**
     * View single booking details
     */
    public function view($id)
    {
        $booking = HotelBooking::with(['hotel', 'customer', 'roomType', 'bookingRooms'])
                               ->findOrFail($id);
        
        return view('admin-views.hotel-management.bookings.booking-details', compact('booking'));
    }

    /**
     * Cancel booking (if required)
     */
    public function cancel(Request $request, $id)
    {
        $request->validate([
            'cancellation_reason' => 'required|string|max:500'
        ]);

        $booking = HotelBooking::findOrFail($id);

        // Check if booking can be cancelled
        if (in_array($booking->booking_status, ['cancelled', 'checked_out'])) {
            Toastr::error(translate('booking_cannot_be_cancelled'));
            return back();
        }

        $booking->booking_status = 'cancelled';
        $booking->cancellation_reason = $request->cancellation_reason;
        $booking->cancelled_by = auth('admin')->id();
        $booking->cancelled_at = now();
        
        // Process refund if payment was made
        if ($booking->payment_status == 'paid') {
            $booking->refund_status = 'pending';
            $booking->refund_amount = $booking->total_price;
            // You can implement refund logic here
        }
        
        $booking->save();

        // Update room inventory
        $this->updateInventoryAfterCancellation($booking);

        Toastr::success(translate('booking_cancelled_successfully'));
        return back();
    }

    /**
     * Update booking status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'booking_status' => 'required|in:pending,confirmed,checked_in,checked_out,cancelled,no_show'
        ]);

        $booking = HotelBooking::findOrFail($id);
        $oldStatus = $booking->booking_status;
        $booking->booking_status = $request->booking_status;

        if ($request->booking_status == 'checked_in') {
            $booking->checked_in_at = now();
        } elseif ($request->booking_status == 'checked_out') {
            $booking->checked_out_at = now();
        } elseif ($request->booking_status == 'cancelled' && $oldStatus != 'cancelled') {
            $booking->cancelled_by = auth('admin')->id();
            $booking->cancelled_at = now();
            
            if ($request->has('cancellation_reason')) {
                $booking->cancellation_reason = $request->cancellation_reason;
            }
        }

        $booking->save();

        Toastr::success(translate('booking_status_updated_successfully'));
        return back();
    }

    /**
     * Export bookings
     */
    public function export(Request $request)
    {
        // Implementation for export
    }

    /**
     * Update inventory after cancellation
     */
    private function updateInventoryAfterCancellation($booking)
    {
        // Logic to update room inventory
    }
}