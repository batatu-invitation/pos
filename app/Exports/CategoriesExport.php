<?php

namespace App\Exports;

use App\Models\Category;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CategoriesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function collection()
    {
        return Category::latest()->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Icon',
            'Color',
            'Description',
            'Items Count',
            'Created At',
        ];
    }

    public function map($category): array
    {
        return [
            $category->id,
            $category->name,
            $category->icon,
            $category->color,
            $category->description,
            $category->products()->count(),
            $category->created_at ? $category->created_at->format('d/m/Y') : '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
