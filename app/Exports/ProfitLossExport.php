<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProfitLossExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $revenueItems;
    protected $expenseItems;
    protected $totalRevenue;
    protected $totalExpenses;
    protected $netProfit;
    protected $startDate;
    protected $endDate;

    public function __construct($revenueItems, $expenseItems, $totalRevenue, $totalExpenses, $netProfit, $startDate, $endDate)
    {
        $this->revenueItems = $revenueItems;
        $this->expenseItems = $expenseItems;
        $this->totalRevenue = $totalRevenue;
        $this->totalExpenses = $totalExpenses;
        $this->netProfit = $netProfit;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function array(): array
    {
        $data = [];

        // Revenue Section
        $data[] = ['REVENUE', ''];
        foreach ($this->revenueItems as $item) {
            $data[] = ['  ' . $item['name'], $item['amount']];
        }
        $data[] = ['TOTAL REVENUE', $this->totalRevenue];
        $data[] = ['', '']; // Spacer

        // Expenses Section
        $data[] = ['EXPENSES', ''];
        foreach ($this->expenseItems as $item) {
            $data[] = ['  ' . $item['name'], $item['amount']];
        }
        $data[] = ['TOTAL EXPENSES', $this->totalExpenses];
        $data[] = ['', '']; // Spacer

        // Net Profit
        $data[] = ['NET PROFIT', $this->netProfit];

        return $data;
    }

    public function headings(): array
    {
        return [
            ['Profit & Loss Statement'],
            ['Period: ' . $this->startDate . ' to ' . $this->endDate],
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
