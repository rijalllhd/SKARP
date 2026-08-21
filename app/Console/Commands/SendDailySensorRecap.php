<?php

namespace App\Console\Commands;

use App\Services\FirebaseSensorService;
use App\Services\FonnteService;
use App\Services\SensorMessageService;
use Illuminate\Console\Command;

class SendDailySensorRecap extends Command
{
    protected $signature = 'sensor:send-daily-recap';
    protected $description = 'Kirim rekap sensor harian ke WhatsApp';

    public function handle(FirebaseSensorService $sensor, FonnteService $fonnte, SensorMessageService $message): int
    {
        try {
            $response = $fonnte->send($message->recap($sensor->realtime()));
            $response->throw();
            $this->info('Rekap WhatsApp terkirim.');
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }
}
