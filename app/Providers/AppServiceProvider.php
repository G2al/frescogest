<?php

namespace App\Providers;

use App\Http\Controllers\Admin\LogoutController as AdminLogoutController;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Observers\CustomerObserver;
use App\Observers\ProductObserver;
use Filament\Auth\Http\Controllers\LogoutController;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            LogoutController::class,
            AdminLogoutController::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Customer::observe(CustomerObserver::class);
        Product::observe(ProductObserver::class);

        ResetPassword::createUrlUsing(fn (User $user, string $token): string => url('/reset-password.html').'?'.http_build_query([
            'token' => $token,
            'email' => $user->email,
        ]));
    }
}
