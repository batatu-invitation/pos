<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TrialBalanceExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $report;
    protected $asOfDate;

    public function __construct($report, $asOfDate)
    {
        $this->report = $report;
        $this->asOfDate = $asOfDate;
    }

    public function array(): array
    {
        $data = [];

        foreach ($this->report['lines'] as $line) {
            $data[] = [
                $line['code'],
                $line['name'],
                $line['debit'] > 0 ? $line['debit'] : '',
                $line['credit'] > 0 ? $line['credit'] : '',
            ];
        }

        // Totals Row
        $data[] = ['', '', '', '']; // Spacer
        $data[] = [
            'TOTAL',
            '',
            $this->report['total_debit'],
            $this->report['total_credit']
        ];

        return $data;
    }

    public function headings(): array
    {
        return [
            ['Trial Balance'],
            ['As of ' . $this->asOfDate],
            [''],
            ['Account Code', 'Account Name', 'Debit', 'Credit'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['italic' => true]],
            4 => ['font' => ['bold' => true]],
        ];
    }
}
