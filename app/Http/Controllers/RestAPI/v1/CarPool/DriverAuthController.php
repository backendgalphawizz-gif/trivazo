<?php

namespace App\Http\Controllers\RestAPI\v1\CarPool;

use App\Http\Controllers\Controller;
use App\Models\CarPoolDriver;
use App\Repositories\CarPoolDriverRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class DriverAuthController extends Controller
{
    public function __construct(private readonly CarPoolDriverRepository $driverRepo) {}

    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'             => 'required|string|max:100',
            'phone'            => 'required|string|unique:carpool_drivers,phone',
            'email'            => 'nullable|email|unique:carpool_drivers,email',
            'password'         => 'required|string|min:6',
            'vehicle_type'     => 'required|string|max:50',
            'vehicle_number'   => 'required|string|max:30',
            'vehicle_model'    => 'required|string|max:100',
            'vehicle_color'    => 'required|string|max:50',
            'vehicle_capacity' => 'required|integer|min:1|max:20',
            'license_number'   => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $driver = $this->driverRepo->add(array_merge(
            $validator->validated(),
            ['password' => Hash::make($request->password)]
        ));

        $token = $driver->createToken('carpool-driver')->accessToken;

        return response()->json([
            'status'  => true,
            'message' => 'Registration successful. Awaiting verification.',
            'token'   => $token,
            'driver'  => $this->formatDriver($driver),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone'    => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $driver = $this->driverRepo->findByPhone($request->phone);

        if (!$driver || !Hash::check($request->password, $driver->password)) {
            return response()->json(['status' => false, 'message' => 'Invalid credentials.'], 401);
        }

        if ($driver->status !== 'active') {
            return response()->json(['status' => false, 'message' => 'Account is not active.'], 403);
        }

        // Persist device/fcm token if provided.
        if ($request->filled('fcm_token')) {
            $driver->update(['fcm_token' => $request->fcm_token]);
        }

        $token = $driver->createToken('carpool-driver')->accessToken;

        return response()->json([
            'status' => true,
            'token'  => $token,
            'driver' => $this->formatDriver($driver->fresh('wallet')),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user('carpool_driver')->token()->revoke();

        return response()->json(['status' => true, 'message' => 'Logged out successfully.']);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'driver' => $this->formatDriver($request->user('carpool_driver')->load('wallet')),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $driver = $request->user('carpool_driver');

        $validator = Validator::make($request->all(), [
            'name'             => 'sometimes|string|max:100',
            'vehicle_type'     => 'sometimes|string|max:50',
            'vehicle_number'   => 'sometimes|string|max:30',
            'vehicle_model'    => 'sometimes|string|max:100',
            'vehicle_color'    => 'sometimes|string|max:50',
            'vehicle_capacity' => 'sometimes|integer|min:1|max:20',
            'fcm_token'        => 'sometimes|string',
            'is_online'        => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $this->driverRepo->update($driver->id, $validator->validated());

        return response()->json([
            'status' => true,
            'driver' => $this->formatDriver($driver->fresh('wallet')),
        ]);
    }

    private function formatDriver(CarPoolDriver $driver): array
    {
        return [
            'id'               => $driver->id,
            'name'             => $driver->name,
            'phone'            => $driver->phone,
            'email'            => $driver->email,
            'vehicle_type'     => $driver->vehicle_type,
            'vehicle_number'   => $driver->vehicle_number,
            'vehicle_model'    => $driver->vehicle_model,
            'vehicle_color'    => $driver->vehicle_color,
            'vehicle_capacity' => $driver->vehicle_capacity,
            'status'           => $driver->status,
            'is_verified'      => $driver->is_verified,
            'is_online'        => (bool) $driver->is_online,
            'rating'           => $driver->rating,
            'total_completed_rides' => $driver->total_completed_rides,
            'wallet'           => $driver->wallet ? [
                'available_balance' => $driver->wallet->available_balance,
                'pending_balance'   => $driver->wallet->pending_balance,
                'total_earned'      => $driver->wallet->total_earned,
                'total_withdrawn'   => $driver->wallet->total_withdrawn,
            ] : null,
        ];
    }
}
