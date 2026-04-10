<?php

namespace App\Http\Controllers\RestAPI\v1;

use App\Http\Controllers\Controller;
use App\Models\Amenities;
use App\Models\Hotel;
use App\Models\HotelService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class HotelController extends Controller
{
	private function buildPublicStorageAsset(string $path): string
	{
		return asset('public/' . ltrim($path, '/'));
	}

	private function normalizeStoragePath(?string $value, string $defaultDirectory): ?string
	{
		if (!$value) {
			return null;
		}

		if (filter_var($value, FILTER_VALIDATE_URL)) {
			return $value;
		}

		$path = trim((string) (parse_url($value, PHP_URL_PATH) ?: $value), '/');

		if ($path === '') {
			return null;
		}

		if (str_starts_with($path, 'public/storage/app/')) {
			return $this->buildPublicStorageAsset(substr($path, strlen('public/')));
		}

		if (str_starts_with($path, 'storage/app/')) {
			return $this->buildPublicStorageAsset($path);
		}

		if (str_starts_with($path, 'hotel/')) {
			return $this->buildPublicStorageAsset('storage/app/' . $path);
		}

		return $this->buildPublicStorageAsset(trim($defaultDirectory, '/') . '/' . basename($path));
	}

	private function buildImageUrl(?string $value): ?string
	{
		return $this->normalizeStoragePath($value, 'storage/app/hotel/hotel');
	}

	private function buildStorageUrl(?string $value): ?string
	{
		return $this->normalizeStoragePath($value, 'storage/app/hotel/hotel');
	}

	private function buildServiceImageUrl(?string $value): ?string
	{
		return $this->normalizeStoragePath($value, 'storage/app/hotel/services');
	}

	private function parseJsonArray($value): array
	{
		if (empty($value)) {
			return [];
		}

		if (is_array($value)) {
			return $value;
		}

		if (is_string($value)) {
			$decoded = json_decode($value, true);

			if (json_last_error() === JSON_ERROR_NONE) {
				if (is_array($decoded)) {
					return $decoded;
				}

				if (is_string($decoded)) {
					$decodedAgain = json_decode($decoded, true);

					if (json_last_error() === JSON_ERROR_NONE && is_array($decodedAgain)) {
						return $decodedAgain;
					}
				}
			}

			$value = trim($value, '"');
			$decoded = json_decode(stripslashes($value), true);

			if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
				return $decoded;
			}

			return array_values(array_filter(array_map('trim', explode(',', $value))));
		}

		return [];
	}

	private function nightsCount(?string $checkInDate, ?string $checkOutDate): int
	{
		if (!$checkInDate || !$checkOutDate) {
			return 1;
		}

		$start = Carbon::parse($checkInDate);
		$end = Carbon::parse($checkOutDate);

		return max(1, $start->diffInDays($end));
	}

	private function getAmenitiesPayload()
	{
		return Amenities::where('status', 1)
			->select(['id', 'name', 'category', 'icon'])
			->orderBy('name')
			->get()
			->map(function ($amenity) {
				return [
					'id' => $amenity->id,
					'name' => $amenity->name,
					'category' => $amenity->category,
					'icon' => $this->buildImageUrl($amenity->icon),
				];
			})
			->values();
	}

	private function getHotelServicesPayload(int $hotelId)
	{
		return HotelService::where('hotel_id', $hotelId)
			->where('status', 1)
			->orderBy('sort_order')
			->latest('id')
			->get(['id', 'title', 'short_description', 'image', 'service_type', 'sort_order', 'status'])
			->map(function ($service) {
				return [
					'id' => (int) $service->id,
					'title' => $service->title,
					'short_description' => $service->short_description,
					'image' => $this->buildServiceImageUrl($service->image),
					'service_type' => $service->service_type,
					'sort_order' => (int) ($service->sort_order ?? 0),
					'status' => (int) ($service->status ?? 0),
				];
			})
			->values();
	}

	private function parseFilterValues($value): array
	{
		$values = $this->parseJsonArray($value);

		if (empty($values) && !is_array($value) && !empty($value)) {
			$values = [$value];
		}

		return collect($values)
			->map(fn ($item) => strtolower(trim((string) $item)))
			->filter()
			->values()
			->all();
	}

	private function getHotelTypeColumn(): ?string
	{
		foreach (['hotel_type', 'property_type', 'type'] as $column) {
			if (Schema::hasColumn('hotels', $column)) {
				return $column;
			}
		}

		return null;
	}

	private function getBooleanFilterColumn(string $filter): ?array
	{
		$map = [
			'free_cancellation' => [
				['table' => 'hotels', 'column' => 'free_cancellation'],
				['table' => 'hotels', 'column' => 'is_free_cancellation'],
				['table' => 'hotels', 'column' => 'free_cancelation'],
				['table' => 'rooms', 'column' => 'free_cancellation'],
				['table' => 'rooms', 'column' => 'is_free_cancellation'],
			],
			'breakfast_included' => [
				['table' => 'hotels', 'column' => 'breakfast_included'],
				['table' => 'hotels', 'column' => 'is_breakfast_included'],
				['table' => 'hotels', 'column' => 'breakfast_available'],
				['table' => 'rooms', 'column' => 'breakfast_included'],
				['table' => 'rooms', 'column' => 'is_breakfast_included'],
				['table' => 'rooms', 'column' => 'breakfast_available'],
			],
		];

		foreach ($map[$filter] ?? [] as $candidate) {
			if (Schema::hasColumn($candidate['table'], $candidate['column'])) {
				return $candidate;
			}
		}

		return null;
	}

	private function resolveBooleanLikeValue($value): ?bool
	{
		if ($value === null || $value === '') {
			return null;
		}

		if (is_bool($value)) {
			return $value;
		}

		$value = strtolower(trim((string) $value));

		if (in_array($value, ['1', 'true', 'yes', 'on'], true)) {
			return true;
		}

		if (in_array($value, ['0', 'false', 'no', 'off'], true)) {
			return false;
		}

		return null;
	}

	private function extractRoomBooleanFlag(int $hotelId, string $column): bool
	{
		return DB::table('rooms')
			->where('hotel_id', $hotelId)
			->where('status', 1)
			->where($column, 1)
			->exists();
	}

	private function getHotelTextTokens(Hotel $hotel): array
	{
		$roomTokens = $this->getHotelRooms($hotel->id)
			->flatMap(function ($room) {
				return array_merge(
					$this->parseFilterValues($room->attributes ?? null),
					$this->parseFilterValues($room->room_type ?? null)
				);
			})
			->values();

		$text = strtolower(trim(implode(' ', array_filter([
			(string) $hotel->name,
			(string) $hotel->description,
			(string) $hotel->address,
		]))));

		$textTokens = collect(preg_split('/[^a-z0-9]+/', $text ?: '') ?: [])
			->filter()
			->values();

		return $roomTokens->merge($textTokens)->unique()->values()->all();
	}

	private function getHotelOptionFlags(Hotel $hotel): array
	{
		$freeCancellationConfig = $this->getBooleanFilterColumn('free_cancellation');
		$breakfastConfig = $this->getBooleanFilterColumn('breakfast_included');
		$textTokens = collect($this->getHotelTextTokens($hotel));

		$freeCancellation = false;
		$breakfastIncluded = false;

		if ($freeCancellationConfig) {
			$freeCancellation = $freeCancellationConfig['table'] === 'rooms'
				? $this->extractRoomBooleanFlag($hotel->id, $freeCancellationConfig['column'])
				: (bool) $this->resolveBooleanLikeValue(data_get($hotel, $freeCancellationConfig['column']));
		}

		if ($breakfastConfig) {
			$breakfastIncluded = $breakfastConfig['table'] === 'rooms'
				? $this->extractRoomBooleanFlag($hotel->id, $breakfastConfig['column'])
				: (bool) $this->resolveBooleanLikeValue(data_get($hotel, $breakfastConfig['column']));
		}

		if (!$freeCancellationConfig) {
			$freeCancellation = $textTokens->contains('refundable')
				|| ($textTokens->contains('free') && ($textTokens->contains('cancellation') || $textTokens->contains('cancelation')));
		}

		if (!$breakfastConfig) {
			$breakfastIncluded = $textTokens->contains('breakfast');
		}

		return [
			'free_cancellation' => $freeCancellation,
			'breakfast_included' => $breakfastIncluded,
		];
	}

	private function getHotelTypeValue(Hotel $hotel): ?string
	{
		$column = $this->getHotelTypeColumn();

		if (!$column) {
			return 'Hotel';
		}

		$value = data_get($hotel, $column);

		return $value ? (string) $value : null;
	}

	private function getHotelAmenityTokens(Hotel $hotel): array
	{
		$tokens = collect($this->getHotelTextTokens($hotel));

		foreach (['amenity_ids', 'amenities', 'amenity_id'] as $field) {
			$tokens = $tokens->merge($this->parseFilterValues(data_get($hotel, $field)));
		}

		$amenityIds = $tokens
			->filter(fn ($item) => ctype_digit((string) $item))
			->map(fn ($item) => (int) $item)
			->unique()
			->values();

		if ($amenityIds->isNotEmpty()) {
			$names = Amenities::whereIn('id', $amenityIds->all())
				->pluck('name')
				->map(fn ($name) => strtolower(trim((string) $name)));

			$tokens = $tokens->merge($names);
		}

		return $tokens->unique()->values()->all();
	}

	private function hasHotelAmenitySupport(): bool
	{
		foreach (['amenity_ids', 'amenities', 'amenity_id'] as $column) {
			if (Schema::hasColumn('hotels', $column)) {
				return true;
			}
		}

		if (Schema::hasColumn('rooms', 'attributes')) {
			return true;
		}

		return false;
	}

	private function getHotelTypeOptions()
	{
		$column = $this->getHotelTypeColumn();

		if ($column) {
			$options = Hotel::where('status', 1)
				->whereNotNull($column)
				->where($column, '!=', '')
				->distinct()
				->orderBy($column)
				->pluck($column)
				->values();

			if ($options->isNotEmpty()) {
				return $options;
			}
		}

		return collect(['Hotel', 'Resort', 'Villa', 'Apartment']);
	}

	private function getPriceRangePayload(): array
	{
		$rooms = DB::table('rooms')
			->where('status', 1)
			->get(['single_price', 'single_sale_price', 'double_price', 'double_sale_price']);

		$prices = $rooms->flatMap(function ($room) {
			return [
				(float) (($room->single_sale_price ?? 0) > 0 ? $room->single_sale_price : ($room->single_price ?? 0)),
				(float) (($room->double_sale_price ?? 0) > 0 ? $room->double_sale_price : ($room->double_price ?? 0)),
			];
		})->filter(fn ($price) => $price > 0)->values();

		return [
			'min' => $prices->isNotEmpty() ? (float) $prices->min() : 0,
			'max' => $prices->isNotEmpty() ? (float) $prices->max() : 20000,
		];
	}

	private function normalizeSortBy(?string $sortBy): ?string
	{
		if ($sortBy === null || trim($sortBy) === '') {
			return null;
		}

		$normalized = strtolower(trim($sortBy));
		$normalized = str_replace([' ', '-'], '_', $normalized);

		return match ($normalized) {
			'popular', 'popularity' => 'popularity',
			'price', 'price_low_high', 'low_to_high', 'price_low_to_high' => 'price',
			'price_high_low', 'high_to_low', 'price_high_to_low' => 'price_high_to_low',
			'rating', 'star', 'star_rating' => 'rating',
			'latest', 'newest' => 'latest',
			default => $normalized,
		};
	}

	private function getHotelRooms(int $hotelId)
	{
		return DB::table('rooms')
			->where('hotel_id', $hotelId)
			->where('status', 1)
			->orderByDesc('id')
			->get();
	}

	private function getAvailableRoomSummary(Hotel $hotel): array
	{
		$requestedRooms = max(1, (int) request('rooms', 1));
		$requestedAdults = max(1, (int) request('adults', 1));
		$requestedChildren = max(0, (int) request('children', 0));

		$availableRoomTypes = [];
		$minimumNightPrice = null;
		$totalAvailableRooms = 0;

		foreach ($this->getHotelRooms($hotel->id) as $room) {
			$availableRooms = (int) ($room->rooms_available ?? 0);
			$maxAdults = (int) ($room->max_adults ?? 0);
			$maxChildren = (int) ($room->max_children ?? 0);

			if ($availableRooms < $requestedRooms) {
				continue;
			}

			if ($maxAdults > 0 && $maxAdults < $requestedAdults) {
				continue;
			}

			if ($maxChildren > 0 && $maxChildren < $requestedChildren) {
				continue;
			}

			$effectivePrice = (float) (($room->single_sale_price ?? 0) > 0 ? $room->single_sale_price : ($room->single_price ?? 0));
			$minimumNightPrice = $minimumNightPrice === null ? $effectivePrice : min($minimumNightPrice, $effectivePrice);
			$totalAvailableRooms += $availableRooms;

			$gallery = collect($this->parseJsonArray($room->gallery ?? null))
				->map(fn ($path) => $this->buildStorageUrl($path))
				->filter()
				->values();

			$availableRoomTypes[] = [
				'id' => (int) $room->id,
				'name' => $room->room_type,
				'featured_image' => $this->buildStorageUrl($room->featured_image),
				'gallery' => $gallery,
				'price' => $effectivePrice,
				'single_price' => (float) ($room->single_price ?? 0),
				'single_sale_price' => (float) ($room->single_sale_price ?? 0),
				'double_price' => (float) ($room->double_price ?? 0),
				'double_sale_price' => (float) ($room->double_sale_price ?? 0),
				'extra_adult_price' => (float) ($room->extra_adult_price ?? 0),
				'extra_child_price' => (float) ($room->extra_child_price ?? 0),
				'gst' => (float) ($room->gst ?? 0),
				'room_size' => $room->room_size,
				'attributes' => $this->parseJsonArray($room->attributes ?? null),
				'total_rooms' => $availableRooms,
				'available_rooms' => $availableRooms,
				'max_adults' => $maxAdults,
				'max_children' => $maxChildren,
			];
		}

		return [
			'available_room_types' => $availableRoomTypes,
			'minimum_night_price' => $minimumNightPrice,
			'has_availability' => !empty($availableRoomTypes),
			'total_available_rooms' => $totalAvailableRooms,
		];
	}

	private function formatHotelCard(Hotel $hotel, array $availabilitySummary, int $nights): array
	{
		$minimumNightPrice = $availabilitySummary['minimum_night_price'];
		$totalStayPrice = $minimumNightPrice !== null ? $minimumNightPrice * $nights : null;
		$hotelType = $this->getHotelTypeValue($hotel);
		$optionFlags = $this->getHotelOptionFlags($hotel);
		$hotelServices = $this->getHotelServicesPayload($hotel->id);

		return [
			'id' => $hotel->id,
			'name' => $hotel->name,
			'slug' => $hotel->slug,
			'featured_image' => $this->buildImageUrl($hotel->featured_image),
			'city' => $hotel->city,
			'state' => $hotel->state,
			'country' => $hotel->country,
			'address' => $hotel->address,
			'hotel_type' => $hotelType,
			'free_cancellation' => $optionFlags['free_cancellation'],
			'breakfast_included' => $optionFlags['breakfast_included'],
			'star_rating' => (int) $hotel->star_rating,
			'description' => $hotel->description,
			'check_in_time' => $hotel->check_in_time,
			'check_out_time' => $hotel->check_out_time,
			'total_rooms' => (int) ($availabilitySummary['total_available_rooms'] ?? ($hotel->total_rooms ?? 0)),
			'minimum_night_price' => $minimumNightPrice,
			'total_stay_price' => $totalStayPrice,
			'seller' => [
				'id' => data_get($hotel, 'seller.id'),
				'name' => trim((string) data_get($hotel, 'seller.f_name') . ' ' . data_get($hotel, 'seller.l_name')),
			],
			'hotel_services_count' => $hotelServices->count(),
			'hotel_services' => $hotelServices,
			'available_room_types_count' => count($availabilitySummary['available_room_types']),
			'has_availability' => $availabilitySummary['has_availability'],
		];
	}

	private function formatHotelListPayload(Hotel $hotel, array $availabilitySummary, int $nights): array
	{
		return array_merge(
			$this->formatHotelCard($hotel, $availabilitySummary, $nights),
			[
				'room_types' => $availabilitySummary['available_room_types'],
			]
		);
	}

	private function formatHotelDetailsPayload(Hotel $hotel, array $availabilitySummary, int $nights): array
	{
		return array_merge(
			$this->formatHotelCard($hotel, $availabilitySummary, $nights),
			[
				'email' => $hotel->email,
				'phone' => $hotel->phone,
				'postal_code' => $hotel->postal_code,
				'amenities' => $this->getAmenitiesPayload(),
				'room_types' => $availabilitySummary['available_room_types'],
				'seller' => [
					'id' => data_get($hotel, 'seller.id'),
					'name' => trim((string) data_get($hotel, 'seller.f_name') . ' ' . data_get($hotel, 'seller.l_name')),
					'email' => data_get($hotel, 'seller.email'),
				],
			]
		);
	}

	public function getFilterOptions(Request $request): JsonResponse
	{
		$cities = Hotel::where('status', 1)
			->whereNotNull('city')
			->where('city', '!=', '')
			->distinct()
			->orderBy('city')
			->pluck('city')
			->values();

		$freeCancellationConfig = $this->getBooleanFilterColumn('free_cancellation');
		$breakfastConfig = $this->getBooleanFilterColumn('breakfast_included');

		return response()->json([
			'destinations' => $cities,
			'sort_options' => [
				['label' => 'Popularity', 'value' => 'popularity'],
				['label' => 'Price', 'value' => 'price'],
				['label' => 'Rating', 'value' => 'rating'],
			],
			'price_range' => $this->getPriceRangePayload(),
			'star_ratings' => [1, 2, 3, 4, 5],
			'hotel_types' => $this->getHotelTypeOptions()->values(),
			'amenities' => $this->getAmenitiesPayload(),
			'other_options' => [
				['label' => 'Free Cancellation', 'value' => 'free_cancellation', 'enabled' => (bool) $freeCancellationConfig],
				['label' => 'Breakfast Included', 'value' => 'breakfast_included', 'enabled' => (bool) $breakfastConfig],
			],
			'filter_support' => [
				'hotel_type' => (bool) $this->getHotelTypeColumn(),
				'amenities' => $this->hasHotelAmenitySupport(),
				'free_cancellation' => (bool) $freeCancellationConfig,
				'breakfast_included' => (bool) $breakfastConfig,
			],
			'guest_filters' => [
				'defaults' => [
					'adults' => 2,
					'children' => 0,
					'rooms' => 1,
				],
				'ranges' => [
					'adults' => ['min' => 1, 'max' => 10],
					'children' => ['min' => 0, 'max' => 10],
					'rooms' => ['min' => 1, 'max' => 10],
				],
			],
		], 200);
	}

	public function getHotelList(Request $request): JsonResponse
	{
		$request->merge([
			'sort_by' => $this->normalizeSortBy($request->input('sort_by')),
		]);

		$validator = Validator::make($request->all(), [
			'destination' => 'nullable|string|max:255',
			'check_in_date' => 'nullable|date',
			'check_out_date' => 'nullable|date|after:check_in_date',
			'adults' => 'nullable|integer|min:1',
			'children' => 'nullable|integer|min:0',
			'rooms' => 'nullable|integer|min:1',
			'star_rating' => 'nullable|integer|min:1|max:5',
			'hotel_type' => 'nullable|string|max:100',
			'amenities' => 'nullable',
			'free_cancellation' => 'nullable|boolean',
			'breakfast_included' => 'nullable|boolean',
			'min_price' => 'nullable|numeric|min:0',
			'max_price' => 'nullable|numeric|min:0',
			'limit' => 'nullable|integer|min:1|max:50',
			'offset' => 'nullable|integer|min:0',
			'sort_by' => 'nullable|in:latest,popularity,price,rating,price_high_to_low',
		]);

		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()], 403);
		}

		$checkInDate = $request->check_in_date;
		$checkOutDate = $request->check_out_date;
		$nights = $this->nightsCount($checkInDate, $checkOutDate);
		$limit = (int) $request->input('limit', 10);
		$offset = (int) $request->input('offset', 0);

		$query = Hotel::with(['seller'])->where('status', 1);

		if ($request->filled('destination')) {
			$destination = trim($request->destination);
			$query->where(function ($builder) use ($destination) {
				$builder->where('name', 'like', "%{$destination}%")
					->orWhere('city', 'like', "%{$destination}%")
					->orWhere('state', 'like', "%{$destination}%")
					->orWhere('country', 'like', "%{$destination}%")
					->orWhere('address', 'like', "%{$destination}%");
			});
		}

		if ($request->filled('star_rating')) {
			$query->where('star_rating', '>=', $request->star_rating);
		}

		$hotelTypeColumn = $this->getHotelTypeColumn();

		if ($request->filled('hotel_type') && $hotelTypeColumn) {
			$hotelType = strtolower(trim((string) $request->hotel_type));

			if ($hotelType !== 'all') {
				$query->whereRaw('LOWER(' . $hotelTypeColumn . ') = ?', [$hotelType]);
			}
		}

		$requestedAmenities = $this->hasHotelAmenitySupport()
			? $this->parseFilterValues($request->input('amenities'))
			: [];

		$freeCancellationSupported = (bool) $this->getBooleanFilterColumn('free_cancellation');
		$breakfastSupported = (bool) $this->getBooleanFilterColumn('breakfast_included');

		$requestedFreeCancellation = $freeCancellationSupported && $request->has('free_cancellation')
			? $request->boolean('free_cancellation')
			: null;
		$requestedBreakfastIncluded = $breakfastSupported && $request->has('breakfast_included')
			? $request->boolean('breakfast_included')
			: null;

		$hotelCards = $query->get()->map(function (Hotel $hotel) use ($nights) {
			$availabilitySummary = $this->getAvailableRoomSummary($hotel);

			return [
				'payload' => $this->formatHotelListPayload($hotel, $availabilitySummary, $nights),
				'amenity_tokens' => $this->getHotelAmenityTokens($hotel),
				'option_flags' => $this->getHotelOptionFlags($hotel),
			];
		})->filter(function (array $row) use ($request) {
			$minimumNightPrice = data_get($row, 'payload.minimum_night_price');

			if ($request->filled('min_price') && $minimumNightPrice !== null && $minimumNightPrice < (float) $request->min_price) {
				return false;
			}

			if ($request->filled('max_price') && $minimumNightPrice !== null && $minimumNightPrice > (float) $request->max_price) {
				return false;
			}

			return true;
		})->filter(function (array $row) use ($requestedAmenities) {
			if (empty($requestedAmenities)) {
				return true;
			}

			$hotelAmenityTokens = collect(data_get($row, 'amenity_tokens', []));

			return collect($requestedAmenities)->every(fn ($amenity) => $hotelAmenityTokens->contains($amenity));
		})->filter(function (array $row) use ($requestedFreeCancellation, $requestedBreakfastIncluded) {
			$flags = data_get($row, 'option_flags', []);

			if ($requestedFreeCancellation !== null && (bool) data_get($flags, 'free_cancellation') !== $requestedFreeCancellation) {
				return false;
			}

			if ($requestedBreakfastIncluded !== null && (bool) data_get($flags, 'breakfast_included') !== $requestedBreakfastIncluded) {
				return false;
			}

			return true;
		})->values();

		$sortBy = $request->input('sort_by', 'latest');

		$hotelCards = match ($sortBy) {
			'price' => $hotelCards->sortBy(fn ($row) => data_get($row, 'payload.minimum_night_price') ?? PHP_INT_MAX)->values(),
			'price_high_to_low' => $hotelCards->sortByDesc(fn ($row) => data_get($row, 'payload.minimum_night_price') ?? 0)->values(),
			'rating' => $hotelCards->sortByDesc(fn ($row) => data_get($row, 'payload.star_rating') ?? 0)->values(),
			'popularity' => $hotelCards->sortByDesc(fn ($row) => data_get($row, 'payload.available_room_types_count') ?? 0)->values(),
			default => $hotelCards->sortByDesc(fn ($row) => data_get($row, 'payload.id'))->values(),
		};

		$paginated = $hotelCards->slice($offset, $limit)->pluck('payload')->values();

		return response()->json([
			'total_size' => $hotelCards->count(),
			'limit' => $limit,
			'offset' => $offset,
			'check_in_date' => $checkInDate,
			'check_out_date' => $checkOutDate,
			'nights' => $nights,
			'hotels' => $paginated,
		], 200);
	}

	public function getHotelDetails($id, Request $request): JsonResponse
	{
		$validator = Validator::make($request->all(), [
			'check_in_date' => 'nullable|date',
			'check_out_date' => 'nullable|date|after:check_in_date',
			'adults' => 'nullable|integer|min:1',
			'children' => 'nullable|integer|min:0',
			'rooms' => 'nullable|integer|min:1',
		]);

		if ($validator->fails()) {
			return response()->json(['errors' => $validator->errors()], 403);
		}

		$hotel = Hotel::with(['seller'])
			->where('status', 1)
			->findOrFail($id);

		$checkInDate = $request->check_in_date;
		$checkOutDate = $request->check_out_date;
		$nights = $this->nightsCount($checkInDate, $checkOutDate);
		$availabilitySummary = $this->getAvailableRoomSummary($hotel);

		return response()->json([
			'hotel' => $this->formatHotelDetailsPayload($hotel, $availabilitySummary, $nights),
			'check_in_date' => $checkInDate,
			'check_out_date' => $checkOutDate,
			'nights' => $nights,
		], 200);
	}
}
