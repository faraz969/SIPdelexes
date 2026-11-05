<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // cPanel / CPGuard / some WAFs strip Authorization. Also accept
        // X-Api-Token / X-Bank-Token for bank API clients.
        Sanctum::getAccessTokenFromRequestUsing(function ($request) {
            $token = $request->bearerToken();
            if (!empty($token)) {
                return $token;
            }

            foreach (['X-Api-Token', 'X-Bank-Token', 'X-Authorization'] as $header) {
                $value = trim((string) $request->header($header, ''));
                if ($value === '') {
                    continue;
                }

                if (stripos($value, 'Bearer ') === 0) {
                    return trim(substr($value, 7));
                }

                return $value;
            }

            return null;
        });
    }
}
