<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class KpisExport implements FromArray, WithHeadings
{
    private array $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function array(): array
    {
        if (empty($this->rows)) {
            return [];
        }

        $first = reset($this->rows);
        if (is_array($first)) {
            return $this->rows;
        }

        return array_map(function ($label, $value) {
            return [$label, $value];
        }, array_keys($this->rows), $this->rows);
    }

    public function headings(): array
    {
        return ['Métrica', 'Valor'];
    }
}
