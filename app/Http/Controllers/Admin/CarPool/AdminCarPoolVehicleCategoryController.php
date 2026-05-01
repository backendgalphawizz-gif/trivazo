<?php

namespace App\Http\Controllers\Admin\CarPool;

use App\Http\Controllers\Controller;
use App\Models\CarPoolDriver;
use App\Models\CarPoolVehicleCategory;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AdminCarPoolVehicleCategoryController extends Controller
{
    public function index(): View
    {
        $categories = CarPoolVehicleCategory::query()
            ->withCount('drivers')
            ->orderBy('name')
            ->paginate((int) (getWebConfig(name: 'pagination_limit') ?: 25));

        return view('admin-views.carpool.vehicle-categories.list', compact('categories'));
    }

    public function create(): View
    {
        return view('admin-views.carpool.vehicle-categories.add');
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:carpool_vehicle_categories,name',
        ]);

        if ($validator->fails()) {
            Toastr::error(translate('Please fix the validation errors.'));

            return back()->withErrors($validator)->withInput();
        }

        CarPoolVehicleCategory::create([
            'name'      => trim($request->input('name')),
            'is_active' => $request->boolean('is_active'),
        ]);

        Toastr::success(translate('Vehicle category saved.'));

        return redirect()->route('admin.carpool.vehicle-categories.list');
    }

    public function edit(int $id): View|RedirectResponse
    {
        $category = CarPoolVehicleCategory::find($id);
        if (!$category) {
            Toastr::error(translate('Category not found.'));

            return redirect()->route('admin.carpool.vehicle-categories.list');
        }

        return view('admin-views.carpool.vehicle-categories.edit', compact('category'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $category = CarPoolVehicleCategory::find($id);
        if (!$category) {
            Toastr::error(translate('Category not found.'));

            return redirect()->route('admin.carpool.vehicle-categories.list');
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('carpool_vehicle_categories', 'name')->ignore($id),
            ],
        ]);

        if ($validator->fails()) {
            Toastr::error(translate('Please fix the validation errors.'));

            return back()->withErrors($validator)->withInput();
        }

        $category->update([
            'name'       => trim($request->input('name')),
            'is_active'  => $request->boolean('is_active'),
        ]);

        CarPoolDriver::query()
            ->where('vehicle_category_id', $category->id)
            ->update(['vehicle_type' => $category->fresh()->name]);

        Toastr::success(translate('Vehicle category updated.'));

        return redirect()->route('admin.carpool.vehicle-categories.list');
    }

    public function toggleActive(int $id): RedirectResponse
    {
        $category = CarPoolVehicleCategory::find($id);
        if (!$category) {
            Toastr::error(translate('Category not found.'));

            return redirect()->route('admin.carpool.vehicle-categories.list');
        }

        $nowActive = !$category->is_active;
        $category->update(['is_active' => $nowActive]);

        Toastr::success(
            $nowActive
                ? translate('Vehicle category activated.')
                : translate('Vehicle category deactivated.')
        );

        return back();
    }

    public function destroy(int $id): RedirectResponse
    {
        $category = CarPoolVehicleCategory::find($id);
        if (!$category) {
            Toastr::error(translate('Category not found.'));

            return redirect()->route('admin.carpool.vehicle-categories.list');
        }

        if ($category->drivers()->exists()) {
            Toastr::error(translate('cannot_delete_vehicle_category_in_use'));

            return back();
        }

        $category->delete();

        Toastr::success(translate('Category deleted.'));

        return redirect()->route('admin.carpool.vehicle-categories.list');
    }
}
