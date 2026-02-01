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
use App\Models\ApplicationSetting;

new #[Layout('components.layouts.pos')] #[Title('Visual POS - Modern POS')] class extends Component {
    use WithPagination;

    public $search = '';
    public $categoryFilter = null;
    public $cart = []; // [productId => [id, name, price, quantity, image, stock]]
    public $selectedCustomerId = null;
    public $selectedCustomerName = '';
    public $customerSearch = '';
    public $showCustomerSearch = false;
    public $selectedTaxId = null;
    public $taxRate = 0;

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
    public $showPaymentModal = false;
    public $showReceiptModal = false;
    public $lastSale = null;

    // Payment State
    public $receivedAmount = '';
    public $paymentMethod = 'cash';
    public $paymentMethods = [];

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

        $this->paymentMethods = [
            ['id' => 'cash', 'name' => __('Cash'), 'icon' => 'fa-money-bill-wave', 'color' => 'indigo'],
            ['id' => 'card', 'name' => __('Credit/Debit Card'), 'icon' => 'fa-credit-card', 'color' => 'gray'],
            ['id' => 'qr', 'name' => __('QR Code'), 'icon' => 'fa-qrcode', 'color' => 'gray'],
            ['id' => 'ewallet', 'name' => __('E-Wallet'), 'icon' => 'fa-wallet', 'color' => 'gray'],
        ];

        // Check for held order restoration from query parameter
        if (request()->has('restore')) {
            $sale = Sale::with(['items', 'customer'])->find(request()->get('restore'));

            if ($sale && $sale->status === 'held') {
                $this->cart = [];
                foreach ($sale->items as $item) {
                    $this->cart[$item->product_id] = [
                        'id' => $item->product_id,
                        'name' => $item->product_name,
                        'price' => $item->price,
                        'quantity' => $item->quantity,
                        'image' => $item->product->image ?? null,
                        'stock' => $item->product->stock ?? 0
                    ];
                }
                $this->selectedCustomerId = $sale->customer_id;
                $this->selectedCustomerName = $sale->customer ? $sale->customer->name : __('Walk-in Customer');
                $this->note = $sale->notes ?? '';
                $this->discount = $sale->discount ?? 0;
                // Shipping is not in the create method, but if it was added to model, we could restore it.
                // Assuming shipping is 0 for now as it wasn't in the create method visible in search results.
                $this->shipping = 0;

                $this->dispatch('notify', __('Held order restored successfully!'));
            }
        }
        // Check for held order restoration from session
        elseif (session()->has('restored_order')) {
            $data = session('restored_order');
            $this->cart = $data['cart'];
            $this->selectedCustomerId = $data['customer_id'];
            $this->selectedCustomerName = $data['customer_name'];
            $this->note = $data['note'] ?? '';
            $this->discount = $data['discount'] ?? 0;
            $this->shipping = $data['shipping'] ?? 0;
            session()->forget('restored_order');
            $this->dispatch('notify', __('Order restored successfully!'));
        } else {
            $this->selectedCustomerName = __('Walk-in Customer');
        }
    }

    public function with()
    {
        $productsQuery = Product::query();

        if ($this->search) {
            $productsQuery->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')->orWhere('sku', 'like', '%' . $this->search . '%');
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

        $taxes = Tax::where('user_id', auth()->id())->where('is_active', true)->get();

        if (!$this->selectedTaxId && $taxes->isNotEmpty()) {
            $this->selectedTaxId = $taxes->first()->id;
        }

        $selectedTax = $taxes->firstWhere('id', $this->selectedTaxId);
        $this->taxRate = $selectedTax ? $selectedTax->rate : 0;

        return [
            'products' => $productsQuery->where('stock', '>', 0)->latest()->paginate(15),
            'categories' => Category::all(),
            'customers' => $customers,
            'taxes' => $taxes,
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
        if (!$product) {
            return;
        }

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
                'stock' => $product->stock,
            ];
        }
    }

    public function updateQuantity($productId, $change)
    {
        if (!isset($this->cart[$productId])) {
            return;
        }

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
        $subtotal = (float) $this->subtotal;
        $discount = (float) $this->discount;

        $taxableAmount = max(0, $subtotal - $discount);
        return $taxableAmount * ($this->taxRate / 100);
    }

    public function getTotalProperty()
    {
        $subtotal = floatval($this->subtotal ?: 0);
        $discount = floatval($this->discount ?: 0);
        $tax = floatval($this->tax ?: 0);
        $shipping = floatval($this->shipping ?: 0);

        return max(0, $subtotal - $discount) + $tax + $shipping;
    }

    public function saveNewCustomer()
    {
        $user = auth()->user();

        $hasSettings = ApplicationSetting::where('user_id', $user->created_by)->exists();

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
            'user_id' => $user->created_by ? $user->created_by : $user->id,
            'input_id' => $user->id,
        ]);

        $this->selectCustomer($customer->id, $customer->name);
        $this->showCreateCustomerModal = false;
        $this->newCustomer = ['name' => '', 'email' => '', 'phone' => '', 'address' => ''];
        $this->dispatch('notify', __('Customer created successfully!'));
    }

    public function holdOrder()
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', __('Cart is empty!'));
            return;
        }


        DB::transaction(function () {
            $user = auth()->user();
            // dd($user);

            // $hasSettings = ApplicationSetting::where('user_id', $user->created_by)->exists();

            $sale = Sale::create([
                'invoice_number' => 'HOLD-' . strtoupper(uniqid()),
                'user_id' => $user->created_by ? $user->created_by : $user->id,
                'input_id' => $user->id,
                'customer_id' => $this->selectedCustomerId,
                'subtotal' => $this->subtotal,
                'tax_id' => $this->selectedTaxId,
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
        $this->dispatch('notify', __('Order held successfully!'));
    }

    public function restoreOrder($saleId)
    {
        $sale = Sale::with('items.product', 'customer')->find($saleId);

        if (!$sale) {
            $this->dispatch('notify', __('Order not found!'));
            return;
        }

        $this->cart = [];
        foreach ($sale->items as $item) {
            $this->cart[$item->product_id] = [
                'id' => $item->product_id,
                'name' => $item->product ? $item->product->name : __('Unknown Product'),
                'price' => $item->price,
                'quantity' => $item->quantity,
                'image' => $item->product ? $item->product->image : null,
                'stock' => $item->product ? $item->product->stock : 0,
            ];
        }

        $this->selectedCustomerId = $sale->customer_id;
        $this->selectedCustomerName = $sale->customer ? $sale->customer->name : __('Walk-in Customer');
        $this->selectedTaxId = $sale->tax_id;
        $this->note = $sale->notes;
        $this->discount = $sale->discount;
        // Shipping is not in Sale model yet, assuming 0 for restored orders unless we add it
        $this->shipping = 0;

        // Delete the held order or mark as restored (here we delete to prevent duplicates)
        $sale->forceDelete();
        // Or better: $sale->update(['status' => 'cancelled']); but standard POS flow often deletes held order when picked up.
        // Let's use soft delete or force delete. Since we have SoftDeletes trait, delete() is soft.
        // $sale->delete();
        // But if we delete it, it won't show in held orders.

        $this->showHeldOrdersModal = false;
        $this->dispatch('notify', __('Order restored!'));
    }

    public function selectPaymentMethod($methodId)
    {
        $this->paymentMethod = $methodId;
    }

    public function setReceivedAmount($amount)
    {
        $this->receivedAmount = number_format($amount, 0, '.', '');
    }

    public function getChangeProperty()
    {
        $received = floatval($this->receivedAmount);
        return max(0, $received - $this->total);
    }

    public function getHeldOrdersProperty()
    {
        return Sale::where('status', 'held')->with('customer')->latest()->get();
    }

    public function completePayment()
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', __('Cart is empty!'));
            return;
        }

        $sale = DB::transaction(function () {
            $sale = Sale::create([
                'invoice_number' => 'INV-' . strtoupper(uniqid()),
                'user_id' => auth()->id(),
                'customer_id' => $this->selectedCustomerId,
                'subtotal' => $this->subtotal,
                'tax_id' => $this->selectedTaxId,
                'tax' => $this->tax,
                'discount' => $this->discount,
                'total_amount' => $this->total,
                'cash_received' => floatval($this->receivedAmount),
                'change_amount' => $this->change,
                'payment_method' => $this->paymentMethod,
                'status' => 'completed',
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

                $product = Product::find($item['id']);
                if ($product) {
                    $product->decrement('stock', $item['quantity']);
                }
            }

            return $sale;
        });

        $this->lastSale = $sale;
        $this->showPaymentModal = false;
        $this->showReceiptModal = true;
        $this->reset(['cart', 'selectedCustomerId', 'selectedCustomerName', 'note', 'discount', 'shipping', 'receivedAmount', 'paymentMethod']);
        $this->dispatch('notify', __('Payment completed successfully!'));
    }

    public function newSale()
    {
        $this->showReceiptModal = false;
        $this->lastSale = null;
        $this->reset(['cart', 'selectedCustomerId', 'selectedCustomerName', 'note', 'discount', 'shipping', 'receivedAmount', 'paymentMethod']);
    }

    public function checkout()
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', __('Cart is empty!'));
            return;
        }

        $this->receivedAmount = number_format($this->total, 0, '.', '');
        $this->showPaymentModal = true;
    }
};
?>

<div class="flex h-full">

    <!-- Left Section: Products -->
    <div class="flex-1 flex flex-col h-full overflow-hidden">
        <!-- Header -->
        <header class="bg-white p-4 shadow-sm z-10 flex items-center justify-between">
            <div class="flex items-center space-x-4 w-full">
                <a wire:navigate href="{{ route('dashboard') }}"
                    class="p-2 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors">
                    <i class="fas fa-arrow-left"></i>
                </a>

                <div class="relative flex-1 max-w-lg">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                        <i class="fas fa-search text-gray-400"></i>
                    </span>
                    <input type="text" wire:model.live.debounce.300ms="search"
                        class="w-full py-2.5 pl-10 pr-4 bg-gray-100 border-none rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 transition-colors"
                        placeholder="{{ __('Scan barcode or search products...') }}">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <i class="fas fa-barcode text-gray-500 cursor-pointer"></i>
                    </span>
                </div>
                <livewire:components.device-toolbar />
                <div class="hidden md:flex space-x-2 overflow-x-auto no-scrollbar">

                    <button wire:click="filterCategory(null)"
                        class="px-4 py-2 {{ is_null($categoryFilter) ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }} rounded-lg text-sm font-medium whitespace-nowrap shadow-sm transition-colors">{{ __('All Items') }}</button>
                    <div class="flex flex-nowrap space-x-2 overflow-x-auto no-scrollbar md:max-w-[440px]">
                        @foreach ($categories as $category)
                            <button wire:click="filterCategory('{{ $category->id }}')"
                                class="px-4 py-2 {{ $categoryFilter == $category->id ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }} rounded-lg text-sm font-medium whitespace-nowrap transition-colors">{{ $category->name }}</button>
                        @endforeach
                    </div>
                </div>
            </div>
        </header>

        <!-- Product Grid -->
        <div class="flex-1 overflow-y-auto p-4 md:p-6" id="product-grid">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                @forelse($products as $product)
                    <div wire:click="addToCart('{{ $product->id }}')"
                        class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow cursor-pointer group">
                        <div class="relative h-32 overflow-hidden bg-gray-100">
                            @if ($product->image)
                                <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-gray-200 text-gray-400">
                                    <i class="fas fa-image text-3xl"></i>
                                </div>
                            @endif
                            <span
                                class="absolute top-2 right-2 bg-indigo-600 text-white text-xs font-bold px-2 py-1 rounded shadow-sm">Rp.
                                {{ number_format($product->price, 0, ',', '.') }}</span>
                        </div>
                        <div class="p-3">
                            <h3 class="text-sm font-bold text-gray-800 truncate">{{ $product->name }}</h3>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ $product->stock > 0 ? $product->stock . ' ' . __('in stock') : __('Out of stock') }}
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-10 text-gray-500">
                        <i class="fas fa-box-open text-4xl mb-2"></i>
                        <p>{{ __('No products found.') }}</p>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $products->links() }}
            </div>
        </div>
    </div>

    <!-- Right Section: Cart -->
    <div class="w-96 bg-white border-l border-gray-200 flex flex-col h-full shadow-xl z-20">
        <!-- Customer & Options -->
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <div class="flex items-center justify-between mb-3 relative">
                <div class="flex items-center space-x-2 bg-white px-3 py-2.5 rounded-lg border border-gray-300 cursor-pointer hover:border-indigo-500 transition-colors flex-1 mr-2"
                    x-data="{ open: false }" @click.outside="open = false">

                    <div class="flex items-center flex-1" @click="open = !open">
                        <i class="fas fa-user text-indigo-600 mr-2"></i>
                        <span class="text-sm font-medium text-gray-700 truncate">{{ $selectedCustomerName }}</span>
                    </div>
                    @if ($selectedCustomerId)
                        <button wire:click="removeCustomer" class="text-gray-400 hover:text-red-500"><i
                                class="fas fa-times"></i></button>
                    @endif

                    <!-- Customer Search Dropdown -->
                    <div x-show="open"
                        class="absolute top-full left-0 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-50 p-2"
                        style="display: none;">
                        <input type="text" wire:model.live.debounce.300ms="customerSearch"
                            class="w-full text-sm p-4 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 mb-2"
                            placeholder="{{ __('Search customer...') }}">
                        <ul class="max-h-40 overflow-y-auto">
                            @foreach ($customers as $customer)
                                <li wire:click="selectCustomer('{{ $customer->id }}', '{{ $customer->name }}')"
                                    class="p-2 hover:bg-gray-50 cursor-pointer rounded flex items-center">
                                    <div
                                        class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 mr-2 font-bold text-xs">
                                        {{ substr($customer->name, 0, 1) }}
                                    </div>
                                    <span class="text-sm text-gray-700">{{ $customer->name }}</span>
                                </li>
                            @endforeach
                            @if (empty($customers) && $customerSearch)
                                <li class="p-2 text-xs text-gray-500 text-center">{{ __('No customers found') }}</li>
                            @endif
                        </ul>
                    </div>
                </div>

                <button wire:click="$set('showCreateCustomerModal', true)"
                    class="p-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-indigo-600">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            <div class="grid grid-cols-3 gap-2">
                <button wire:click="$set('showNoteModal', true)"
                    class="flex items-center justify-center px-3 py-1.5 bg-white border border-gray-300 rounded text-xs font-medium text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-sticky-note mr-1"></i> {{ __('Note') }}
                </button>
                <button wire:click="$set('showShippingModal', true)"
                    class="flex items-center justify-center px-3 py-1.5 bg-white border border-gray-300 rounded text-xs font-medium text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-truck mr-1"></i> {{ __('Shipping') }}
                </button>
                <button wire:click="$set('showDiscountModal', true)"
                    class="flex items-center justify-center px-3 py-1.5 bg-white border border-gray-300 rounded text-xs font-medium text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-percent mr-1"></i> {{ __('Discount') }}
                </button>
            </div>
        </div>

        <!-- Cart Items -->
        <div class="flex-1 overflow-y-auto p-4 space-y-3">
            @forelse($cart as $item)
                <div class="flex items-start justify-between pb-3 border-b border-gray-100">
                    <div class="flex items-start space-x-3">
                        @if ($item['image'])
                            <img src="{{ Storage::url($item['image']) }}" class="w-12 h-12 rounded-lg object-cover" alt="Item">
                        @else
                            <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400">
                                <i class="fas fa-image"></i>
                            </div>
                        @endif
                        <div>
                            <h4 class="text-sm font-bold text-gray-800 truncate max-w-[120px]">{{ $item['name'] }}
                            </h4>
                            <p class="text-xs text-gray-500">Rp. {{ number_format($item['price'], 0, ',', '.') }} </p>
                        </div>
                    </div>
                    <div class="flex flex-col items-end">
                        <span class="text-sm font-bold text-gray-800">Rp.
                            {{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}</span>
                        <div class="flex items-center mt-1 bg-gray-100 rounded-lg">
                            <button wire:click="updateQuantity('{{ $item['id'] }}', -1)"
                                class="w-6 h-6 flex items-center justify-center text-gray-500 hover:text-red-500 transition-colors">-</button>
                            <span class="text-xs font-medium w-4 text-center">{{ $item['quantity'] }}</span>
                            <button wire:click="updateQuantity('{{ $item['id'] }}', 1)"
                                class="w-6 h-6 flex items-center justify-center text-gray-500 hover:text-green-500 transition-colors">+</button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="h-full flex flex-col items-center justify-center text-gray-400">
                    <i class="fas fa-shopping-cart text-4xl mb-2"></i>
                    <p class="text-sm">{{ __('Cart is empty. Scan an item to start.') }}</p>
                </div>
            @endforelse
        </div>

        <!-- Footer / Totals -->
        <div class="bg-gray-50 p-4 border-t border-gray-200">
            <div class="space-y-2 mb-4">
                <div class="flex justify-between text-sm text-gray-600">
                    <span>{{ __('Subtotal') }}</span>
                    <span>Rp. {{ number_format($this->subtotal, 0, ',', '.') }}</span>
                </div>
                @if ($this->discount > 0)
                    <div class="flex justify-between text-sm text-red-500">
                        <span>{{ __('Discount') }}</span>
                        <span>-Rp. {{ number_format($this->discount, 0, ',', '.') }}</span>
                    </div>
                @endif
                <div class="flex justify-between items-center text-sm text-gray-600">
                    <div class="flex items-center">
                        <span class="mr-2">{{ __('Tax') }}</span>
                        @if($taxes->count() > 1)
                            <select wire:model.live="selectedTaxId"
                                class="text-xs border-gray-300 rounded p-1 pr-6 py-0 focus:ring-indigo-500 focus:border-indigo-500">
                                @foreach($taxes as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }} ({{ number_format($t->rate, 0) }}%)</option>
                                @endforeach
                            </select>
                        @else
                            <span>({{ number_format($this->taxRate, 0) }}%)</span>
                        @endif
                    </div>
                    <span>Rp. {{ number_format($this->tax, 0, ',', '.') }}</span>
                </div>
                @if ($this->shipping > 0)
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>{{ __('Shipping') }}</span>
                        <span>Rp. {{ number_format($this->shipping, 0, ',', '.') }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-base font-bold text-gray-900 border-t border-gray-200 pt-2">
                    <span>{{ __('Total Payable') }}</span>
                    <span>Rp. {{ number_format($this->total, 0, ',', '.') }}</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-3">
                <button wire:click="$set('cart', [])"
                    class="py-3 rounded-lg border border-red-200 text-red-600 font-medium text-sm hover:bg-red-50 transition-colors">
                    {{ __('Cancel') }}
                </button>
                <button wire:click="holdOrder"
                    class="py-3 rounded-lg border border-indigo-200 text-indigo-600 font-medium text-sm hover:bg-indigo-50 transition-colors">
                    {{ __('Held Orders') }}
                </button>
            </div>

            <button wire:click="checkout" wire:loading.attr="disabled"
                class="block w-full py-4 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white rounded-xl font-bold text-lg text-center shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-0.5">
                <span wire:loading.remove>{{ __('Pay Now') }} Rp. {{ number_format($this->total, 0, ',', '.') }}</span>
                <span wire:loading>{{ __('Processing...') }}</span>
            </button>
        </div>
    </div>

    <!-- Note Modal -->
    @if ($showNoteModal)
        <div wire:transition.opacity class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
            aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
                    wire:click="$set('showNoteModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">{{ __('Order Note') }}</h3>
                        <div class="mt-2">
                            <textarea wire:model="note" rows="4"
                                class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm px-4 py-2 border border-gray-300 rounded-md"
                                placeholder="{{ __('Add a note to this order...') }}"></textarea>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" wire:click="$set('showNoteModal', false)"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            {{ __('Save Note') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Shipping Modal -->
    @if ($showShippingModal)
        <div wire:transition.opacity class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
            aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
                    wire:click="$set('showShippingModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">{{ __('Shipping Cost') }}
                        </h3>
                        <div class="mt-2">
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">Rp. </span>
                                </div>
                                <input type="text" wire:model.live.="shipping" step="0.01"
                                    class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-9  p-4 sm:text-sm border border-gray-300 rounded-md"
                                    placeholder="0.00">
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" wire:click="$set('showShippingModal', false)"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            {{ __('Close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Discount Modal -->
    @if ($showDiscountModal)
        <div wire:transition.opacity class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
            aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
                    wire:click="$set('showDiscountModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">{{ __('Discount') }}</h3>
                        <div class="mt-2">
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">Rp. </span>
                                </div>
                                <input type="text" x-on:input="$el.value = $el.value.replace(/[^0-9]/g, '')"
                                    wire:model.live.debounce.500ms="discount" step="0.01"
                                    class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-9  p-4 sm:text-sm  border border-gray-300 rounded-md"
                                    placeholder="0.00">
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" wire:click="$set('showDiscountModal', false)"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            {{ __('Close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Create Customer Modal -->
    @if ($showCreateCustomerModal)
        <div wire:transition.opacity class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
            aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
                    wire:click="$set('showCreateCustomerModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                            {{ __('New Customer') }}
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">{{ __('Name') }}</label>
                                <input type="text" wire:model="newCustomer.name"
                                    class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm p-4 border border-gray-300 rounded-md">
                                @error('newCustomer.name')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">{{ __('Email') }}</label>
                                <input type="email" wire:model="newCustomer.email"
                                    class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm p-4 border border-gray-300 rounded-md">
                                @error('newCustomer.email')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">{{ __('Phone') }}</label>
                                <input type="text" wire:model="newCustomer.phone"
                                    class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm p-4 border border-gray-300 rounded-md">
                                @error('newCustomer.phone')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">{{ __('Address') }}</label>
                                <textarea wire:model="newCustomer.address" rows="2"
                                    class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm p-4 border border-gray-300 rounded-md"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" wire:click="saveNewCustomer"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            {{ __('Save Customer') }}
                        </button>
                        <button type="button" wire:click="$set('showCreateCustomerModal', false)"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Held Orders Modal -->
    @if ($showHeldOrdersModal)
        <div wire:transition.opacity class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
            aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
                    wire:click="$set('showHeldOrdersModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                            {{ __('Held Orders') }}</h3>
                        <div class="overflow-y-auto max-h-96">
                            @forelse($this->heldOrders as $order)
                                <div class="flex items-center justify-between p-4 mb-2 border rounded-lg hover:bg-gray-50">
                                    <div>
                                        <p class="font-bold text-gray-800">{{ $order->invoice_number }}</p>
                                        <p class="text-sm text-gray-500">{{ $order->created_at->diffForHumans() }} -
                                            {{ $order->customer ? $order->customer->name : __('Walk-in') }}
                                        </p>
                                        <p class="text-xs text-gray-400">Total: Rp.
                                            {{ number_format($order->total_amount, 0, ',', '.') }} | Note: {{ $order->notes }}
                                        </p>
                                    </div>
                                    <button wire:click="restoreOrder('{{ $order->id }}')"
                                        class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-md hover:bg-indigo-200 text-sm font-medium">
                                        {{ __('Restore') }}
                                    </button>
                                </div>
                            @empty
                                <div class="text-center text-gray-500 py-8">
                                    <i class="fas fa-box-open text-4xl mb-2"></i>
                                    <p>{{ __('No held orders found.') }}</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" wire:click="$set('showHeldOrdersModal', false)"
                            class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            {{ __('Close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Payment Modal -->
    @if ($showPaymentModal)
        <div wire:transition.opacity class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
            aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
                    wire:click="$set('showPaymentModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full">

                    <div class="flex flex-col md:flex-row h-[80vh]">
                        <!-- Left: Order Summary -->
                        <div class="w-full md:w-1/3 bg-gray-50 border-r border-gray-200 p-6 flex flex-col">
                            <h2 class="text-xl font-bold text-gray-800 mb-6">{{ __('Order Summary') }}</h2>

                            <div class="mb-4 p-3 bg-white rounded-lg border border-gray-200 shadow-sm">
                                <p class="text-xs text-gray-500 uppercase font-bold tracking-wider mb-1">
                                    {{ __('Customer') }}</p>
                                <div class="flex items-center text-gray-800 font-medium">
                                    <i class="fas fa-user-circle text-indigo-500 mr-2 text-lg"></i>
                                    {{ $selectedCustomerName }}
                                </div>
                            </div>

                            <div class="flex-1 overflow-y-auto space-y-4 pr-2">
                                @foreach($cart as $item)
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="font-medium text-gray-800">{{ $item['name'] }}</p>
                                            <p class="text-sm text-gray-500">{{ $item['quantity'] }} x Rp.
                                                {{ number_format($item['price'], 0, ',', '.') }}</p>
                                        </div>
                                        <span class="font-bold text-gray-800">Rp.
                                            {{ number_format($item['quantity'] * $item['price'], 0, ',', '.') }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <div class="border-t border-gray-200 pt-4 mt-4 space-y-2">
                                <div class="flex justify-between text-gray-600">
                                    <span>{{ __('Subtotal') }}</span>
                                    <span>Rp. {{ number_format($this->subtotal, 0, ',', '.') }}</span>
                                </div>
                                @if($discount > 0)
                                    <div class="flex justify-between text-red-500">
                                        <span>{{ __('Discount') }}</span>
                                        <span>-Rp. {{ number_format($discount, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                                <div class="flex justify-between text-gray-600">
                                    <span>{{ __('Tax') }}</span>
                                    <span>Rp. {{ number_format($this->tax, 0, ',', '.') }}</span>
                                </div>
                                @if($shipping > 0)
                                    <div class="flex justify-between text-gray-600">
                                        <span>{{ __('Shipping') }}</span>
                                        <span>Rp. {{ number_format($shipping, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                                <div class="flex justify-between text-2xl font-bold text-gray-900 pt-2">
                                    <span>{{ __('Total') }}</span>
                                    <span>Rp. {{ number_format($this->total, 0, ',', '.') }}</span>
                                </div>
                            </div>

                            <button wire:click="$set('showPaymentModal', false)"
                                class="mt-6 text-center text-indigo-600 font-medium hover:text-indigo-800">
                                <i class="fas fa-arrow-left mr-2"></i> {{ __('Back to POS') }}
                            </button>
                        </div>

                        <!-- Right: Payment Interface -->
                        <div class="w-full md:w-2/3 p-6 flex flex-col">
                            <!-- Payment Methods -->
                            <div class="grid grid-cols-4 gap-4 mb-8 overflow-y-auto max-h-[240px]">
                                @foreach($paymentMethods as $method)
                                    <button wire:click="selectPaymentMethod('{{ $method['id'] }}')"
                                        class="flex flex-col items-center justify-center p-4 rounded-xl border-2 {{ $paymentMethod == $method['id'] ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-gray-200 text-gray-600 hover:border-indigo-300 hover:bg-gray-50' }} transition-all">
                                        <i class="fas {{ $method['icon'] }} text-2xl mb-2"></i>
                                        <span class="font-medium text-center text-sm">{{ $method['name'] }}</span>
                                    </button>
                                @endforeach
                            </div>

                            <!-- Amount Input Section -->
                            <div class="flex-1 flex flex-col justify-center max-w-md mx-auto w-full">

                                <div class="mb-6">
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2">{{ __('Received Amount') }}</label>
                                    <div class="relative">
                                        <span
                                            class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-500 text-xl font-bold">Rp.</span>
                                        <input type="number" step="0.01" wire:model.live="receivedAmount"
                                            class="w-full pl-12 pr-4 py-4 text-3xl font-bold text-gray-900 bg-gray-50 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                </div>

                                <div class="grid grid-cols-4 gap-3 mb-6">
                                    <button wire:click="setReceivedAmount({{ $this->total }})"
                                        class="py-2 px-4 bg-white border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-100 font-medium">{{ __('Exact') }}</button>
                                    <button wire:click="setReceivedAmount({{ ceil($this->total / 1000) * 1000 }})"
                                        class="py-2 px-4 bg-white border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-100 font-medium">{{ number_format(ceil($this->total / 1000) * 1000, 0) }}</button>
                                    <button wire:click="setReceivedAmount({{ ceil($this->total / 5000) * 5000 }})"
                                        class="py-2 px-4 bg-white border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-100 font-medium">{{ number_format(ceil($this->total / 5000) * 5000, 0) }}</button>
                                    <button wire:click="setReceivedAmount({{ ceil($this->total / 10000) * 10000 }})"
                                        class="py-2 px-4 bg-white border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-100 font-medium">{{ number_format(ceil($this->total / 10000) * 10000, 0) }}</button>
                                </div>

                                <div
                                    class="bg-green-50 rounded-xl p-4 flex justify-between items-center mb-8 border border-green-100">
                                    <span class="text-green-800 font-medium">{{ __('Change Return') }}</span>
                                    <span class="text-2xl font-bold text-green-700">Rp.
                                        {{ number_format($this->change, 0, ',', '.') }}</span>
                                </div>

                                <button onclick="confirmPayment()" wire:loading.attr="disabled"
                                    class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white rounded-xl font-bold text-xl text-center shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-1">
                                    <span wire:loading.remove>{{ __('Complete Payment') }}</span>
                                    <span wire:loading>{{ __('Processing...') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    @endif

    <!-- Receipt Modal -->
    @if ($showReceiptModal && $lastSale)
        <div wire:transition.opacity class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
            aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div
                    class="inline-block align-bottom transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">

                    <!-- Printable Receipt Section (Hidden on Screen) -->
                    <div id="printable-area"
                        class="hidden print:block bg-white p-4 max-w-[80mm] mx-auto text-black font-mono text-sm text-left">
                        <div class="text-center mb-4">
                            <h2 class="text-xl font-bold uppercase">POS Pro Store</h2>
                            <p class="text-xs">123 Business Street, City, Country</p>
                            <p class="text-xs">Tel: +1 234 567 890</p>
                        </div>

                        <div class="border-b-2 border-dashed border-black my-2"></div>

                        <div class="flex justify-between text-xs mb-2">
                            <span>{{ __('Date') }}: {{ $lastSale->created_at->format('Y-m-d') }}</span>
                            <span>{{ __('Time') }}: {{ $lastSale->created_at->format('h:i A') }}</span>
                        </div>
                        <div class="flex justify-between text-xs mb-2">
                            <span>{{ __('Order') }}: {{ $lastSale->invoice_number }}</span>
                            <span>{{ __('Cashier') }}: {{ auth()->user()->name ?? 'Admin' }}</span>
                        </div>

                        <div class="border-b-2 border-dashed border-black my-2"></div>

                        <div class="flex flex-col gap-1 text-xs">
                            @foreach($lastSale->items as $item)
                                <div class="flex justify-between">
                                    <span>{{ $item->quantity }} x {{ $item->product_name }}</span>
                                    <span>Rp. {{ number_format($item->total_price, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="border-b-2 border-dashed border-black my-2"></div>

                        <div class="flex justify-between font-bold text-sm">
                            <span>{{ __('TOTAL') }}</span>
                            <span>Rp. {{ number_format($lastSale->total_amount, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-xs mt-1">
                            <span>{{ __('CASH') }}</span>
                            <span>Rp. {{ number_format($lastSale->cash_received, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-xs mt-1">
                            <span>{{ __('CHANGE') }}</span>
                            <span>Rp. {{ number_format($lastSale->change_amount, 0, ',', '.') }}</span>
                        </div>

                        <div class="border-b-2 border-dashed border-black my-4"></div>

                        <div class="text-center text-xs">
                            <p class="mb-2">{{ __('Thank you for your purchase!') }}</p>
                            <p>{{ __('Please visit us again.') }}</p>
                            <div class="mt-4 mx-auto max-w-[150px]">
                                <!-- Barcode Placeholder -->
                                <div class="h-8 bg-black"></div>
                                <p class="text-[10px] mt-1">{{ $lastSale->invoice_number }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Screen UI -->
                    <div class="print:hidden">
                        <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-6 text-left">
                            <div class="bg-green-500 p-8 text-center text-white">
                                <div
                                    class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4 backdrop-blur-sm">
                                    <i class="fas fa-check text-3xl"></i>
                                </div>
                                <h2 class="text-2xl font-bold">{{ __('Payment Successful!') }}</h2>
                                <p class="text-green-100 mt-1">{{ __('Transaction') }} {{ $lastSale->invoice_number }}</p>
                            </div>

                            <div class="p-8">
                                <div class="text-center mb-6">
                                    <p class="text-gray-500 text-sm">{{ __('Total Paid') }}</p>
                                    <p class="text-3xl font-bold text-gray-900">Rp.
                                        {{ number_format($lastSale->total_amount, 0, ',', '.') }}</p>
                                    <p class="text-gray-400 text-xs mt-1">{{ __('Via') }}
                                        {{ ucfirst($lastSale->payment_method) }}</p>
                                </div>

                                <div class="border-t border-b border-gray-100 py-4 mb-6 space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">{{ __('Date') }}</span>
                                        <span
                                            class="font-medium text-gray-900">{{ $lastSale->created_at->format('M d, Y, h:i A') }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">{{ __('Customer') }}</span>
                                        <span
                                            class="font-medium text-gray-900">{{ $lastSale->customer ? $lastSale->customer->name : __('Walk-in Customer') }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">{{ __('Cashier') }}</span>
                                        <span class="font-medium text-gray-900">{{ auth()->user()->name ?? 'Admin' }}</span>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <button onclick="printReceipt('{{ route('pos.receipt.print', $lastSale->id) }}')"
                                        class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold shadow-lg transition-colors flex items-center justify-center">
                                        <i class="fas fa-print mr-2"></i> {{ __('Print Receipt (PDF)') }}
                                    </button>
                                    <button
                                        class="w-full py-3 bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 rounded-xl font-medium transition-colors flex items-center justify-center">
                                        <i class="fas fa-envelope mr-2"></i> {{ __('Email Receipt') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <button wire:click="newSale"
                                class="inline-flex items-center text-white hover:text-gray-200 font-medium">
                                <i class="fas fa-arrow-left mr-2"></i> {{ __('New Sale') }}
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    @endif
    <script>
        function printReceipt(url) {
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = url;
            document.body.appendChild(iframe);
            // Clean up after 1 minute to allow time for loading and printing
            setTimeout(() => {
                document.body.removeChild(iframe);
            }, 60000);
        }
    </script>
    <script>
        function confirmPayment() {
            Swal.fire({
                title: '{{ __('Confirm Payment?') }}',
                text: "{{ __('Are you sure you want to complete this transaction?') }}",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#ef4444',
                confirmButtonText: '{{ __('Yes, Complete Payment!') }}',
                cancelButtonText: '{{ __('Cancel') }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    @this.call('completePayment');
                }
            });
        }

        document.addEventListener('livewire:initialized', () => {
            Livewire.on('notify', (message) => {
                const msg = Array.isArray(message) ? message[0] : message;
                // Assuming Swal is available globally as in other components
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: '{{ __('Info') }}',
                        text: msg,
                        icon: 'info',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    alert(msg);
                }
            });
        });
    </script>
</div>