<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BalanceSheetExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $assets;
    protected $liabilities;
    protected $equity;
    protected $totalAssets;
    protected $totalLiabilities;
    protected $totalEquity;
    protected $date;

    public function __construct($assets, $liabilities, $equity, $totalAssets, $totalLiabilities, $totalEquity, $date)
    {
        $this->assets = $assets;
        $this->liabilities = $liabilities;
        $this->equity = $equity;
        $this->totalAssets = $totalAssets;
        $this->totalLiabilities = $totalLiabilities;
        $this->totalEquity = $totalEquity;
        $this->date = $date;
    }

    public function array(): array
    {
        $data = [];

        // Assets
        $data[] = ['ASSETS', ''];
        foreach ($this->assets as $groupName => $items) {
            $data[] = [$groupName, ''];
            foreach ($items as $item) {
                $data[] = ['  ' . $item['name'], $item['amount']];
            }
        }
        $data[] = ['TOTAL ASSETS', $this->totalAssets];
        $data[] = ['', '']; // Spacer

        // Liabilities
        $data[] = ['LIABILITIES', ''];
        foreach ($this->liabilities as $groupName => $items) {
            $data[] = [$groupName, ''];
            foreach ($items as $item) {
                $data[] = ['  ' . $item['name'], $item['amount']];
            }
        }
        $data[] = ['TOTAL LIABILITIES', $this->totalLiabilities];
        $data[] = ['', '']; // Spacer

        // Equity
        $data[] = ['EQUITY', ''];
        foreach ($this->equity as $groupName => $items) {
            $data[] = [$groupName, ''];
            foreach ($items as $item) {
                $data[] = ['  ' . $item['name'], $item['amount']];
            }
        }
        $data[] = ['TOTAL EQUITY', $this->totalEquity];
        $data[] = ['', '']; // Spacer
        
        $data[] = ['TOTAL LIABILITIES & EQUITY', $this->totalLiabilities + $this->totalEquity];

        return $data;
    }

    public function headings(): array
    {
        return [
            ['Balance Sheet'],
            ['As of ' . $this->date],
            [''],
            ['Description', 'Amount'],
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
