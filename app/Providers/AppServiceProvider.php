<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Buat file firebase.json di Wasmer jika variabel env tersedia
        if (env('FIREBASE_JSON_CONTENT')) {
            $path = storage_path('app/firebase-credentials.json');
            if (!file_exists($path)) {
                if (!file_exists(storage_path('app'))) {
                    mkdir(storage_path('app'), 0755, true);
                }
                file_put_contents($path, env('FIREBASE_JSON_CONTENT'));
            }
        }
    }
}
