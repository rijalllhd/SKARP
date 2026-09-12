<?php

namespace App\Http\Controllers;

use App\Exports\SensorHistoryExport;
use App\Services\FirebaseSensorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class HistoryExportController extends Controller
{
    public function excel(Request $request, FirebaseSensorService $sensor)
    {
        $filter = $this->filter($request, $sensor);
        return Excel::download(
            new SensorHistoryExport($filter['rows']),
            'riwayat-sensor-'.$filter['suffix'].'.xlsx',
            \Maatwebsite\Excel\Excel::XLSX,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function pdf(Request $request, FirebaseSensorService $sensor)
    {
        $filter = $this->filter($request, $sensor);
        $filter['rows'] = $this->summarizeForPdf($filter['rows']);
        return Pdf::loadView('dashboard.exports.history-pdf', $filter)
            ->setPaper('a4', 'landscape')
            ->download('riwayat-sensor-'.$filter['suffix'].'.pdf');
    }

    private function filter(Request $request, FirebaseSensorService $sensor): array
    {
        $validated = $request->validate([
            'period' => ['required', 'in:all,last_month,range'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
        if ($validated['period'] === 'range' && (! $request->filled('start_date') || ! $request->filled('end_date'))) {
            abort(422, 'Tanggal awal dan akhir wajib diisi untuk pilihan rentang.');
        }

        $start = $validated['period'] === 'last_month' ? now('Asia/Jakarta')->subMonth()->startOfDay() : ($request->filled('start_date') ? Carbon::parse($request->start_date, 'Asia/Jakarta')->startOfDay() : null);
        $end = $request->filled('end_date') ? Carbon::parse($request->end_date, 'Asia/Jakarta')->endOfDay() : null;

        $rows = collect($sensor->history())
            ->filter(fn ($entry) => isset($entry['timestamp']) && Carbon::hasFormat($entry['timestamp'], 'Y-m-d H:i:s'))
            ->reject(fn ($entry) => isset($entry['amonia_ppm']) && is_numeric($entry['amonia_ppm']) && (float) $entry['amonia_ppm'] > config('sensor_alerts.history_max_amonia'))
            ->filter(function ($entry) use ($start, $end) {
                $timestamp = Carbon::createFromFormat('Y-m-d H:i:s', $entry['timestamp'], 'Asia/Jakarta');
                return (! $start || $timestamp->gte($start)) && (! $end || $timestamp->lte($end));
            })
            ->sortBy('timestamp')
            ->map(fn ($entry) => [
                'timestamp' => $entry['timestamp'],
                'suhu' => $entry['suhu'] ?? null,
                'kelembapan' => $entry['kelembapan'] ?? null,
                'amonia_ppm' => $entry['amonia_ppm'] ?? null,
                'thi' => $entry['thi'] ?? null,
            ])->values()->all();

        $labels = ['all' => 'Semua data', 'last_month' => '1 bulan terakhir', 'range' => 'Rentang '.$request->start_date.' s.d. '.$request->end_date];
        return ['rows' => $rows, 'periodLabel' => $labels[$validated['period']], 'suffix' => now()->format('Ymd-His')];
    }

    private function summarizeForPdf(array $rows): array
    {
        $days = [];

        foreach ($rows as $row) {
            $date = substr($row['timestamp'], 0, 10);
            $days[$date] ??= [
                'date' => $date,
                'count' => 0,
                'suhu' => $this->emptyStats(),
                'kelembapan' => $this->emptyStats(),
                'amonia_ppm' => $this->emptyStats(),
                'thi' => $this->emptyStats(),
            ];

            $days[$date]['count']++;
            foreach (['suhu', 'kelembapan', 'amonia_ppm', 'thi'] as $metric) {
                if (is_numeric($row[$metric])) {
                    $value = (float) $row[$metric];
                    $days[$date][$metric]['min'] = min($days[$date][$metric]['min'], $value);
                    $days[$date][$metric]['max'] = max($days[$date][$metric]['max'], $value);
                    $days[$date][$metric]['sum'] += $value;
                    $days[$date][$metric]['count']++;
                }
            }
        }

        return array_map(function (array $day) {
            foreach (['suhu', 'kelembapan', 'amonia_ppm', 'thi'] as $metric) {
                $stats = $day[$metric];
                $day[$metric] = $stats['count'] === 0 ? ['min' => null, 'avg' => null, 'max' => null] : [
                    'min' => $stats['min'],
                    'avg' => $stats['sum'] / $stats['count'],
                    'max' => $stats['max'],
                ];
            }

            return $day;
        }, $days);
    }

    private function emptyStats(): array
    {
        return ['min' => INF, 'max' => -INF, 'sum' => 0, 'count' => 0];
    }
}
