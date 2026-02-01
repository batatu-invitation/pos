<?php

namespace App\Exports;

use App\Models\Emoji;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmojisExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function collection()
    {
        return Emoji::where('tenant_id', auth()->id())
            ->orWhereNull('tenant_id')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Icon',
            'Type',
            'Created At',
        ];
    }

    public function map($emoji): array
    {
        return [
            $emoji->id,
            $emoji->name,
            $emoji->icon,
            $emoji->tenant_id ? 'Custom' : 'Global',
            $emoji->created_at ? $emoji->created_at->format('d/m/Y H:i') : '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
