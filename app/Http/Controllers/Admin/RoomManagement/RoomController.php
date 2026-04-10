<?php

namespace App\Http\Controllers\Admin\RoomManagement;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class RoomController extends Controller
{
    private function storeRoomImage($file)
    {
        $directory = public_path('storage/app/hotel/hotel');

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $fileName = uniqid() . time() . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $fileName);

        return 'storage/app/hotel/hotel/' . $fileName;
    }


    public function all()
    {
        $rooms = Room::with('hotel')
            ->latest()
            ->paginate(20);

        return view('admin-views.rooms.index', compact('rooms'));
    }


    public function create()
    {
        $hotels = Hotel::where('status', 1)->orderBy('name')->get();

        return view('admin-views.rooms.create', compact('hotels'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'hotel_id' => 'required|exists:hotels,id',
            'room_type' => 'required|string|max:100',
            'single_price' => 'nullable|numeric|min:0',
            'double_price' => 'nullable|numeric|min:0',
            'featured_image' => 'nullable|file|mimes:jpg,jpeg,png,webp,jfif,gif,avif|max:5120',
            'gallery.*' => 'nullable|file|mimes:jpg,jpeg,png,webp,jfif,gif,avif|max:5120',
        ]);

        $room = new Room();

        $room->hotel_id = $request->hotel_id;
        $room->room_type = $request->room_type;
        $room->room_size = $request->room_size;
        $room->rooms_available = $request->rooms_available ?? 0;

        // Pricing
        $room->single_price = $request->single_price;
        $room->single_sale_price = $request->single_sale_price;
        $room->double_price = $request->double_price;
        $room->double_sale_price = $request->double_sale_price;

        $room->extra_adult_price = $request->extra_adult_price;
        $room->extra_child_price = $request->extra_child_price;
        $room->gst = $request->gst ?? 0;

        // Occupancy
        $room->max_adults = $request->max_adults ?? 1;
        $room->max_children = $request->max_children ?? 0;

        // Attributes
        // ✅ Attributes (ALWAYS store array)
        $room->attributes = json_encode($request->input('attributes', []));

        // Featured Image
        if ($request->hasFile('featured_image')) {
            $room->featured_image = $this->storeRoomImage($request->file('featured_image'));
        }

        // Gallery Images
        if ($request->hasFile('gallery')) {
            $gallery = [];
            foreach ($request->file('gallery') as $image) {
                $gallery[] = $this->storeRoomImage($image);
            }
            $room->gallery = json_encode($gallery);
        }

        $room->status = 1;
        $room->save();

        return redirect()
            ->route('admin.rooms.all')
            ->with('success', translate('room_added_successfully'));
    }


    public function view($id)
    {
        $room = Room::with('hotel')->findOrFail($id);
        return view('admin-views.rooms.view', compact('room'));
    }


    public function edit($id)
    {
        $room = Room::findOrFail($id);
        return view('admin-views.rooms.edit', compact('room'));
    }


    public function update(Request $request, $id)
    {
        $room = Room::findOrFail($id);

        $request->validate([
            'room_type' => 'required|string|max:100',
            'single_price' => 'nullable|numeric|min:0',
            'double_price' => 'nullable|numeric|min:0',
            'featured_image' => 'nullable|file|mimes:jpg,jpeg,png,webp,jfif,gif,avif|max:5120',
            'gallery.*' => 'nullable|file|mimes:jpg,jpeg,png,webp,jfif,gif,avif|max:5120',
        ]);

        $room->room_type = $request->room_type;
        $room->room_size = $request->room_size;
        $room->rooms_available = $request->rooms_available;

        $room->single_price = $request->single_price;
        $room->single_sale_price = $request->single_sale_price;
        $room->double_price = $request->double_price;
        $room->double_sale_price = $request->double_sale_price;

        $room->extra_adult_price = $request->extra_adult_price;
        $room->extra_child_price = $request->extra_child_price;
        $room->gst = $request->gst;

        $room->max_adults = $request->max_adults;
        $room->max_children = $request->max_children;

        $room->attributes = json_encode($request->input('attributes', []));

        // Replace featured image
        if ($request->hasFile('featured_image')) {
            $room->featured_image = $this->storeRoomImage($request->file('featured_image'));
        }

        // Replace gallery
        if ($request->hasFile('gallery')) {
            $gallery = [];
            foreach ($request->file('gallery') as $image) {
                $gallery[] = $this->storeRoomImage($image);
            }
            $room->gallery = json_encode($gallery);
        }

        $room->save();

        return redirect()
            ->route('admin.rooms.all')
            ->with('success', translate('room_updated_successfully'));
    }


    public function updateStatus($id)
    {
        $room = Room::findOrFail($id);
        $room->status = !$room->status;
        $room->save();

        return back()->with('success', translate('status_updated'));
    }


    public function delete($id)
    {
        $room = Room::findOrFail($id);
        $room->delete();

        return back()->with('success', translate('room_deleted'));
    }
}
