# CarPool API — cURL Reference

**Base URL:** `http://localhost/api`  
**Admin Base:** `http://localhost/admin`

Replace placeholders before running:
- `{DRIVER_TOKEN}` — Bearer token from driver login/register  
- `{PASSENGER_TOKEN}` — Bearer token from customer login  
- `{XSRF_TOKEN}` / `{SESSION}` — CSRF token + session cookie from admin browser session

---

## PUBLIC ENDPOINTS

### 1. Driver Register
```bash
curl -X POST http://localhost/api/v1/carpool/driver/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John Driver",
    "phone": "01700000001",
    "email": "john@driver.com",
    "password": "secret123",
    "vehicle_type": "Sedan",
    "vehicle_number": "DHK-1234",
    "vehicle_model": "Toyota Corolla",
    "vehicle_color": "White",
    "vehicle_capacity": 4,
    "license_number": "DL-9876543"
  }'
```

### 2. Driver Login
```bash
curl -X POST http://localhost/api/v1/carpool/driver/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "phone": "01700000001",
    "password": "secret123",
    "fcm_token": "device_fcm_token_here"
  }'
```

### 3. Search Routes
```bash
curl -X GET "http://localhost/api/v1/carpool/routes/search?origin_lat=23.8103&origin_lng=90.4125&destination_lat=23.7000&destination_lng=90.3750&date=2026-04-15&seats=2&radius_km=5&limit=10" \
  -H "Accept: application/json"
```

### 4. Get Route Detail
```bash
curl -X GET http://localhost/api/v1/carpool/routes/1 \
  -H "Accept: application/json"
```

---

## DRIVER ENDPOINTS — `Authorization: Bearer {DRIVER_TOKEN}`

### 5. Logout
```bash
curl -X POST http://localhost/api/v1/carpool/driver/logout \
  -H "Authorization: Bearer {DRIVER_TOKEN}" \
  -H "Accept: application/json"
```

### 6. Get Profile
```bash
curl -X GET http://localhost/api/v1/carpool/driver/profile \
  -H "Authorization: Bearer {DRIVER_TOKEN}" \
  -H "Accept: application/json"
```

### 7. Update Profile
```bash
curl -X PUT http://localhost/api/v1/carpool/driver/profile \
  -H "Authorization: Bearer {DRIVER_TOKEN}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John Driver Updated",
    "vehicle_color": "Black",
    "vehicle_type": "SUV",
    "vehicle_model": "Toyota RAV4",
    "vehicle_number": "DHK-5678",
    "vehicle_capacity": 6,
    "fcm_token": "new_fcm_token",
    "is_online": true
  }'
```

### 8. Get Wallet Balance
```bash
curl -X GET http://localhost/api/v1/carpool/driver/wallet \
  -H "Authorization: Bearer {DRIVER_TOKEN}" \
  -H "Accept: application/json"
```

### 9. Wallet Transaction History
```bash
curl -X GET "http://localhost/api/v1/carpool/driver/wallet/transactions?limit=20" \
  -H "Authorization: Bearer {DRIVER_TOKEN}" \
  -H "Accept: application/json"
```

### 10. Request Withdrawal
```bash
curl -X POST http://localhost/api/v1/carpool/driver/wallet/withdraw \
  -H "Authorization: Bearer {DRIVER_TOKEN}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "amount": 50.00,
    "account_details": {
      "bank_name": "Dutch Bangla Bank",
      "account_number": "123456789",
      "account_holder": "John Driver",
      "routing_number": "090264100"
    }
  }'
```

### 11. List Withdrawal Requests
```bash
curl -X GET "http://localhost/api/v1/carpool/driver/wallet/withdrawals?status=pending&limit=10" \
  -H "Authorization: Bearer {DRIVER_TOKEN}" \
  -H "Accept: application/json"
```
> **`status`** options: `pending` | `approved` | `rejected` | `paid` | `all`

### 12. Create Route
```bash
curl -X POST http://localhost/api/v1/carpool/driver/my-routes \
  -H "Authorization: Bearer {DRIVER_TOKEN}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "origin_name": "Mirpur 10, Dhaka",
    "origin_lat": 23.8057,
    "origin_lng": 90.3668,
    "destination_name": "Motijheel, Dhaka",
    "destination_lat": 23.7272,
    "destination_lng": 90.4192,
    "waypoints": [
      {"name": "Farmgate", "lat": 23.7567, "lng": 90.3871}
    ],
    "ride_type": "scheduled",
    "departure_at": "2026-04-15 08:00:00",
    "total_seats": 3,
    "price_per_seat": 80,
    "currency": "BDT",
    "estimated_duration_min": 45,
    "estimated_distance_km": 12.5,
    "note": "No smoking inside the car"
  }'
```
> **`ride_type`** options: `instant` | `scheduled`  
> Driver must be `is_verified = true` to create routes.

### 13. List My Routes
```bash
curl -X GET "http://localhost/api/v1/carpool/driver/my-routes?status=open&limit=10" \
  -H "Authorization: Bearer {DRIVER_TOKEN}" \
  -H "Accept: application/json"
```
> **`status`** options: `open` | `full` | `departed` | `completed` | `cancelled` | `all`

### 14. Get Single Route
```bash
curl -X GET http://localhost/api/v1/carpool/driver/my-routes/1 \
  -H "Authorization: Bearer {DRIVER_TOKEN}" \
  -H "Accept: application/json"
```

### 15. Depart (Start Ride)
```bash
curl -X POST http://localhost/api/v1/carpool/driver/my-routes/1/depart \
  -H "Authorization: Bearer {DRIVER_TOKEN}" \
  -H "Accept: application/json"
```
> Transitions route to `departed`, all confirmed bookings → `departed`.  
> Only works when route status is `open` or `full`.

### 16. Complete Ride
```bash
curl -X POST http://localhost/api/v1/carpool/driver/my-routes/1/complete \
  -H "Authorization: Bearer {DRIVER_TOKEN}" \
  -H "Accept: application/json"
```
> Transitions route to `completed`, fires `CarPoolRideCompletedEvent` per booking (triggers wallet settlement + notifications).

### 17. Delete Route
```bash
curl -X DELETE http://localhost/api/v1/carpool/driver/my-routes/1 \
  -H "Authorization: Bearer {DRIVER_TOKEN}" \
  -H "Accept: application/json"
```
> Only cancellable routes (not departed/completed) can be deleted.

---

## PASSENGER ENDPOINTS — `Authorization: Bearer {PASSENGER_TOKEN}`

### 18. Create Booking (Wallet Payment — instant confirm)
```bash
curl -X POST http://localhost/api/v1/carpool/bookings \
  -H "Authorization: Bearer {PASSENGER_TOKEN}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "route_id": 1,
    "seat_count": 2,
    "pickup_name": "Mirpur 10 Metro Station",
    "pickup_lat": 23.8057,
    "pickup_lng": 90.3668,
    "drop_name": "Motijheel Bus Stand",
    "drop_lat": 23.7272,
    "drop_lng": 90.4192,
    "payment_method": "wallet",
    "passengers": [
      {"name": "Alice",  "phone": "01700000002", "gender": "female"},
      {"name": "Bob",    "phone": "01700000003", "gender": "male"}
    ]
  }'
```

### 19. Create Booking (Online Payment — returns pending transaction)
```bash
curl -X POST http://localhost/api/v1/carpool/bookings \
  -H "Authorization: Bearer {PASSENGER_TOKEN}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "route_id": 1,
    "seat_count": 1,
    "pickup_name": "Mirpur 10 Metro Station",
    "pickup_lat": 23.8057,
    "pickup_lng": 90.3668,
    "drop_name": "Motijheel Bus Stand",
    "drop_lat": 23.7272,
    "drop_lng": 90.4192,
    "payment_method": "online",
    "passengers": [
      {"name": "Alice", "phone": "01700000002", "gender": "female"}
    ]
  }'
```
> Returns `booking` + `transaction` with a pending gateway reference. Submit the `transaction.id` to your gateway, then call endpoint 21 to confirm.

### 20. List My Bookings
```bash
curl -X GET "http://localhost/api/v1/carpool/bookings?status=confirmed&limit=10" \
  -H "Authorization: Bearer {PASSENGER_TOKEN}" \
  -H "Accept: application/json"
```
> **`status`** options: `pending_payment` | `confirmed` | `departed` | `completed` | `cancelled` | `all`

### 21. Get Booking Detail
```bash
curl -X GET http://localhost/api/v1/carpool/bookings/1 \
  -H "Authorization: Bearer {PASSENGER_TOKEN}" \
  -H "Accept: application/json"
```

### 22. Confirm Online Payment
```bash
curl -X POST http://localhost/api/v1/carpool/bookings/1/pay \
  -H "Authorization: Bearer {PASSENGER_TOKEN}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "gateway_reference": "TXN_20260415_ABC123"
  }'
```
> Call this after the payment gateway confirms success. Transitions booking to `confirmed`.

### 23. Cancel Booking
```bash
curl -X POST http://localhost/api/v1/carpool/bookings/1/cancel \
  -H "Authorization: Bearer {PASSENGER_TOKEN}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "reason": "Change of plans"
  }'
```
> If the booking was already paid, a refund is issued automatically.  
> Only bookings with status `pending_payment` or `confirmed` can be cancelled.

### 24. Submit Review
```bash
curl -X POST http://localhost/api/v1/carpool/bookings/1/review \
  -H "Authorization: Bearer {PASSENGER_TOKEN}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "rating": 5,
    "comment": "Great ride, very punctual driver!"
  }'
```
> Only available after the ride status is `completed`. One review per booking.  
> **`rating`** must be `1`–`5`.

---

## ADMIN ENDPOINTS — Session Cookie Auth

Admin routes use `web` + `admin` session middleware.  
Get `XSRF-TOKEN` and `laravel_session` cookies from your browser after logging into the admin panel.

### 25. List Drivers
```bash
curl -X GET "http://localhost/admin/carpool/drivers?status=active&is_verified=0&search=john&limit=15" \
  -H "Accept: application/json" \
  -H "X-XSRF-TOKEN: {XSRF_TOKEN}" \
  -b "XSRF-TOKEN={XSRF_TOKEN}; laravel_session={SESSION}"
```
> **`status`** options: `active` | `inactive` | `suspended`  
> **`is_verified`**: `0` (unverified) | `1` (verified) — omit to get all

### 26. Verify Driver
```bash
curl -X PUT http://localhost/admin/carpool/drivers/1/verify \
  -H "Accept: application/json" \
  -H "X-XSRF-TOKEN: {XSRF_TOKEN}" \
  -b "XSRF-TOKEN={XSRF_TOKEN}; laravel_session={SESSION}"
```

### 27. Update Driver Status
```bash
curl -X PUT http://localhost/admin/carpool/drivers/1/status \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-XSRF-TOKEN: {XSRF_TOKEN}" \
  -b "XSRF-TOKEN={XSRF_TOKEN}; laravel_session={SESSION}" \
  -d '{
    "status": "suspended"
  }'
```
> **`status`** options: `active` | `inactive` | `suspended`

### 28. List Routes
```bash
curl -X GET "http://localhost/admin/carpool/routes?status=open&ride_type=scheduled&date_from=2026-04-01&date_to=2026-04-30&search=mirpur&limit=20" \
  -H "Accept: application/json" \
  -H "X-XSRF-TOKEN: {XSRF_TOKEN}" \
  -b "XSRF-TOKEN={XSRF_TOKEN}; laravel_session={SESSION}"
```
> **`status`** options: `open` | `full` | `departed` | `completed` | `cancelled`  
> **`ride_type`** options: `instant` | `scheduled`

### 29. List Bookings
```bash
curl -X GET "http://localhost/admin/carpool/bookings?status=confirmed&payment_status=paid&date_from=2026-04-01&date_to=2026-04-30&search=BKG-&limit=20" \
  -H "Accept: application/json" \
  -H "X-XSRF-TOKEN: {XSRF_TOKEN}" \
  -b "XSRF-TOKEN={XSRF_TOKEN}; laravel_session={SESSION}"
```
> **`status`** options: `pending_payment` | `confirmed` | `departed` | `completed` | `cancelled`  
> **`payment_status`** options: `unpaid` | `paid` | `refunded`

### 30. Commission Report
```bash
curl -X GET "http://localhost/admin/carpool/commission-report?date_from=2026-04-01&date_to=2026-04-30" \
  -H "Accept: application/json" \
  -H "X-XSRF-TOKEN: {XSRF_TOKEN}" \
  -b "XSRF-TOKEN={XSRF_TOKEN}; laravel_session={SESSION}"
```
> Returns: `total_bookings`, `total_revenue`, `total_commission`, `total_driver_paid`.  
> Omit date params to get all-time stats.

### 31. List Withdrawal Requests
```bash
curl -X GET "http://localhost/admin/carpool/withdrawals?status=pending&limit=15" \
  -H "Accept: application/json" \
  -H "X-XSRF-TOKEN: {XSRF_TOKEN}" \
  -b "XSRF-TOKEN={XSRF_TOKEN}; laravel_session={SESSION}"
```
> **`status`** options: `pending` | `approved` | `rejected` | `paid` | `all`

### 32. Approve Withdrawal
```bash
curl -X PUT http://localhost/admin/carpool/withdrawals/1/approve \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-XSRF-TOKEN: {XSRF_TOKEN}" \
  -b "XSRF-TOKEN={XSRF_TOKEN}; laravel_session={SESSION}" \
  -d '{
    "note": "Processed via bank transfer on 2026-04-10."
  }'
```
> Deducts from driver `available_balance`, increments `total_withdrawn`, creates withdrawal transaction, notifies driver via FCM.

### 33. Reject Withdrawal
```bash
curl -X PUT http://localhost/admin/carpool/withdrawals/1/reject \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-XSRF-TOKEN: {XSRF_TOKEN}" \
  -b "XSRF-TOKEN={XSRF_TOKEN}; laravel_session={SESSION}" \
  -d '{
    "note": "Account details mismatch. Please resubmit."
  }'
```

---

## Quick Flow Walkthrough

```
1. Driver registers       → POST /driver/register      (gets DRIVER_TOKEN)
2. Admin verifies driver  → PUT  /admin/carpool/drivers/1/verify
3. Driver creates route   → POST /driver/my-routes
4. Passenger searches     → GET  /routes/search
5. Passenger books (wallet) → POST /bookings           (auto-confirmed)
   -- OR --
   Passenger books (online) → POST /bookings           (pending_payment)
   Passenger pays          → POST /bookings/1/pay      (confirmed)
6. Driver departs         → POST /driver/my-routes/1/depart
7. Driver completes       → POST /driver/my-routes/1/complete
                            (driver wallet credited, passenger notified)
8. Passenger reviews      → POST /bookings/1/review
9. Driver requests payout → POST /driver/wallet/withdraw
10. Admin approves payout → PUT  /admin/carpool/withdrawals/1/approve
```
