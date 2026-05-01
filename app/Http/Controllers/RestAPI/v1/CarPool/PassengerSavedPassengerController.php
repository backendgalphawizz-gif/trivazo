<?php

namespace App\Http\Controllers\RestAPI\v1\CarPool;

use App\Http\Controllers\Controller;
use App\Models\CarPoolSavedPassenger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PassengerSavedPassengerController extends Controller
{
    /**
     * GET /api/v1/carpool/passenger/saved-passengers
     * List all saved passengers for the logged-in user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user('api');

        $passengers = CarPoolSavedPassenger::where('user_id', $user->id)
            ->orderBy('name')
            ->get();

        return response()->json([
            'status'     => true,
            'total'      => $passengers->count(),
            'passengers' => $passengers,
        ]);
    }

    /**
     * POST /api/v1/carpool/passenger/saved-passengers
     * Add a single passenger to the master list.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user('api');

        $validator = Validator::make($request->all(), [
            'name'   => 'required|string|max:100',
            'phone'  => 'nullable|string|max:20',
            'gender' => 'nullable|string|in:male,female,other',
            'age'    => 'nullable|integer|min:1|max:120',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $passenger = CarPoolSavedPassenger::create([
            'user_id' => $user->id,
            'name'    => $validator->validated()['name'],
            'phone'   => $validator->validated()['phone'] ?? null,
            'gender'  => $validator->validated()['gender'] ?? null,
            'age'     => $validator->validated()['age'] ?? null,
        ]);

        return response()->json([
            'status'    => true,
            'message'   => 'Passenger added to your list.',
            'passenger' => $passenger,
        ], 201);
    }

    /**
     * PUT /api/v1/carpool/passenger/saved-passengers/{id}
     * Update a saved passenger's details.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user      = $request->user('api');
        $passenger = CarPoolSavedPassenger::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$passenger) {
            return response()->json(['status' => false, 'message' => 'Passenger not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'   => 'sometimes|required|string|max:100',
            'phone'  => 'nullable|string|max:20',
            'gender' => 'nullable|string|in:male,female,other',
            'age'    => 'nullable|integer|min:1|max:120',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $passenger->update($validator->validated());

        return response()->json([
            'status'    => true,
            'message'   => 'Passenger updated.',
            'passenger' => $passenger->fresh(),
        ]);
    }

    /**
     * DELETE /api/v1/carpool/passenger/saved-passengers/{id}
     * Remove a saved passenger from the master list.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user      = $request->user('api');
        $passenger = CarPoolSavedPassenger::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$passenger) {
            return response()->json(['status' => false, 'message' => 'Passenger not found.'], 404);
        }

        $passenger->delete();

        return response()->json(['status' => true, 'message' => 'Passenger removed from your list.']);
    }
}
