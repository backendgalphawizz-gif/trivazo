<?php

namespace App\Services;

use App\Enums\CarPoolBookingStatus;
use App\Enums\CarPoolRouteStatus;
use App\Events\CarPool\CarPoolBookingConfirmedEvent;
use App\Models\CarPoolBooking;
use App\Models\CarPoolRoute;
use App\Models\CarPoolStatusHistory;
use Illuminate\Support\Str;

class CarPoolBookingService
{
    public function __construct(
        private readonly CarPoolFareService $fareService,
        private readonly CarPoolSeatService $seatService
    ) {}

    /**
     * Create a new booking in pending_payment status and reserve seats.
     */
    public function createBooking(CarPoolRoute $route, array $data): CarPoolBooking
    {
        $seatCount = (int) ($data['seat_count'] ?? 1);
        $fareTotal = $this->fareService->estimate($route, $seatCount);
        $split     = $this->fareService->splitFare($fareTotal);

        // Reserve seats (throws on failure).
        $this->seatService->reserveSeats($route, $seatCount);

        $booking = CarPoolBooking::create([
            'route_id'                => $route->id,
            'passenger_id'            => $data['passenger_id'],
            'user_id'                 => $data['passenger_id'],
            'pickup_name'             => $data['pickup_name'] ?? $route->origin_name,
            'pickup_lat'              => $data['pickup_lat'] ?? $route->origin_lat,
            'pickup_lng'              => $data['pickup_lng'] ?? $route->origin_lng,
            'drop_name'               => $data['drop_name'] ?? $route->destination_name,
            'drop_lat'                => $data['drop_lat'] ?? $route->destination_lat,
            'drop_lng'                => $data['drop_lng'] ?? $route->destination_lng,
            'seat_count'              => $seatCount,
            'booking_code'            => strtoupper(Str::random(8)),
            'status'                  => CarPoolBookingStatus::PENDING_PAYMENT,
            'fare_total'              => $fareTotal,
            'admin_commission_amount' => $split['admin_commission'],
            'driver_amount'           => $split['driver_amount'],
            'payment_method'          => $data['payment_method'] ?? null,
            'payment_status'          => 'unpaid',
        ]);

        // Store individual named passengers if provided.
        if (!empty($data['passengers']) && is_array($data['passengers'])) {
            foreach ($data['passengers'] as $p) {
                $booking->passengers()->create([
                    'saved_passenger_id' => $p['saved_passenger_id'] ?? $p['id'] ?? null,
                    'name'               => $p['name'] ?? '',
                    'phone'              => $p['phone'] ?? null,
                    'gender'             => $p['gender'] ?? null,
                    'age'                => $p['age'] ?? null,
                ]);
            }
        }

        $this->recordHistory('booking', $booking->id, null, CarPoolBookingStatus::PENDING_PAYMENT, 'passenger', $data['passenger_id']);

        return $booking;
    }

    /**
     * Called after payment is confirmed. Moves booking to confirmed status.
     */
    public function confirmAfterPayment(CarPoolBooking $booking, string $paymentMethod, string $gatewayReference = null): void
    {
        $oldStatus = $booking->status;

        $booking->update([
            'status'            => CarPoolBookingStatus::CONFIRMED,
            'payment_method'    => $paymentMethod,
            'payment_status'    => 'paid',
            'gateway_reference' => $gatewayReference,
            'confirmed_at'      => now(),
        ]);

        $this->recordHistory('booking', $booking->id, $oldStatus, CarPoolBookingStatus::CONFIRMED, 'system', null, 'Payment confirmed');

        event(new CarPoolBookingConfirmedEvent($booking->fresh(['route', 'passenger', 'passengers'])));
    }

    /**
     * Cancel a booking (only if in a cancellable state).
     */
    public function cancel(CarPoolBooking $booking, string $cancelledBy, string $reason = null): void
    {
        if (!$booking->isCancellable()) {
            throw new \RuntimeException('Booking cannot be cancelled at this stage.');
        }

        $oldStatus = $booking->status;

        $booking->update([
            'status'              => CarPoolBookingStatus::CANCELLED,
            'cancelled_by'        => $cancelledBy,
            'cancellation_reason' => $reason,
            'cancelled_at'        => now(),
        ]);

        // Release reserved seats back.
        $this->seatService->releaseSeats($booking->route, $booking->seat_count);

        $this->recordHistory('booking', $booking->id, $oldStatus, CarPoolBookingStatus::CANCELLED, $cancelledBy, null, $reason);
    }

    /**
     * Transition all confirmed bookings of a route to a new status.
     */
    public function transitionBookingsForRoute(CarPoolRoute $route, string $newStatus, string $actor = 'driver'): void
    {
        $route->bookings()
            ->whereIn('status', [CarPoolBookingStatus::CONFIRMED, CarPoolBookingStatus::DEPARTED])
            ->each(function (CarPoolBooking $booking) use ($newStatus, $actor) {
                $oldStatus = $booking->status;
                $timestamps = [];

                if ($newStatus === CarPoolBookingStatus::DEPARTED) {
                    $timestamps['departed_at'] = now();
                } elseif ($newStatus === CarPoolBookingStatus::COMPLETED) {
                    $timestamps['completed_at'] = now();
                }

                $booking->update(array_merge(['status' => $newStatus], $timestamps));
                $this->recordHistory('booking', $booking->id, $oldStatus, $newStatus, $actor, null);
            });
    }

    private function recordHistory(string $type, int $id, ?string $old, string $new, ?string $actorType, ?int $actorId, string $note = null): void
    {
        CarPoolStatusHistory::create([
            'target_type' => $type,
            'target_id'   => $id,
            'old_status'  => $old,
            'new_status'  => $new,
            'actor_type'  => $actorType,
            'actor_id'    => $actorId,
            'note'        => $note,
        ]);
    }
}
