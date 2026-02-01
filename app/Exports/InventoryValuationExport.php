<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryValuationExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function collection()
    {
        return Product::with('category')->get();
    }

    public function headings(): array
    {
        return [
            'Product Name',
            'Category',
            'Stock',
            'Cost Price',
            'Selling Price',
            'Total Cost Value',
            'Total Sales Value',
        ];
    }

    public function map($product): array
    {
        return [
            $product->name,
            $product->category->name ?? 'Uncategorized',
            $product->stock,
            $product->cost,
            $product->price,
            $product->stock * $product->cost,
            $product->stock * $product->price,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
