<?php

namespace App\Livewire\Analytics;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\InventoryCapitalExport;
use Barryvdh\DomPDF\Facade\Pdf;

#[Layout('components.layouts.app')]
#[Title('Inventory Capital - Analytics')]
class InventoryCapital extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    public function render()
    {
        $query = Product::query();

        // Calculate Total Capital (Global for the user)
        $totalCapital = (clone $query)->sum(DB::raw('cost * stock'));
        $totalPotentialSales = (clone $query)->sum(DB::raw('price * stock'));
        $totalPotentialProfit = (clone $query)->sum(DB::raw('(price - cost) * stock'));
        
        $totalItems = (clone $query)->sum('stock');
        $totalProducts = (clone $query)->count();

        // Filter for table
        $products = $query->paginate(10);

        return view('livewire.analytics.inventory-capital', [
            'products' => $products,
            'totalCapital' => $totalCapital,
            'totalPotentialSales' => $totalPotentialSales,
            'totalPotentialProfit' => $totalPotentialProfit,
            'totalItems' => $totalItems,
            'totalProducts' => $totalProducts,
        ]);
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function exportExcel()
    {
        // Implementation for Excel export can be added here
        // return Excel::download(new InventoryCapitalExport, 'inventory_capital.xlsx');
    }

    public function exportPdf()
    {
        $products = Product::query()
            ->when($this->search, function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->get();

        $totalCapital = $products->sum(function($product) {
            return $product->cost * $product->stock;
        });

        $totalPotentialSales = $products->sum(function($product) {
            return $product->price * $product->stock;
        });

        $totalPotentialProfit = $products->sum(function($product) {
            return ($product->price - $product->cost) * $product->stock;
        });

        $pdf = Pdf::loadView('pdf.inventory-capital', compact('products', 'totalCapital', 'totalPotentialSales', 'totalPotentialProfit'));
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'inventory-capital.pdf');
    }
}
