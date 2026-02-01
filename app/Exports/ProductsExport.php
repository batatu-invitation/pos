<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $search;
    protected $categoryFilter;

    public function __construct($search = '', $categoryFilter = '')
    {
        $this->search = $search;
        $this->categoryFilter = $categoryFilter;
    }

    public function collection()
    {
        return Product::with('category')
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('sku', 'like', '%' . $this->search . '%'))
            ->when($this->categoryFilter, fn($q) => $q->whereHas('category', fn($c) => $c->where('name', $this->categoryFilter)))
            ->latest()
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'SKU',
            'Category',
            'Price',
            'Cost',
            'Margin (%)',
            'Stock',
            'Status',
            'Created At',
        ];
    }

    public function map($product): array
    {
        return [
            $product->id,
            $product->name,
            $product->sku,
            $product->category ? $product->category->name : 'Uncategorized',
            $product->price,
            $product->cost,
            $product->margin,
            $product->stock,
            $product->status,
            $product->created_at->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
