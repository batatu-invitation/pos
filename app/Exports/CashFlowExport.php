<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CashFlowExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $operatingActivities;
    protected $investingActivities;
    protected $financingActivities;
    protected $startDate;
    protected $endDate;

    public function __construct($operatingActivities, $investingActivities, $financingActivities, $startDate, $endDate)
    {
        $this->operatingActivities = $operatingActivities;
        $this->investingActivities = $investingActivities;
        $this->financingActivities = $financingActivities;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function array(): array
    {
        $data = [];

        $totalOperating = $this->calculateTotal($this->operatingActivities);
        $totalInvesting = $this->calculateTotal($this->investingActivities);
        $totalFinancing = $this->calculateTotal($this->financingActivities);
        $netCashFlow = $totalOperating + $totalInvesting + $totalFinancing;

        // Operating
        $data[] = ['OPERATING ACTIVITIES', ''];
        foreach ($this->operatingActivities as $activity) {
             $amount = $activity['amount'];
             if ($activity['type'] === 'negative') {
                 $amount = -$amount;
             }
             $data[] = ['  ' . $activity['name'], $amount];
        }
        $data[] = ['Net Cash from Operating Activities', $totalOperating];
        $data[] = ['', ''];

        // Investing
        $data[] = ['INVESTING ACTIVITIES', ''];
        foreach ($this->investingActivities as $activity) {
             $amount = $activity['amount'];
             if ($activity['type'] === 'negative') {
                 $amount = -$amount;
             }
             $data[] = ['  ' . $activity['name'], $amount];
        }
        $data[] = ['Net Cash from Investing Activities', $totalInvesting];
        $data[] = ['', ''];

        // Financing
        $data[] = ['FINANCING ACTIVITIES', ''];
        foreach ($this->financingActivities as $activity) {
             $amount = $activity['amount'];
             if ($activity['type'] === 'negative') {
                 $amount = -$amount;
             }
             $data[] = ['  ' . $activity['name'], $amount];
        }
        $data[] = ['Net Cash from Financing Activities', $totalFinancing];
        $data[] = ['', ''];

        $data[] = ['NET INCREASE/DECREASE IN CASH', $netCashFlow];

        return $data;
    }

    protected function calculateTotal($activities)
    {
        $total = 0;
        foreach ($activities as $activity) {
            if ($activity['type'] === 'positive') {
                $total += $activity['amount'];
            } else {
                $total -= $activity['amount'];
            }
        }
        return $total;
    }

    public function headings(): array
    {
        return [
            ['Cash Flow Statement'],
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
