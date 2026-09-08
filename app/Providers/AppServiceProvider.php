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
        $targetPath = storage_path('app/firebase-credentials.json');

        // Buat file firebase-credentials.json jika belum ada di server Wasmer
        if (!file_exists($targetPath) && env('FIREBASE_CREDENTIALS_BASE64')) {
            $dir = dirname($targetPath);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            
            $jsonContent = base64_decode(env('FIREBASE_CREDENTIALS_BASE64'));
            file_put_contents($targetPath, $jsonContent);
        }
    }
}
