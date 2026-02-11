<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GeneralLedgerExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $ledgerItems;
    protected $openingBalance;
    protected $closingBalance;
    protected $selectedAccount;
    protected $startDate;
    protected $endDate;

    public function __construct($ledgerItems, $openingBalance, $closingBalance, $selectedAccount, $startDate, $endDate)
    {
        $this->ledgerItems = $ledgerItems;
        $this->openingBalance = $openingBalance;
        $this->closingBalance = $closingBalance;
        $this->selectedAccount = $selectedAccount;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function array(): array
    {
        $data = [];

        // Opening Balance Row
        $data[] = [
            $this->startDate,
            '',
            'Opening Balance',
            '',
            '',
            $this->openingBalance
        ];

        // Transactions
        foreach ($this->ledgerItems as $item) {
            $data[] = [
                $item['date']->format('Y-m-d'),
                $item['reference'],
                $item['description'],
                $item['debit'] > 0 ? $item['debit'] : '',
                $item['credit'] > 0 ? $item['credit'] : '',
                $item['balance']
            ];
        }

        // Closing Balance Row
        $data[] = [
            $this->endDate,
            '',
            'Closing Balance',
            '',
            '',
            $this->closingBalance
        ];

        return $data;
    }

    public function headings(): array
    {
        $accountInfo = $this->selectedAccount 
            ? $this->selectedAccount->code . ' - ' . $this->selectedAccount->name 
            : 'All Accounts';

        return [
            ['General Ledger'],
            ['Account: ' . $accountInfo],
            ['Period: ' . $this->startDate . ' to ' . $this->endDate],
            [''],
            ['Date', 'Reference', 'Description', 'Debit', 'Credit', 'Balance'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true]],
            3 => ['font' => ['italic' => true]],
            5 => ['font' => ['bold' => true]],
        ];
    }
}
