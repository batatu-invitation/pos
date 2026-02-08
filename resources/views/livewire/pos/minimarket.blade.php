<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Tax;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;
use Livewire\Attributes\On;

new #[Layout('components.layouts.pos')] #[Title('Mini Market POS - Modern POS')] class extends Component {
    use WithPagination;

    public $search = '';
    public $categoryFilter = null;
    public $cart = []; // [productId => [id, name, price, quantity, image, stock]]
    public $selectedCustomerId = null;
    public $selectedCustomerName = 'Walk-in Customer';
    public $customerSearch = '';
    public $showCustomerSearch = false;
    public $taxRate = 0.10;
    public $paymentMethod = 'cash';

    // Order Details
    public $note = '';
    public $discount = 0;
    public $shipping = 0;

    // Modals
    public $showNoteModal = false;
    public $showShippingModal = false;
    public $showDiscountModal = false;
    public $showCreateCustomerModal = false;
    public $showHeldOrdersModal = false;

    // New Customer
    public $newCustomer = [
        'name' => '',
        'email' => '',
        'phone' => '',
        'address' => '',
    ];

    public function mount()
    {
        $this->selectedCustomerName = __('Walk-in Customer');

        // Check for held order restoration
        if (session()->has('restored_order')) {
            $data = session('restored_order');
            $this->cart = $data['cart'];
            $this->selectedCustomerId = $data['customer_id'];
            $this->selectedCustomerName = $data['customer_name'];
            $this->note = $data['note'] ?? '';
            $this->discount = $data['discount'] ?? 0;
            $this->shipping = $data['shipping'] ?? 0;
            session()->forget('restored_order');
            $this->dispatch('notify', __('Order restored successfully!'));
        }
    }

    public function with()
    {
        $productsQuery = Product::query();

        if ($this->search) {
            $productsQuery->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->categoryFilter) {
            $productsQuery->where('category_id', $this->categoryFilter);
        }

        $customers = [];
        if ($this->customerSearch) {
            $customers = Customer::where('name', 'like', '%' . $this->customerSearch . '%')
                ->orWhere('phone', 'like', '%' . $this->customerSearch . '%')
                ->take(5)
                ->get();
        }

        return [
            'products' => $productsQuery->latest()->paginate(15),
            'categories' => Category::all(),
            'customers' => $customers,
        ];
    }

    public function filterCategory($categoryId)
    {
        $this->categoryFilter = $categoryId;
        $this->resetPage();
    }

    public function addToCart($productId)
    {
        $product = Product::find($productId);
        if (!$product) return;

        if ($product->stock <= 0) {
            $this->dispatch('notify', __('Product is out of stock!'));
            return;
        }

        if (isset($this->cart[$productId])) {
            if ($this->cart[$productId]['quantity'] < $product->stock) {
                $this->cart[$productId]['quantity']++;
            } else {
                 $this->dispatch('notify', __('Not enough stock!'));
            }
        } else {
            $this->cart[$productId] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => 1,
                'image' => $product->image,
                'stock' => $product->stock
            ];
        }
    }

    public function updateQuantity($productId, $change)
    {
        if (!isset($this->cart[$productId])) return;

        $newQuantity = $this->cart[$productId]['quantity'] + $change;

        if ($newQuantity > 0) {
             if ($newQuantity <= $this->cart[$productId]['stock']) {
                $this->cart[$productId]['quantity'] = $newQuantity;
             } else {
                 $this->dispatch('notify', __('Not enough stock!'));
             }
        } else {
            unset($this->cart[$productId]);
        }
    }

    public function removeFromCart($productId)
    {
        unset($this->cart[$productId]);
    }

    public function selectCustomer($customerId, $customerName)
    {
        $this->selectedCustomerId = $customerId;
        $this->selectedCustomerName = $customerName;
        $this->showCustomerSearch = false;
        $this->customerSearch = '';
    }

    public function removeCustomer()
    {
         $this->selectedCustomerId = null;
         $this->selectedCustomerName = __('Walk-in Customer');
    }

    public function getSubtotalProperty()
    {
        return collect($this->cart)->sum(fn($item) => $item['price'] * $item['quantity']);
    }

    public function getTaxProperty()
    {
        $taxRate = Tax::where('is_active', true)->sum('rate');
        $taxableAmount = max(0, $this->subtotal - $this->discount);
        return $taxableAmount * ($taxRate / 100);
    }

    public function getTotalProperty()
    {
        return max(0, $this->subtotal - $this->discount) + $this->tax + $this->shipping;
    }

    public function saveNewCustomer()
    {
        $this->validate([
            'newCustomer.name' => 'required|string|max:255',
            'newCustomer.email' => 'nullable|email|max:255',
            'newCustomer.phone' => 'nullable|string|max:20',
        ]);

        $customer = Customer::create([
            'name' => $this->newCustomer['name'],
            'email' => $this->newCustomer['email'],
            'phone' => $this->newCustomer['phone'],
            'address' => $this->newCustomer['address'],
        ]);

        $this->selectCustomer($customer->id, $customer->name);
        $this->showCreateCustomerModal = false;
        $this->newCustomer = ['name' => '', 'email' => '', 'phone' => '', 'address' => ''];
        $this->dispatch('notify', 'Customer created successfully!');
    }

    public function holdOrder()
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', 'Cart is empty!');
            return;
        }

        DB::transaction(function () {
            $sale = Sale::create([
                'invoice_number' => 'HOLD-' . strtoupper(uniqid()),
                'user_id' => auth()->id(),
                'customer_id' => $this->selectedCustomerId,
                'subtotal' => $this->subtotal,
                'tax' => $this->tax,
                'discount' => $this->discount,
                'total_amount' => $this->total,
                'cash_received' => 0,
                'change_amount' => 0,
                'payment_method' => 'other',
                'status' => 'held',
                'notes' => $this->note,
            ]);

            foreach ($this->cart as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['id'],
                    'product_name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total_price' => $item['price'] * $item['quantity'],
                ]);
            }
        });

        $this->reset(['cart', 'selectedCustomerId', 'selectedCustomerName', 'note', 'discount', 'shipping']);
        $this->dispatch('notify', 'Order held successfully!');
    }

    public function restoreOrder($saleId)
    {
        $sale = Sale::with('items.product', 'customer')->find($saleId);

        if (!$sale) {
            $this->dispatch('notify', 'Order not found!');
            return;
        }

        $this->cart = [];
        foreach ($sale->items as $item) {
            $this->cart[$item->product_id] = [
                'id' => $item->product_id,
                'name' => $item->product ? $item->product->name : 'Unknown Product',
                'price' => $item->price,
                'quantity' => $item->quantity,
                'image' => $item->product ? $item->product->image : null,
                'stock' => $item->product ? $item->product->stock : 0,
            ];
        }

        $this->selectedCustomerId = $sale->customer_id;
        $this->selectedCustomerName = $sale->customer ? $sale->customer->name : 'Walk-in Customer';
        $this->note = $sale->notes;
        $this->discount = $sale->discount;
        $this->shipping = 0;

        $sale->forceDelete();

        $this->showHeldOrdersModal = false;
        $this->dispatch('notify', 'Order restored!');
    }

    public function checkout()
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', 'Cart is empty!');
            return;
        }

        session([
            'pos_cart' => $this->cart,
            'pos_subtotal' => $this->subtotal,
            'pos_tax' => $this->tax,
            'pos_total' => $this->total,
            'pos_customer_id' => $this->selectedCustomerId,
            'pos_customer_name' => $this->selectedCustomerName,
            'pos_discount' => $this->discount,
        ]);

        return $this->redirect(route('pos.payment'), navigate: true);
    }
};
?>

<div class="flex h-full">

    <!-- Left Section: Product Table -->
    <div class="flex-1 flex flex-col h-full overflow-hidden">
        <!-- Header -->
        <header class="bg-white p-4 shadow-sm z-10 flex items-center justify-between dark:bg-gray-800 dark:border-b dark:border-gray-700">
            <div class="flex items-center space-x-4 w-full">
                <a wire:navigate href="{{ route('dashboard') }}" class="p-2 rounded-2xl bg-gradient-to-br from-indigo-50 to-indigo-100 text-indigo-600 hover:from-indigo-100 hover:to-indigo-200 transition-all shadow-sm dark:from-indigo-900/30 dark:to-indigo-800/30 dark:text-indigo-400">
                    <i class="fas fa-arrow-left"></i>
                </a>

                <div class="relative flex-1 max-w-2xl">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                        <i class="fas fa-search text-gray-400 dark:text-gray-500"></i>
                    </span>
                    <input type="text" wire:model.live.debounce.300ms="search" id="search-input" class="w-full py-2.5 pl-10 pr-12 bg-gray-100 border-none rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 transition-colors dark:bg-gray-700 dark:text-gray-300 dark:placeholder-gray-500" placeholder="{{ __('Scan barcode (F2) or search products...') }}" autofocus>
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <i class="fas fa-barcode text-gray-500 dark:text-gray-400 cursor-pointer"></i>
                    </span>
                </div>

                <div class="hidden md:flex items-center space-x-3">
                    <!-- Extra Buttons -->
                    <div class="flex items-center space-x-1 mr-2 border-r border-gray-200 dark:border-gray-600 pr-2">
                        <button onclick="toggleFullscreen()" class="p-2 text-gray-400 hover:text-indigo-600 transition-colors rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700" title="{{ __('Toggle Fullscreen') }}">
                            <i class="fas fa-expand text-lg"></i>
                        </button>
                        <button onclick="connectDevice('printer')" id="btn-printer" class="relative p-2 text-gray-400 hover:text-indigo-600 transition-colors rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 group" title="{{ __('Connect Printer') }}">
                            <i class="fas fa-print text-lg"></i>
                            <span id="status-printer" class="absolute top-1.5 right-1.5 h-2 w-2 bg-red-500 rounded-full border border-white dark:border-gray-800"></span>
                        </button>
                        <button onclick="connectDevice('scanner')" id="btn-scanner" class="relative p-2 text-gray-400 hover:text-indigo-600 transition-colors rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 group" title="{{ __('Connect Scanner') }}">
                            <i class="fas fa-barcode text-lg"></i>
                            <span id="status-scanner" class="absolute top-1.5 right-1.5 h-2 w-2 bg-red-500 rounded-full border border-white dark:border-gray-800"></span>
                        </button>
                        <button wire:click="$set('showHeldOrdersModal', true)" class="p-2 text-gray-400 hover:text-indigo-600 transition-colors rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700" title="{{ __('Held Orders') }}">
                            <i class="fas fa-clock text-lg"></i>
                        </button>
                    </div>

                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        <span class="font-bold text-gray-700 dark:text-gray-300">F2</span> {{ __('Focus Search') }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        <span class="font-bold text-gray-700 dark:text-gray-300">F4</span> {{ __('Pay') }}
                    </div>
                </div>
            </div>
        </header>

        <!-- Product Table Container -->
        <div class="flex-1 overflow-hidden p-4 md:p-6 flex flex-col">
            <div class="bg-white rounded-3xl shadow-lg border border-gray-200 flex-1 overflow-hidden flex flex-col dark:bg-gray-800 dark:border-gray-700">
                <!-- Table Header -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 sticky top-0 z-10">
                            <tr>
                                <th class="p-4 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase border-b border-gray-200 dark:border-gray-600">{{ __('Barcode') }}</th>
                                <th class="p-4 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase border-b border-gray-200 dark:border-gray-600">{{ __('Item Name') }}</th>
                                <th class="p-4 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase border-b border-gray-200 dark:border-gray-600">{{ __('Category') }}</th>
                                <th class="p-4 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase border-b border-gray-200 dark:border-gray-600 text-right">{{ __('Stock') }}</th>
                                <th class="p-4 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase border-b border-gray-200 dark:border-gray-600 text-right">{{ __('Price') }}</th>
                                <th class="p-4 text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase border-b border-gray-200 dark:border-gray-600 text-center">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700" id="product-table-body">
                            @forelse($products as $product)
                            <tr wire:click="addToCart('{{ $product->id }}')" class="hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors cursor-pointer group">
                                <td class="p-4 text-sm text-gray-600 dark:text-gray-400 font-mono">{{ $product->sku ?? $product->id }}</td>
                                <td class="p-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                    <div class="flex items-center">
                                        @if($product->image)
                                            <img src="{{ Storage::url($product->image) }}" class="w-8 h-8 rounded-xl object-cover mr-2 shadow-sm">
                                        @endif
                                        {{ $product->name }}
                                    </div>
                                </td>
                                <td class="p-4 text-sm text-gray-500 dark:text-gray-400">{{ $product->category ? $product->category->name : '-' }}</td>
                                <td class="p-4 text-sm text-gray-600 dark:text-gray-400 text-right">{{ $product->stock }}</td>
                                <td class="p-4 text-sm font-bold text-gray-900 dark:text-gray-100 text-right">Rp. {{ number_format($product->price, 2) }}</td>
                                <td class="p-4 text-center">
                                    <button wire:click.stop="addToCart('{{ $product->id }}')" class="p-2 bg-gradient-to-r from-indigo-100 to-purple-100 text-indigo-600 rounded-xl hover:from-indigo-600 hover:to-purple-600 hover:text-white transition-all shadow-sm">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-gray-500 dark:text-gray-400">
                                {{ __('No products found.') }}
                            </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-gray-200 dark:border-gray-600">
                    {{ $products->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Right Section: Cart -->
    <div class="w-96 bg-white border-l border-gray-200 flex flex-col h-full shadow-xl z-20 dark:bg-gray-800 dark:border-gray-700">
        <!-- Customer & Options -->
        <div class="p-4 border-b border-gray-200 bg-gray-50 dark:bg-gray-700/50 dark:border-gray-600">
            <div class="flex items-center justify-between mb-3 relative">
                <div class="flex items-center space-x-2 bg-white px-3 py-1.5 rounded-xl border border-gray-300 cursor-pointer hover:border-indigo-500 transition-colors flex-1 mr-2 dark:bg-gray-800 dark:border-gray-600 dark:hover:border-indigo-400"
                     x-data="{ open: false }" @click.outside="open = false">

                    <div class="flex items-center flex-1" @click="open = !open">
                         <i class="fas fa-user text-indigo-600 mr-2 dark:text-indigo-400"></i>
                         <span class="text-sm font-medium text-gray-700 truncate dark:text-gray-300">{{ $selectedCustomerName }}</span>
                    </div>
                    @if($selectedCustomerId)
                        <button wire:click="removeCustomer" class="text-gray-400 hover:text-red-500 dark:text-gray-500 dark:hover:text-red-400"><i class="fas fa-times"></i></button>
                    @endif

                    <!-- Customer Search Dropdown -->
                    <div x-show="open" class="absolute top-full left-0 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-xl z-50 p-2 dark:bg-gray-800 dark:border-gray-700" style="display: none;">
                        <input type="text" wire:model.live.debounce.300ms="customerSearch" class="w-full text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 mb-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" placeholder="{{ __('Search customer...') }}">
                        <ul class="max-h-40 overflow-y-auto">
                            @foreach($customers as $customer)
                                <li wire:click="selectCustomer('{{ $customer->id }}', '{{ $customer->name }}')" class="p-2 hover:bg-gray-50 cursor-pointer rounded-lg flex items-center dark:hover:bg-gray-700">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-100 to-indigo-200 flex items-center justify-center text-indigo-600 mr-2 font-bold text-xs dark:from-indigo-900 dark:to-indigo-800 dark:text-indigo-300">
                                        {{ substr($customer->name, 0, 1) }}
                                    </div>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $customer->name }}</span>
                                </li>
                            @endforeach
                            @if(empty($customers) && $customerSearch)
                                <li class="p-2 text-xs text-gray-500 text-center dark:text-gray-400">{{ __('No customers found') }}</li>
                            @endif
                        </ul>
                    </div>
                </div>

                <button wire:click="$set('showCreateCustomerModal', true)" class="p-2 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 text-indigo-600 transition-colors dark:bg-gray-800 dark:border-gray-600 dark:hover:bg-gray-700">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            <div class="grid grid-cols-3 gap-2">
                <button wire:click="$set('showNoteModal', true)" class="flex items-center justify-center px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-xs font-medium text-gray-600 hover:bg-gray-50 transition-colors dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                    <i class="fas fa-sticky-note mr-1"></i> {{ __('Note') }}
                </button>
                <button wire:click="$set('showShippingModal', true)" class="flex items-center justify-center px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-xs font-medium text-gray-600 hover:bg-gray-50 transition-colors dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                    <i class="fas fa-truck mr-1"></i> {{ __('Shipping') }}
                </button>
                <button wire:click="$set('showDiscountModal', true)" class="flex items-center justify-center px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-xs font-medium text-gray-600 hover:bg-gray-50 transition-colors dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                    <i class="fas fa-tag mr-1"></i> {{ __('Discount') }}
                </button>
            </div>
        </div>

        <!-- Cart Items -->
        <div class="flex-1 overflow-y-auto p-4 space-y-3 dark:bg-gray-800">
            @forelse($cart as $item)
            <div class="flex items-start justify-between pb-3 border-b border-gray-100 dark:border-gray-700">
                <div class="flex items-start space-x-3">
                    @if($item['image'])
                         <img src="{{ Storage::url($item['image']) }}" class="w-10 h-10 rounded-xl object-cover shadow-sm" alt="Item">
                    @else
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center text-gray-400 shadow-sm dark:from-gray-600 dark:to-gray-700">
                            <i class="fas fa-box"></i>
                        </div>
                    @endif
                    <div>
                        <h4 class="text-sm font-bold text-gray-800 truncate max-w-[120px] dark:text-gray-100">{{ $item['name'] }}</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Rp. {{ number_format($item['price'], 2) }} / ea</p>
                    </div>
                </div>
                <div class="flex flex-col items-end">
                    <span class="text-sm font-bold text-gray-800 dark:text-gray-100">Rp. {{ number_format($item['price'] * $item['quantity'], 2) }}</span>
                    <div class="flex items-center mt-1 bg-gray-100 rounded-xl shadow-sm dark:bg-gray-700">
                        <button wire:click="updateQuantity('{{ $item['id'] }}', -1)" class="w-6 h-6 flex items-center justify-center text-gray-500 hover:text-red-500 transition-colors dark:text-gray-400">-</button>
                        <span class="text-xs font-medium w-4 text-center dark:text-gray-300">{{ $item['quantity'] }}</span>
                        <button wire:click="updateQuantity('{{ $item['id'] }}', 1)" class="w-6 h-6 flex items-center justify-center text-gray-500 hover:text-green-500 transition-colors dark:text-gray-400">+</button>
                    </div>
                </div>
            </div>
            @empty
            <div class="flex flex-col items-center justify-center h-40 text-gray-400 dark:text-gray-500">
                <i class="fas fa-shopping-basket text-4xl mb-2"></i>
                <p class="text-sm">{{ __('Cart is empty') }}</p>
            </div>
            @endforelse
        </div>

        <!-- Footer / Totals -->
        <div class="bg-gray-50 p-4 border-t border-gray-200 dark:bg-gray-700/50 dark:border-gray-600">
            <div class="space-y-2 mb-4">
                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                    <span>{{ __('Subtotal') }}</span>
                    <span>Rp. {{ number_format($this->subtotal, 2) }}</span>
                </div>
                @if($this->discount > 0)
                <div class="flex justify-between text-sm text-green-600 dark:text-green-400">
                    <span>{{ __('Discount') }}</span>
                    <span>-Rp. {{ number_format($this->discount, 2) }}</span>
                </div>
                @endif
                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                    <span>{{ __('Tax (10%)') }}</span>
                    <span>Rp. {{ number_format($this->tax, 2) }}</span>
                </div>
                @if($this->shipping > 0)
                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                    <span>{{ __('Shipping') }}</span>
                    <span>Rp. {{ number_format($this->shipping, 2) }}</span>
                </div>
                @endif
                <div class="flex justify-between text-base font-bold text-gray-900 dark:text-gray-100 border-t border-gray-200 pt-2 dark:border-gray-600">
                    <span>{{ __('Total Payable') }}</span>
                    <span>Rp. {{ number_format($this->total, 2) }}</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-3">
                <button wire:click="$set('cart', [])" class="py-3 rounded-xl border border-red-200 text-red-600 font-medium text-sm hover:bg-red-50 transition-colors dark:bg-red-900/20 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/30">
                    {{ __('Cancel') }}
                </button>
                <button wire:click="holdOrder" class="py-3 rounded-xl border border-indigo-200 text-indigo-600 font-medium text-sm hover:bg-indigo-50 transition-colors dark:bg-indigo-900/20 dark:border-indigo-700 dark:text-indigo-400 dark:hover:bg-indigo-900/30">
                    {{ __('Hold Order') }}
                </button>
            </div>

            <button wire:click="checkout" class="block w-full py-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl font-bold text-lg text-center shadow-lg shadow-indigo-200/50 transition-all transform hover:-translate-y-0.5 dark:shadow-none">
                {{ __('Pay Now') }} Rp. {{ number_format($this->total, 2) }} (F4)
            </button>
        </div>
    </div>
</div>

<!-- Modals -->
<!-- Note Modal -->
@if($showNoteModal)
<div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showNoteModal', false)"></div>
        <div class="bg-white rounded-3xl overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full z-10 dark:bg-gray-800">
            <div class="px-4 py-5 sm:p-6 dark:bg-gray-800">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4 dark:text-gray-100">{{ __('Add Note') }}</h3>
                <textarea wire:model="note" rows="4" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-xl dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" placeholder="{{ __('Enter order notes...') }}"></textarea>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse dark:bg-gray-700/50">
                <button wire:click="$set('showNoteModal', false)" type="button" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-base font-medium text-white hover:from-indigo-700 hover:to-purple-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    {{ __('Save Note') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Shipping Modal -->
@if($showShippingModal)
<div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showShippingModal', false)"></div>
        <div class="bg-white rounded-3xl overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full z-10 dark:bg-gray-800">
            <div class="px-4 py-5 sm:p-6 dark:bg-gray-800">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4 dark:text-gray-100">{{ __('Shipping Cost') }}</h3>
                <input type="number" wire:model.live="shipping" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-xl dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" placeholder="{{ __('Enter shipping cost') }}">
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse dark:bg-gray-700/50">
                <button wire:click="$set('showShippingModal', false)" type="button" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-base font-medium text-white hover:from-indigo-700 hover:to-purple-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    {{ __('Save') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Discount Modal -->
@if($showDiscountModal)
<div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showDiscountModal', false)"></div>
        <div class="bg-white rounded-3xl overflow-hidden shadow-xl transform transition-all sm:max-w-sm sm:w-full z-10 dark:bg-gray-800">
            <div class="px-4 py-5 sm:p-6 dark:bg-gray-800">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4 dark:text-gray-100">{{ __('Discount') }}</h3>
                <div class="relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm dark:text-gray-400">Rp</span>
                    </div>
                    <input type="number" wire:model.live="discount" step="0.01" class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-9 sm:text-sm border-gray-300 rounded-xl dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" placeholder="0.00">
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse dark:bg-gray-700/50">
                <button wire:click="$set('showDiscountModal', false)" type="button" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-base font-medium text-white hover:from-indigo-700 hover:to-purple-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    {{ __('Close') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Create Customer Modal -->
@if($showCreateCustomerModal)
<div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showCreateCustomerModal', false)"></div>
        <div class="bg-white rounded-3xl overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full z-10 dark:bg-gray-800">
            <div class="px-4 py-5 sm:p-6 dark:bg-gray-800">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4 dark:text-gray-100">{{ __('Create New Customer') }}</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Name') }}</label>
                        <input type="text" wire:model="newCustomer.name" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                        @error('newCustomer.name') <span class="text-red-500 dark:text-red-400 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Email') }}</label>
                        <input type="email" wire:model="newCustomer.email" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                        @error('newCustomer.email') <span class="text-red-500 dark:text-red-400 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Phone') }}</label>
                        <input type="text" wire:model="newCustomer.phone" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                        @error('newCustomer.phone') <span class="text-red-500 dark:text-red-400 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Address') }}</label>
                        <textarea wire:model="newCustomer.address" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"></textarea>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse dark:bg-gray-700/50">
                <button wire:click="saveNewCustomer" type="button" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-base font-medium text-white hover:from-indigo-700 hover:to-purple-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    {{ __('Save Customer') }}
                </button>
                <button wire:click="$set('showCreateCustomerModal', false)" type="button" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600">
                    {{ __('Cancel') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Held Orders Modal -->
@if($showHeldOrdersModal)
<div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showHeldOrdersModal', false)"></div>
        <div class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:max-w-4xl sm:w-full z-10">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">{{ __('Held Orders') }}</h3>
                    <button wire:click="$set('showHeldOrdersModal', false)" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Date') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Customer') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Items') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Total') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Note') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @php
                                $heldOrders = \App\Models\Sale::where('status', 'held')->with('customer')->latest()->get();
                            @endphp
                            @forelse($heldOrders as $order)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $order->created_at->format('M d, H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $order->customer ? $order->customer->name : 'Walk-in Customer' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $order->items->count() }} items
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                        Rp. {{ number_format($order->total_amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 truncate max-w-xs">
                                        {{ $order->notes }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button wire:click="restoreOrder('{{ $order->id }}')" class="text-indigo-600 hover:text-indigo-900">{{ __('Restore') }}</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">{{ __('No held orders found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<script>
    // Keyboard Shortcuts
    document.addEventListener('keydown', function(event) {
        // F2 to focus search
        if (event.key === 'F2') {
            event.preventDefault();
            document.getElementById('search-input').focus();
        }
        // F4 to pay
        if (event.key === 'F4') {
            event.preventDefault();
            @this.call('checkout');
        }
    });
</script>
