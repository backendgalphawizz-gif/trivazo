<?php

namespace App\Services;

use App\Models\CarPoolBooking;
use App\Models\CarPoolDriver;
use App\Traits\PushNotificationTrait;

class CarPoolNotificationService
{
    use PushNotificationTrait;

    public function notifyPassengerBookingConfirmed(CarPoolBooking $booking): void
    {
        $passenger = $booking->passenger;

        if (!$passenger || !$passenger->fcm_token) {
            return;
        }

        $this->sendPushNotificationToDevice(
            $passenger->fcm_token,
            $this->buildPayload(
                title: 'Booking Confirmed',
                body: "Your carpool booking #{$booking->booking_code} is confirmed.",
                data: ['booking_id' => $booking->id, 'type' => 'carpool_booking_confirmed']
            )
        );
    }

    public function notifyPassengerRideDeparted(CarPoolBooking $booking): void
    {
        $passenger = $booking->passenger;

        if (!$passenger || !$passenger->fcm_token) {
            return;
        }

        $this->sendPushNotificationToDevice(
            $passenger->fcm_token,
            $this->buildPayload(
                title: 'Ride Departed',
                body: "Your driver has departed. Booking #{$booking->booking_code}.",
                data: ['booking_id' => $booking->id, 'type' => 'carpool_ride_departed']
            )
        );
    }

    public function notifyPassengerRideCompleted(CarPoolBooking $booking): void
    {
        $passenger = $booking->passenger;

        if (!$passenger || !$passenger->fcm_token) {
            return;
        }

        $this->sendPushNotificationToDevice(
            $passenger->fcm_token,
            $this->buildPayload(
                title: 'Ride Completed',
                body: "Your ride is complete. Please leave a review!",
                data: ['booking_id' => $booking->id, 'type' => 'carpool_ride_completed']
            )
        );
    }

    public function notifyDriverWithdrawalApproved(CarPoolDriver $driver, float $amount): void
    {
        if (!$driver->fcm_token) {
            return;
        }

        $this->sendPushNotificationToDevice(
            $driver->fcm_token,
            $this->buildPayload(
                title: 'Withdrawal Approved',
                body: "Your withdrawal of {$amount} has been approved.",
                data: ['amount' => $amount, 'type' => 'carpool_withdrawal_approved']
            )
        );
    }

    private function buildPayload(string $title, string $body, array $data = []): array
    {
        return [
            'notification' => [
                'title' => $title,
                'body'  => $body,
            ],
            'data' => array_merge($data, ['click_action' => 'FLUTTER_NOTIFICATION_CLICK']),
        ];
    }
}
