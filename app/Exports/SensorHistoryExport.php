<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SensorHistoryExport implements FromArray, WithHeadings
{
    public function __construct(private readonly array $rows) {}

    public function headings(): array
    {
        return ['Waktu', 'Suhu (C)', 'Kelembapan (%)', 'Amonia (ppm)', 'THI'];
    }

    public function array(): array
    {
        return array_map(fn (array $row) => [
            $row['timestamp'], $row['suhu'], $row['kelembapan'], $row['amonia_ppm'], $row['thi'],
        ], $this->rows);
    }
}
