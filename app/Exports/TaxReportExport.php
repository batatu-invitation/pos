<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TaxReportExport implements FromArray, WithHeadings, WithMapping
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Tax Name',
            'Rate (%)',
            'Taxable Amount',
            'Tax Amount',
        ];
    }

    public function map($row): array
    {
        return [
            $row['name'],
            $row['rate'],
            $row['taxable'],
            $row['tax'],
        ];
    }
}
