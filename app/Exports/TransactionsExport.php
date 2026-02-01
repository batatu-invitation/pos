<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class TransactionsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithColumnFormatting
{
    protected $search;
    protected $typeFilter;

    public function __construct($search = '', $typeFilter = '')
    {
        $this->search = $search;
        $this->typeFilter = $typeFilter;
    }

    public function collection()
    {
        return Transaction::query()
            ->when($this->search, fn($q) => $q->where(function($sub) {
                $sub->where('description', 'like', '%'.$this->search.'%')
                    ->orWhere('category', 'like', '%'.$this->search.'%')
                    ->orWhere('reference_number', 'like', '%'.$this->search.'%');
            }))
            ->when($this->typeFilter && $this->typeFilter !== 'All Types', fn($q) => $q->where('type', $this->typeFilter))
            ->latest('date')
            ->latest('created_at')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Date',
            'Type',
            'Category',
            'Description',
            'Reference',
            'Payment Method',
            'Amount',
            'Status',
        ];
    }

    public function map($transaction): array
    {
        return [
            $transaction->date->format('d/m/Y'),
            ucfirst($transaction->type),
            $transaction->category,
            $transaction->description,
            $transaction->reference_number,
            $transaction->payment_method,
            $transaction->amount,
            ucfirst($transaction->status),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'G' => '#,##0', // Amount column
        ];
    }
}
