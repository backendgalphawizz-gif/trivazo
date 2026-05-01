<?php

namespace App\Http\Controllers\RestAPI\v1\CarPool\Concerns;

use App\Models\CarPoolDriver;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait ResolvesCarpoolDriverFromUser
{
    /**
     * CarPool driver row for the authenticated store user (same phone as `users`).
     */
    protected function carpoolDriverFromUser(Request $request): CarPoolDriver|JsonResponse
    {
        $user = $request->user();
        if (!$user instanceof User) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated.'], 401);
        }
        if (!$user->phone) {
            return response()->json(['status' => false, 'message' => 'User account has no phone number.'], 422);
        }

        $driver = CarPoolDriver::query()->where('phone', $user->phone)->first();
        if (!$driver) {
            return response()->json(['status' => false, 'message' => 'Not registered as a driver.'], 403);
        }
        if ($driver->status !== 'active') {
            return response()->json(['status' => false, 'message' => 'Account is not active.'], 403);
        }

        return $driver;
    }
}
