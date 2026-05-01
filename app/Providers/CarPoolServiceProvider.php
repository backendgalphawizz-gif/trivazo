<?php

namespace App\Providers;

use App\Contracts\Repositories\CarPoolBookingRepositoryInterface;
use App\Contracts\Repositories\CarPoolDriverRepositoryInterface;
use App\Contracts\Repositories\CarPoolRouteRepositoryInterface;
use App\Contracts\Repositories\CarPoolTransactionRepositoryInterface;
use App\Events\CarPool\CarPoolBookingConfirmedEvent;
use App\Events\CarPool\CarPoolRideCompletedEvent;
use App\Listeners\CarPool\NotifyPassengerOnBookingConfirmedListener;
use App\Listeners\CarPool\NotifyPassengerOnRideCompletedListener;
use App\Listeners\CarPool\SettleDriverWalletOnCompletionListener;
use App\Models\CarPoolDriver;
use App\Repositories\CarPoolBookingRepository;
use App\Repositories\CarPoolDriverRepository;
use App\Repositories\CarPoolRouteRepository;
use App\Repositories\CarPoolTransactionRepository;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

class CarPoolServiceProvider extends EventServiceProvider
{
    /**
     * Event → Listener mappings for the carpool domain.
     */
    protected $listen = [
        CarPoolBookingConfirmedEvent::class => [
            NotifyPassengerOnBookingConfirmedListener::class,
        ],
        CarPoolRideCompletedEvent::class => [
            SettleDriverWalletOnCompletionListener::class,
            NotifyPassengerOnRideCompletedListener::class,
        ],
    ];

    public function register(): void
    {
        // Bind interfaces → concrete repositories.
        $this->app->bind(CarPoolDriverRepositoryInterface::class, CarPoolDriverRepository::class);
        $this->app->bind(CarPoolRouteRepositoryInterface::class,  CarPoolRouteRepository::class);
        $this->app->bind(CarPoolBookingRepositoryInterface::class, CarPoolBookingRepository::class);
        $this->app->bind(CarPoolTransactionRepositoryInterface::class, CarPoolTransactionRepository::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Register carpool_driver Passport guard.
        Auth::extend('carpool_driver_token', function ($app, $name, array $config) {
            return new \Laravel\Passport\Guards\TokenGuard(
                $app->make(\Laravel\Passport\TokenRepository::class),
                $app->make(\Laravel\Passport\ClientRepository::class),
                $app->make(\Lcobucci\JWT\Configuration::class),
                $app->make(\League\OAuth2\Server\ResourceServer::class),
                Auth::createUserProvider($config['provider']),
                $app->make(\Illuminate\Http\Request::class),
                $app->make(\Laravel\Passport\PassportUserProvider::class, ['config' => $config])
            );
        });

        // Register admin carpool routes.
        Route::middleware('web')
            ->namespace('App\Http\Controllers')
            ->group(base_path('routes/admin/carpool.php'));
    }
}
