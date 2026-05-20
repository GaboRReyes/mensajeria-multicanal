<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class KpisExport implements FromArray
{
    public function __construct(
        private array $data
    ) {}

    public function array(): array
    {
        return [
            ['Reporte KPIs'],

            [],

            ['Volumen'],

            ['Canal', 'Estado', 'Total'],

            ...collect($this->data['volumen'])
                ->map(fn ($item) => [
                    $item['channel'],
                    $item['status'],
                    $item['total']
                ])
                ->toArray()
        ];
    }
}