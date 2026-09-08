<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->ensureFirebaseCredentialsExist();
    }

    public function boot(): void
    {
        $this->ensureFirebaseCredentialsExist();
    }

    private function ensureFirebaseCredentialsExist(): void
    {
        $targetPath = storage_path('app/firebase-credentials.json');

        if (!file_exists($targetPath)) {
            // Ambil dari getenv() atau env()
            $base64 = getenv('FIREBASE_CREDENTIALS_BASE64') ?: env('FIREBASE_CREDENTIALS_BASE64');

            if ($base64) {
                $dir = dirname($targetPath);
                if (!file_exists($dir)) {
                    mkdir($dir, 0755, true);
                }

                file_put_contents($targetPath, base64_decode($base64));
            }
        }
    }
}