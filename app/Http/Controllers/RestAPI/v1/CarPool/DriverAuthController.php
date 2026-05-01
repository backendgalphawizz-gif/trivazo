<?php

namespace App\Http\Controllers\RestAPI\v1\CarPool;

use App\Http\Controllers\RestAPI\v1\CarPool\Concerns\ResolvesCarpoolDriverFromUser;
use App\Http\Controllers\Controller;
use App\Models\CarPoolDriver;
use App\Models\CarPoolVehicleCategory;
use App\Repositories\CarPoolDriverRepository;
use App\Services\CarPoolDriverCustomerSyncService;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DriverAuthController extends Controller
{
    use ResolvesCarpoolDriverFromUser;

    public function __construct(
        private readonly CarPoolDriverRepository $driverRepo,
        private readonly CarPoolDriverCustomerSyncService $driverCustomerSync,
    ) {}

    /**
     * Register as driver (after customer account exists).
     * Requires Bearer token from customer login (`auth:api`). Password is copied from `users.password` (no password field in body).
     */
    public function register(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (!$user->phone) {
            return response()->json(['status' => false, 'message' => 'User account has no phone number.'], 422);
        }

        if (CarPoolDriver::query()->where('phone', $user->phone)->exists()) {
            return response()->json(['status' => false, 'message' => 'This account is already registered as a driver.'], 409);
        }

        $validator = Validator::make($request->all(), [
            'gender'               => 'nullable|in:male,female,other',
            'vehicle_category_id'  => [
                'nullable',
                'integer',
                Rule::exists('carpool_vehicle_categories', 'id')->where('is_active', 1),
            ],
            'vehicle_category_name' => 'nullable|string|max:100',
            'vehicle_number'      => 'required|string|max:30',
            'vehicle_model'     => 'required|string|max:100',
            'vehicle_color'     => 'required|string|max:50',
            'vehicle_capacity'  => 'required|integer|min:1|max:20',
            'license_number'    => 'required|string|max:50',
            'license_doc'       => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'vehicle_image'     => 'required|image|mimes:jpg,jpeg,png|max:3072',
            'profile_image'     => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $validator->after(function ($validator) use ($request) {
            $hasId = $request->filled('vehicle_category_id');
            $hasName = $request->filled('vehicle_category_name');
            if (!$hasId && !$hasName) {
                $validator->errors()->add(
                    'vehicle_category_id',
                    'Provide vehicle_category_id (from GET /carpool/vehicle-categories) or vehicle_category_name (exact category name).'
                );
            }
        });

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $vehicleCategory = $this->resolveActiveVehicleCategory($request);
        if (!$vehicleCategory) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid vehicle category. Check vehicle_category_id or vehicle_category_name (active category only).',
            ], 422);
        }

        $email = $user->email ? trim((string) $user->email) : null;
        if ($email !== null && $email !== '' && CarPoolDriver::query()->where('email', $email)->exists()) {
            return response()->json(['status' => false, 'message' => 'This email is already used on another driver account.'], 422);
        }

        $name = trim((string) ($user->name ?? ''));
        if ($name === '') {
            $name = trim(trim((string) ($user->f_name ?? '')) . ' ' . trim((string) ($user->l_name ?? '')));
        }
        if ($name === '') {
            $name = 'User';
        }

        $driver = DB::transaction(function () use ($request, $validator, $user, $name, $email, $vehicleCategory) {
            $data = collect($validator->validated())
                ->except(['license_doc', 'vehicle_image', 'profile_image', 'vehicle_category_id', 'vehicle_category_name'])
                ->all();

            $g = $data['gender'] ?? null;
            if ($g === null || $g === '') {
                $ug = $user->gender ?? '';
                $data['gender'] = in_array((string) $ug, ['male', 'female', 'other'], true) ? $ug : null;
            }

            $payload = array_merge($data, [
                'name'                 => $name,
                'phone'                => $user->phone,
                'email'                => $email !== '' ? $email : null,
                'country_code'         => $user->country_code ?: '+91',
                'password'             => $user->password,
                'vehicle_category_id'  => $vehicleCategory->id,
                'vehicle_type'         => $vehicleCategory->name,
                'license_doc'    => $request->file('license_doc')->store('carpool/drivers/docs', 'public'),
                'vehicle_image'  => $request->file('vehicle_image')->store('carpool/drivers/vehicles', 'public'),
                'is_verified'    => false,
                'status'         => 'active',
            ]);

            if ($request->hasFile('profile_image')) {
                $payload['profile_image'] = $request->file('profile_image')->store('carpool/drivers/photos', 'public');
            }

            $driver = $this->driverRepo->add($payload);
            $this->driverCustomerSync->ensureCustomerUserFromDriver($driver);

            return $driver;
        });

        $driver->refresh();
        $this->driverCustomerSync->syncDriverIdentityFromUser($user, $driver);

        return response()->json([
            'status'  => true,
            'message' => 'Registration successful. Awaiting verification. Use the same Bearer token as customer login.',
            'driver'  => $this->formatDriver($driver->fresh(['wallet', 'vehicleCategory']), $user),
        ], 200);
    }

    /**
     * Uses the same password as the customer `users` row for this phone.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone'    => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $user = User::query()->where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['status' => false, 'message' => 'Invalid credentials.'], 401);
        }

        $driver = $this->driverRepo->findByPhone($request->phone);

        if (!$driver) {
            return response()->json(['status' => false, 'message' => 'Not registered as a driver.'], 404);
        }

        if ($driver->status !== 'active') {
            return response()->json(['status' => false, 'message' => 'Account is not active.'], 403);
        }

        if ($request->filled('fcm_token')) {
            $driver->update(['fcm_token' => $request->fcm_token]);
        }

        $this->driverCustomerSync->syncDriverIdentityFromUser($user, $driver);

        $token = $user->createToken('LaravelAuthApp')->accessToken;

        return response()->json([
            'status' => true,
            'token'  => $token,
            'driver' => $this->formatDriver($driver->fresh(['wallet', 'vehicleCategory']), $user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->token()->revoke();

        return response()->json(['status' => true, 'message' => 'Logged out successfully.']);
    }

    public function profile(Request $request): JsonResponse
    {
        /** @var User $authUser */
        $authUser = $request->user();
        $driver = $this->carpoolDriverFromUser($request);
        if ($driver instanceof JsonResponse) {
            return $driver;
        }

        $this->driverCustomerSync->syncDriverIdentityFromUser($authUser, $driver);
        $driver->refresh()->load(['wallet', 'vehicleCategory']);

        return response()->json([
            'status' => true,
            'driver' => $this->formatDriver($driver, $authUser),
        ]);
    }

    /**
     * Update driver profile (JSON and/or files).
     * Use **POST** with `multipart/form-data` when uploading `license_doc`, `vehicle_image`, or `profile_image`
     * — PHP/Laravel often do not receive files on **PUT** multipart.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $driver = $this->carpoolDriverFromUser($request);
        if ($driver instanceof JsonResponse) {
            return $driver;
        }

        $this->driverCustomerSync->syncDriverIdentityFromUser($request->user(), $driver);
        $driver->refresh();

        /** @var User $authUser */
        $authUser = $request->user();

        // PHP / Symfony often do not populate multipart fields on PUT — body looks empty → no DB update but 200 still returned.
        $contentType = strtolower((string) $request->header('Content-Type', ''));
        if ($request->isMethod('PUT') && str_contains($contentType, 'multipart/form-data')) {
            return response()->json([
                'status'  => false,
                'message' => 'Do not use PUT with multipart/form-data. Use POST /api/v1/carpool/driver/profile with the same Bearer token and the same form fields (PHP does not reliably parse PUT multipart, so nothing was saved).',
            ], 422);
        }

        if ($request->filled('phone') && trim((string) $request->input('phone')) !== (string) $driver->phone) {
            return response()->json([
                'status'  => false,
                'message' => 'Phone cannot be changed from driver profile; it must stay the same as your logged-in account.',
            ], 422);
        }

        // Clients often send `vehicle_type` (label) — treat like `vehicle_category_name` for category resolution.
        if ($request->filled('vehicle_type')
            && !$request->filled('vehicle_category_name')
            && !$request->filled('vehicle_category_id')) {
            $request->merge(['vehicle_category_name' => trim((string) $request->input('vehicle_type'))]);
        }

        $validator = Validator::make($request->all(), [
            'name'              => 'sometimes|string|max:100',
            'country_code'      => 'sometimes|nullable|string|max:12',
            'email'             => [
                'sometimes',
                'nullable',
                'email',
                Rule::unique('carpool_drivers', 'email')->ignore($driver->id),
                Rule::unique('users', 'email')->ignore($authUser->id),
            ],
            'gender'               => 'sometimes|nullable|in:male,female,other',
            'vehicle_category_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('carpool_vehicle_categories', 'id')->where('is_active', 1),
            ],
            'vehicle_category_name' => 'sometimes|nullable|string|max:100',
            'vehicle_number'       => 'sometimes|string|max:30',
            'vehicle_model'     => 'sometimes|string|max:100',
            'vehicle_color'     => 'sometimes|string|max:50',
            'vehicle_capacity'  => 'sometimes|integer|min:1|max:20',
            'license_number'    => 'sometimes|string|max:50',
            'fcm_token'         => 'sometimes|string',
            'is_online'         => 'sometimes|boolean',
            // `nullable` so JSON clients may send null placeholders; real binaries only via multipart (prefer POST profile).
            'license_doc'       => 'sometimes|nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'vehicle_image'     => 'sometimes|nullable|image|mimes:jpg,jpeg,png|max:3072',
            'profile_image'     => 'sometimes|nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $data = collect($validator->validated())
            ->except(['license_doc', 'vehicle_image', 'profile_image', 'vehicle_category_id', 'vehicle_category_name'])
            ->all();

        $hasCategoryPayload = $request->filled('vehicle_category_id') || $request->filled('vehicle_category_name');

        if ($hasCategoryPayload) {
            $cat = $this->resolveActiveVehicleCategory($request);
            if (!$cat) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid vehicle category. Send vehicle_category_id or vehicle_category_name (active only). If both sent, id is used.',
                ], 422);
            }
            $data['vehicle_category_id'] = $cat->id;
            $data['vehicle_type'] = $cat->name;
        }

        if ($request->hasFile('license_doc')) {
            if ($driver->license_doc) {
                Storage::disk('public')->delete($driver->license_doc);
            }
            $data['license_doc'] = $request->file('license_doc')->store('carpool/drivers/docs', 'public');
        }
        if ($request->hasFile('vehicle_image')) {
            if ($driver->vehicle_image) {
                Storage::disk('public')->delete($driver->vehicle_image);
            }
            $data['vehicle_image'] = $request->file('vehicle_image')->store('carpool/drivers/vehicles', 'public');
        }
        if ($request->hasFile('profile_image')) {
            if ($driver->profile_image) {
                Storage::disk('public')->delete($driver->profile_image);
            }
            $data['profile_image'] = $request->file('profile_image')->store('carpool/drivers/photos', 'public');
        }

        if ($data !== []) {
            $driver->update($data);
        }

        $driver->refresh();
        $this->driverCustomerSync->syncLinkedCustomerFromDriver($driver);

        $authUser->refresh();

        return response()->json([
            'status'  => true,
            'message' => 'Profile updated successfully.',
            'driver'  => $this->formatDriver($driver->fresh(['wallet', 'vehicleCategory']), $authUser),
        ]);
    }

    /**
     * Active category lookup: prefers vehicle_category_id when present; otherwise vehicle_category_name (case-insensitive).
     */
    private function resolveActiveVehicleCategory(Request $request): ?CarPoolVehicleCategory
    {
        if ($request->filled('vehicle_category_id')) {
            return CarPoolVehicleCategory::query()
                ->where('id', (int) $request->input('vehicle_category_id'))
                ->where('is_active', true)
                ->first();
        }

        if (!$request->filled('vehicle_category_name')) {
            return null;
        }

        $label = trim((string) $request->input('vehicle_category_name'));

        return CarPoolVehicleCategory::query()
            ->whereRaw('LOWER(name) = LOWER(?)', [$label])
            ->where('is_active', true)
            ->first();
    }

    private function formatDriver(CarPoolDriver $driver, ?User $user = null): array
    {
        $userBlock = null;
        if ($user instanceof User) {
            $userBlock = [
                'id'            => $user->id,
                'name'          => $user->name,
                'f_name'        => $user->f_name,
                'l_name'        => $user->l_name,
                'email'         => $user->email,
                'phone'         => $user->phone,
                'country_code'  => $user->country_code ?: '+91',
                'image_full_url' => $user->image_full_url ?? null,
            ];
        }

        return [
            'id'                      => $driver->id,
            'name'                    => $driver->name,
            'phone'                   => $driver->phone,
            'country_code'            => $driver->country_code ?: '+91',
            'email'                   => $driver->email,
            'gender'                  => $driver->gender,
            'license_number'          => $driver->license_number,
            'license_doc_url'         => $driver->license_doc
                ? Storage::disk('public')->url($driver->license_doc)
                : null,
            'profile_image_url'       => $driver->profile_image
                ? Storage::disk('public')->url($driver->profile_image)
                : null,
            'vehicle_category_id'      => $driver->vehicle_category_id,
            'vehicle_type'             => $driver->vehicle_type,
            'vehicle_category'         => $driver->vehicleCategory
                ? ['id' => $driver->vehicleCategory->id, 'name' => $driver->vehicleCategory->name]
                : null,
            'vehicle_number'          => $driver->vehicle_number,
            'vehicle_model'           => $driver->vehicle_model,
            'vehicle_color'           => $driver->vehicle_color,
            'vehicle_capacity'        => $driver->vehicle_capacity,
            'vehicle_image_url'       => $driver->vehicle_image
                ? Storage::disk('public')->url($driver->vehicle_image)
                : null,
            'status'                  => $driver->status,
            'is_verified'             => $driver->is_verified,
            'is_online'               => (bool) $driver->is_online,
            'rating'                  => $driver->rating,
            'total_completed_rides'   => $driver->total_completed_rides,
            'wallet'                  => $driver->wallet ? [
                'available_balance' => $driver->wallet->available_balance,
                'pending_balance'   => $driver->wallet->pending_balance,
                'total_earned'        => $driver->wallet->total_earned,
                'total_withdrawn'     => $driver->wallet->total_withdrawn,
            ] : null,
            'user'                    => $userBlock,
        ];
    }
}
