<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class StockExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
{
    protected $search;

    public function __construct($search = '')
    {
        $this->search = $search;
    }

    public function collection()
    {
        return Product::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('sku', 'like', '%' . $this->search . '%'))
            ->orderByRaw('CASE WHEN stock <= 10 THEN 0 ELSE 1 END') // Low stock first
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Product Name',
            'SKU',
            'Current Stock',
            'Status',
            'Price',
            'Category',
            'Last Updated',
        ];
    }

    public function map($product): array
    {
        $status = $product->stock <= 10 ? 'Low Stock' : ($product->stock == 0 ? 'Out of Stock' : 'In Stock');
        
        return [
            $product->name,
            $product->sku,
            $product->stock,
            $status,
            $product->price,
            $product->category ? $product->category->name : '-',
            $product->updated_at->format('d/m/Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                $highestRow = $sheet->getHighestRow();
                
                // Highlight Low Stock rows (assuming Status is column D/4)
                for ($row = 2; $row <= $highestRow; $row++) {
                    $stock = $sheet->getCell('C' . $row)->getValue();
                    if ($stock <= 10) {
                        $sheet->getStyle('A' . $row . ':G' . $row)->getFont()->getColor()->setARGB('FF0000'); // Red text
                    }
                }
            },
        ];
    }
}
