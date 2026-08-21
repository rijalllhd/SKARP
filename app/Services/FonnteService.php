<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class FonnteService
{
    public function send(string $message): Response
    {
        return Http::withHeaders(['Authorization' => config('services.fonnte.token')])
            ->asForm()
            ->post('https://api.fonnte.com/send', [
                'target' => config('services.fonnte.target'),
                'message' => $message,
            ]);
    }
}
