<?php

namespace App\Exports;

use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SuppliersExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function collection()
    {
        return Supplier::latest()->get();
    }

    public function headings(): array
    {
        return [
            'Name',
            'Contact Person',
            'Email',
            'Phone',
            'Address',
            'Status',
            'Created At'
        ];
    }

    public function map($supplier): array
    {
        return [
            $supplier->name,
            $supplier->contact_person,
            $supplier->email,
            $supplier->phone,
            $supplier->address,
            $supplier->status,
            $supplier->created_at ? $supplier->created_at->format('d/m/Y') : '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
