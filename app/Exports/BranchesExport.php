<?php

namespace App\Exports;

use App\Models\Tenant;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BranchesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function collection()
    {
        return Tenant::with('domains')->latest()->get();
    }

    public function headings(): array
    {
        return [
            'Name',
            'Code',
            'Type',
            'Domain',
            'Location',
            'Manager',
            'Phone',
            'Email',
            'Status',
            'Created At',
        ];
    }

    public function map($branch): array
    {
        return [
            $branch->name,
            $branch->code,
            $branch->type,
            $branch->domains->first()?->domain,
            $branch->location,
            $branch->manager,
            $branch->phone,
            $branch->email,
            $branch->status,
            $branch->created_at ? $branch->created_at->format('d/m/Y') : '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
