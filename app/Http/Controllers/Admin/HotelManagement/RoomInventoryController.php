<?php

namespace App\Http\Controllers\Admin\HotelManagement;

use App\Http\Controllers\Controller;
use App\Models\RoomInventory;
use App\Models\Hotel;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;

class RoomInventoryController extends Controller
{
    /**
     * Manage room inventory visibility
     */
    public function index(Request $request)
    {
        $hotels = Hotel::where('status', 1)->get();
        
        $selectedHotelId = $request->hotel_id ?? ($hotels->first()->id ?? null);
        
        $roomTypes = RoomType::where('hotel_id', $selectedHotelId)
                            ->where('status', 1)
                            ->get();

        // Get inventory for selected date range
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::today();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::today()->addDays(30);

        $inventory = RoomInventory::where('hotel_id', $selectedHotelId)
                                  ->whereBetween('date', [$startDate, $endDate])
                                  ->get()
                                  ->groupBy('room_type_id');

        return view('admin-views.hotel-management.room-inventory.index', compact('hotels', 'roomTypes', 'inventory', 'selectedHotelId', 'startDate', 'endDate'));
    }

    /**
     * Calendar view for inventory
     */
    public function calendar($hotel_id)
    {
        $hotel = Hotel::findOrFail($hotel_id);
        $roomTypes = RoomType::where('hotel_id', $hotel_id)->get();

        return view('admin-views.hotel-management.room-inventory.calendar', compact('hotel', 'roomTypes'));
    }

    /**
     * Update room visibility (available/blocked)
     */
    public function updateVisibility(Request $request, $id)
    {
        $request->validate([
            'is_available' => 'required|boolean',
            'date' => 'required|date'
        ]);

        $inventory = RoomInventory::findOrFail($id);
        $inventory->is_available = $request->is_available;
        
        if (!$request->is_available) {
            $inventory->blocked_rooms = $inventory->total_rooms;
        } else {
            $inventory->blocked_rooms = 0;
        }
        
        $inventory->save();

        return response()->json(['success' => true]);
    }

    /**
     * Bulk update inventory
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'hotel_id' => 'required|exists:hotels,id',
            'room_type_id' => 'required|exists:room_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'action' => 'required|in:block,unblock,update_price',
            'price' => 'required_if:action,update_price|numeric|min:0'
        ]);

        $dates = Carbon::parse($request->start_date)->daysUntil($request->end_date);

        foreach ($dates as $date) {
            $inventory = RoomInventory::firstOrNew([
                'hotel_id' => $request->hotel_id,
                'room_type_id' => $request->room_type_id,
                'date' => $date->format('Y-m-d')
            ]);

            $roomType = RoomType::find($request->room_type_id);
            
            if (!$inventory->exists) {
                $inventory->total_rooms = $roomType->total_rooms;
                $inventory->available_rooms = $roomType->total_rooms;
                $inventory->booked_rooms = 0;
                $inventory->blocked_rooms = 0;
                $inventory->price = $roomType->base_price;
            }

            switch ($request->action) {
                case 'block':
                    $inventory->is_available = false;
                    $inventory->blocked_rooms = $inventory->total_rooms;
                    break;
                case 'unblock':
                    $inventory->is_available = true;
                    $inventory->blocked_rooms = 0;
                    break;
                case 'update_price':
                    $inventory->price = $request->price;
                    break;
            }

            $inventory->save();
        }

        Toastr::success(translate('inventory_updated_successfully'));
        return back();
    }
}