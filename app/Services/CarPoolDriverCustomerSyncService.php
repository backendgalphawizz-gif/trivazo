<?php

namespace App\Services;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Models\CarPoolDriver;
use App\User;
use App\Utils\Helpers;
use Illuminate\Support\Str;

/**
 * When a carpool driver is registered, ensure a matching row exists in `users`
 * (store customer) so they can use the main app as a customer with the same phone.
 */
class CarPoolDriverCustomerSyncService
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepo,
    ) {}

    public function ensureCustomerUserFromDriver(CarPoolDriver $driver): void
    {
        if (User::query()->where('phone', $driver->phone)->exists()) {
            return;
        }

        [$fName, $lName] = $this->splitDriverName($driver->name);
        if ($lName === '') {
            $lName = '-';
        }

        $email = $driver->email;
        if ($email !== null && $email !== '' && User::query()->where('email', $email)->exists()) {
            $email = null;
        }

        $this->customerRepo->add([
            'name'              => $driver->name,
            'f_name'            => $fName,
            'l_name'            => $lName,
            'phone'             => $driver->phone,
            'country_code'      => $driver->country_code ?: '+91',
            'email'             => $email,
            'gender'            => $driver->gender,
            'password'          => $driver->password,
            'is_active'         => 1,
            'temporary_token'   => Str::random(40),
            'referral_code'     => Helpers::generate_referer_code(),
            'is_phone_verified' => 0,
            'is_email_verified' => 0,
        ]);
    }

    /**
     * Copy identity from the store `users` row (Passport user) onto `carpool_drivers` for the same phone.
     * Keeps driver API data aligned with the customer account the token belongs to.
     */
    public function syncDriverIdentityFromUser(User $user, CarPoolDriver $driver): void
    {
        if ((string) $user->phone !== (string) $driver->phone) {
            return;
        }

        $name = trim((string) ($user->name ?? ''));
        if ($name === '') {
            $name = trim(trim((string) ($user->f_name ?? '')) . ' ' . trim((string) ($user->l_name ?? '')));
        }
        if ($name === '') {
            $name = $driver->name;
        }

        $payload = [
            'name'         => $name,
            'country_code' => $user->country_code ?: ($driver->country_code ?: '+91'),
        ];

        if ($user->phone) {
            $payload['phone'] = $user->phone;
        }

        $email = $user->email ? trim((string) $user->email) : null;
        if ($email !== null && $email !== '') {
            $taken = User::query()->where('email', $email)->where('id', '!=', $user->id)->exists();
            if (!$taken) {
                $conflictDriver = CarPoolDriver::query()
                    ->where('email', $email)
                    ->where('id', '!=', $driver->id)
                    ->exists();
                if (!$conflictDriver) {
                    $payload['email'] = $email;
                }
            }
        }

        $g = $user->gender ?? null;
        if ($g !== null && $g !== '' && in_array((string) $g, ['male', 'female', 'other'], true)) {
            $payload['gender'] = $g;
        }

        $driver->fill($payload);
        if ($driver->isDirty()) {
            $driver->save();
        }
    }

    /**
     * Keep linked store customer (same phone) aligned with driver profile after admin/API edits.
     */
    public function syncLinkedCustomerFromDriver(CarPoolDriver $driver): void
    {
        $user = User::query()->where('phone', $driver->phone)->first();
        if (!$user) {
            return;
        }

        [$fName, $lName] = $this->splitDriverName($driver->name);
        if ($lName === '') {
            $lName = '-';
        }

        $update = [
            'name'         => $driver->name,
            'f_name'       => $fName,
            'l_name'       => $lName,
            'country_code' => $driver->country_code ?: '+91',
            'gender'       => $driver->gender,
        ];

        if ($driver->email && (string) $driver->email !== '') {
            if (!User::query()->where('email', $driver->email)->where('id', '!=', $user->id)->exists()) {
                $update['email'] = $driver->email;
            }
        }

        $this->customerRepo->update(id: (string) $user->id, data: $update);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitDriverName(string $name): array
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name));
        if ($name === '') {
            return ['Driver', ''];
        }
        $parts = preg_split('/\s+/u', $name, 2);

        return [$parts[0], $parts[1] ?? ''];
    }
}
