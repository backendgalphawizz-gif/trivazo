<?php

namespace App\Http\Controllers\RestAPI\v1\CarPool;

use App\Http\Controllers\Controller;
use App\Models\CarPoolVehicleCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleCategoryController extends Controller
{
    /**
     * GET /api/v1/carpool/vehicle-categories?active_only=1
     */
    public function index(Request $request): JsonResponse
    {
        $q = CarPoolVehicleCategory::query()->orderBy('name');

        if (!$request->has('active_only') || $request->boolean('active_only')) {
            $q->where('is_active', true);
        }

        $categories = $q->get(['id', 'name']);

        return response()->json([
            'status'     => true,
            'categories' => $categories,
        ]);
    }
}
