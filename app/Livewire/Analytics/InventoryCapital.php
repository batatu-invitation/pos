<?php

namespace App\Livewire\Analytics;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Barryvdh\DomPDF\Facade\Pdf;

#[Layout('components.layouts.app')]
#[Title('Inventory Capital - Analytics')]
class InventoryCapital extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'name'; // Disarankan default ke name jika untuk laporan
    public $sortDirection = 'asc';

    public function render()
    {
        // 1. Definisikan Base Query dengan Eager Loading untuk menghindari N+1
        $query = Product::query()
            ->with(['category', 'emoji']) // Load relasi kategori dan emoji sekaligus
            ->when($this->search, function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%');
            });

        // 2. Optimasi Agregasi: Hitung semua total dalam 1 query DB (lebih cepat daripada clone)
        $totals = (clone $query)->selectRaw('
            SUM(cost * stock) as total_capital,
            SUM(price * stock) as total_potential_sales,
            SUM((price - cost) * stock) as total_potential_profit,
            SUM(stock) as total_items,
            COUNT(*) as total_products
        ')->first();

        // 3. Gunakan Pagination alih-alih lazy() untuk performa tabel yang lebih baik di UI
        // Jika tetap ingin menampilkan semua tanpa page, gunakan ->get()
        $products = $query->orderBy($this->sortField, $this->sortDirection)
                         ->paginate(50); 

        return view('livewire.analytics.inventory-capital', [
            'products' => $products,
            'totalCapital' => $totals->total_capital ?? 0,
            'totalPotentialSales' => $totals->total_potential_sales ?? 0,
            'totalPotentialProfit' => $totals->total_potential_profit ?? 0,
            'totalItems' => $totals->total_items ?? 0,
            'totalProducts' => $totals->total_products ?? 0,
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

    public function exportPdf()
    {
        // Tetap gunakan Eager Loading di export PDF agar tidak lambat saat render view PDF
        $products = Product::query()
            ->with(['category', 'emoji'])
            ->when($this->search, function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->get();

        // Hitung total dari koleksi yang sudah ada (tidak perlu query lagi)
        $totalCapital = $products->sum(fn($p) => $p->cost * $p->stock);
        $totalPotentialSales = $products->sum(fn($p) => $p->price * $p->stock);
        $totalPotentialProfit = $products->sum(fn($p) => ($p->price - $p->cost) * $p->stock);

        $pdf = Pdf::loadView('pdf.inventory-capital', compact(
            'products', 
            'totalCapital', 
            'totalPotentialSales', 
            'totalPotentialProfit'
        ));

        return response()->streamDownload(fn() => print($pdf->output()), 'inventory-capital.pdf');
    }
}