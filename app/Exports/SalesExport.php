<?php

namespace App\Exports;

use App\Models\Sale;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $dateFilter;
    protected $statusFilter;

    public function __construct($dateFilter = null, $statusFilter = null)
    {
        $this->dateFilter = $dateFilter;
        $this->statusFilter = $statusFilter;
    }

    public function collection()
    {
        $query = Sale::with(['customer', 'user'])->latest();

        if ($this->dateFilter) {
            $query->whereDate('created_at', $this->dateFilter);
        }

        if ($this->statusFilter && $this->statusFilter !== 'All Statuses') {
            $query->where('status', strtolower($this->statusFilter));
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Invoice Number',
            'Date',
            'Customer',
            'User',
            'Total Amount',
            'Payment Method',
            'Status',
        ];
    }

    public function map($sale): array
    {
        return [
            $sale->invoice_number,
            $sale->created_at->format('Y-m-d H:i'),
            $sale->customer ? $sale->customer->name : 'Walk-in Customer',
            $sale->user ? $sale->user->name : 'Unknown',
            $sale->total_amount,
            ucfirst($sale->payment_method),
            ucfirst($sale->status),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
