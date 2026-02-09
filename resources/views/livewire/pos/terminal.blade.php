<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\Tax;
use App\Models\ApplicationSetting;
use App\Models\User;
use App\Models\BalanceHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\On;

new
#[Layout('components.layouts.pos')]
#[Title('POS Terminal - Modern POS')]
class extends Component
{
    public $barcode = '';
    public $cart = []; // [productId => [id, code, name, price, quantity, discount, subtotal, stock]]
    public $selectedCustomerId = null;
    public $selectedCustomerName = 'Walk-in Customer';
    
    // Transaction Data
    public $selectedTaxId = null;
    public $taxRate = 0;
    public $discountBill = 0;
    public $note = '';
    
    // Payment
    public $receivedAmount = '';
    // public $changeAmount = 0; // Removed in favor of computed property
    public $showPaymentModal = false;
    public $paymentMethod = 'cash';
    public $paymentMethods = [];
    public $lastSale = null;
    public $showReceiptModal = false;

    public $functionKeys = [];

    public function mount()
    {
        $this->initializeFunctionKeys();
        $this->loadDefaultTax();
        $this->paymentMethods = [
            ['id' => 'cash', 'name' => __('Cash'), 'icon' => 'fa-money-bill-wave', 'color' => 'indigo'],
            ['id' => 'card', 'name' => __('Credit/Debit Card'), 'icon' => 'fa-credit-card', 'color' => 'gray'],
            ['id' => 'qr', 'name' => __('QR Code'), 'icon' => 'fa-qrcode', 'color' => 'gray'],
            ['id' => 'ewallet', 'name' => __('E-Wallet'), 'icon' => 'fa-wallet', 'color' => 'gray'],
        ];
    }

    public function initializeFunctionKeys()
    {
        $this->functionKeys = [
            ['key' => 'F1', 'label' => __('New Trans'), 'action' => 'newTransaction', 'color' => 'indigo'],
            ['key' => 'F2', 'label' => __('Search Item'), 'action' => 'focusSearch', 'color' => 'indigo'],
            ['key' => 'F3', 'label' => __('Qty'), 'action' => 'editQty', 'color' => 'indigo'],
            ['key' => 'F4', 'label' => __('Disc Item'), 'action' => 'discountItem', 'color' => 'indigo'],
            ['key' => 'F5', 'label' => __('Disc Bill'), 'action' => 'discountBill', 'color' => 'indigo'],
            ['key' => 'F6', 'label' => __('Void Item'), 'action' => 'voidItem', 'color' => 'indigo'],
            ['key' => 'F7', 'label' => __('Member'), 'action' => 'selectMember', 'color' => 'indigo'],
            ['key' => 'F8', 'label' => __('Pending'), 'action' => 'pendingTransaction', 'color' => 'indigo'],
            ['key' => 'F9', 'label' => __('Recall'), 'action' => 'recallTransaction', 'color' => 'indigo'],
            ['key' => 'F10', 'label' => __('Drawer'), 'action' => 'openDrawer', 'color' => 'indigo'],
            ['key' => 'F11', 'label' => __('Reprint'), 'action' => 'reprintReceipt', 'color' => 'indigo'],
            ['key' => 'F12', 'label' => __('Payment'), 'action' => 'processPayment', 'color' => 'indigo'],
        ];
    }

    public function loadDefaultTax()
    {
        $tax = Tax::where('user_id', auth()->id())->where('is_active', true)->first();
        if ($tax) {
            $this->taxRate = $tax->rate;
            $this->selectedTaxId = $tax->id;
        } else {
            $this->taxRate = 0;
            $this->selectedTaxId = null;
        }
    }

    // Modals & Selection
    public $showProductSearchModal = false;
    public $showDiscountModal = false;
    public $showMemberModal = false;
    public $showCreateCustomerModal = false;
    public $showHeldOrdersModal = false;
    public $discountType = 'item'; // 'item' or 'bill'
    public $selectedItemId = null; // For item actions
    public $tempDiscount = 0; // For modal input
    
    // Member Search
    public $memberSearch = '';
    public $memberResults = [];

    // New Customer
    public $newCustomer = [
        'name' => '',
        'email' => '',
        'phone' => '',
        'address' => '',
    ];

    // --- Core POS Logic ---

    // Triggered by Enter on barcode input or scanner
    public function scanItem()
    {
        if (empty($this->barcode)) return;

        // 1. Try Exact SKU/Barcode Match
        $query = Product::where('sku', $this->barcode);
        
        if (Str::isUuid($this->barcode)) {
            $query->orWhere('id', $this->barcode);
        }
        
        $product = $query->first();

        if ($product) {
            $this->addToCart($product);
            $this->barcode = ''; 
            $this->dispatch('notify', __('Item added'));
            return;
        }

        // 2. Try Name Search (Partial)
        $results = Product::where('name', 'like', '%' . $this->barcode . '%')
            ->orWhere('sku', 'like', '%' . $this->barcode . '%')
            ->take(20)
            ->get();

        if ($results->count() === 1) {
            $this->addToCart($results->first());
            $this->barcode = '';
            $this->dispatch('notify', __('Item added'));
        } elseif ($results->count() > 1) {
            $this->searchResults = $results->toArray(); // Convert to array for Livewire
            $this->showProductSearchModal = true;
        } else {
            $this->dispatch('notify-error', __('Product not found!'));
        }
    }

    public function selectProduct($productId)
    {
        $product = Product::find($productId);
        if ($product) {
            $this->addToCart($product);
            $this->showProductSearchModal = false;
            $this->barcode = '';
            $this->searchResults = [];
            $this->dispatch('notify', __('Item added'));
            $this->dispatch('focus-input', 'barcode-input');
        }
    }

    public function addToCart($product, $qty = 1)
    {
        if ($product->stock <= 0) {
            $this->dispatch('notify-error', __('Product is out of stock!'));
            return;
        }

        if (isset($this->cart[$product->id])) {
            if ($this->cart[$product->id]['quantity'] < $product->stock) {
                $this->cart[$product->id]['quantity'] += $qty;
            } else {
                 $this->dispatch('notify-error', __('Insufficient Stock!'));
                 return;
            }
        } else {
            $this->cart[$product->id] = [
                'id' => $product->id,
                'code' => $product->sku,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => $qty,
                'discount' => 0,
                'subtotal' => 0 // Will be calculated below
            ];
        }
        $this->cart[$product->id]['subtotal'] = $this->calculateItemSubtotal($this->cart[$product->id]);
        $this->selectedItemId = $product->id; // Auto-select added item
    }

    public function updateQty($productId, $qty)
    {
        if (isset($this->cart[$productId])) {
            $qty = intval($qty);
            if ($qty <= 0) {
                $this->removeItem($productId);
                return;
            }
            
            // Stock Check
            $product = Product::find($productId);
            if ($product && $product->stock < $qty) {
                 $this->dispatch('notify-error', __('Insufficient Stock!'));
                 return; 
            }

            $this->cart[$productId]['quantity'] = $qty;
            $this->cart[$productId]['subtotal'] = $this->calculateItemSubtotal($this->cart[$productId]);
        }
    }

    public function calculateItemSubtotal($item)
    {
        return ($item['price'] * $item['quantity']) - $item['discount'];
    }

    public function removeItem($productId)
    {
        unset($this->cart[$productId]);
        if ($this->selectedItemId == $productId) {
            $this->selectedItemId = null;
        }
    }

    // --- Computeds ---

    public function getSubtotalProperty()
    {
        return collect($this->cart)->sum('subtotal');
    }

    public function getTaxAmountProperty()
    {
        return ($this->subtotal - $this->discountBill) * ($this->taxRate / 100);
    }

    public function getTotalProperty()
    {
        return max(0, $this->subtotal - $this->discountBill + $this->taxAmount);
    }

    // --- Actions ---

    public function newTransaction()
    {
        $this->reset(['cart', 'barcode', 'selectedCustomerId', 'selectedCustomerName', 'discountBill', 'receivedAmount', 'note', 'paymentMethod', 'lastSale', 'showReceiptModal', 'selectedItemId']);
        $this->paymentMethod = 'cash';
        $this->dispatch('notify', __('New transaction started'));
    }

    public function focusSearch()
    {
        $this->dispatch('focus-input', 'barcode-input');
    }

    public function processPayment()
    {
        if (empty($this->cart)) {
            $this->dispatch('notify-error', __('Cart is empty!'));
            return;
        }
        $this->showPaymentModal = true;
        $this->receivedAmount = number_format($this->total, 0, '.', '');
    }
    
    public function getChangeProperty()
    {
        $received = floatval($this->receivedAmount);
        return max(0, $received - $this->total);
    }

    public function completePayment()
    {
        if (empty($this->cart)) return;
        
        $sale = DB::transaction(function () {
            $user = auth()->user();
            $sale = Sale::create([
                'invoice_number' => 'INV-' . strtoupper(uniqid()),
                'user_id' => $user->created_by ? $user->created_by : $user->id,
                'input_id' => $user->id,
                'customer_id' => $this->selectedCustomerId,
                'subtotal' => $this->subtotal,
                'tax_id' => $this->selectedTaxId,
                'tax' => $this->taxAmount,
                'discount' => $this->discountBill,
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
                    'total_price' => $item['subtotal'],
                ]);

                $product = Product::find($item['id']);
                if ($product) {
                    $product->decrement('stock', $item['quantity']);
                }
            }

            // Deduct transaction fee from manager
            $managerId = $user->created_by ? $user->created_by : $user->id;
            $manager = User::find($managerId);

            if ($manager) {
                $manager->decrement('balance', 10);

                BalanceHistory::create([
                    'user_id' => $manager->id,
                    'amount' => -10,
                    'type' => 'debit',
                    'description' => 'Transaction Fee - ' . $sale->invoice_number,
                ]);
            }

            return $sale;
        });

        $this->lastSale = $sale;
        $this->showPaymentModal = false;
        $this->showReceiptModal = true;
        $this->newTransaction(); 
        
        $this->dispatch('notify', __('Payment Successful!'));
    }

    public function selectPaymentMethod($methodId)
    {
        $this->paymentMethod = $methodId;
    }

    public function holdOrder()
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', ['message' => __('Cart is empty!'), 'type' => 'warning']);
            return;
        }

        DB::transaction(function () {
            $user = auth()->user();
            $sale = Sale::create([
                'invoice_number' => 'HOLD-' . strtoupper(uniqid()),
                'user_id' => $user->created_by ? $user->created_by : $user->id,
                'input_id' => $user->id,
                'customer_id' => $this->selectedCustomerId,
                'subtotal' => $this->subtotal,
                'tax_id' => $this->selectedTaxId,
                'tax' => $this->taxAmount,
                'discount' => $this->discountBill,
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
                    'total_price' => $item['subtotal'],
                ]);
            }
        });

        $this->newTransaction();
        $this->dispatch('notify', ['message' => __('Order held successfully!'), 'type' => 'success']);
    }

    public function getHeldOrdersProperty()
    {
        return Sale::where('status', 'held')->with('customer')->latest()->get();
    }

    public function restoreOrder($saleId)
    {
        $sale = Sale::with('items.product', 'customer')->find($saleId);

        if (!$sale) {
            $this->dispatch('notify-error', __('Order not found!'));
            return;
        }

        $this->newTransaction(); // Clear current cart first

        foreach ($sale->items as $item) {
            // Re-construct cart item
            $subtotal = $item->total_price;
            $expectedSubtotal = $item->price * $item->quantity;
            $discount = max(0, $expectedSubtotal - $subtotal);
            
            $this->cart[$item->product_id] = [
                'id' => $item->product_id,
                'code' => $item->product ? $item->product->sku : '',
                'name' => $item->product_name,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'discount' => $discount, 
                'subtotal' => $subtotal,
                'stock' => $item->product ? $item->product->stock : 0
            ];
        }

        $this->selectedCustomerId = $sale->customer_id;
        $this->selectedCustomerName = $sale->customer ? $sale->customer->name : __('Walk-in Customer');
        $this->selectedTaxId = $sale->tax_id;
        $this->discountBill = $sale->discount; // Global discount
        $this->note = $sale->notes;

        // Delete held order
        $sale->forceDelete();

        $this->showHeldOrdersModal = false;
        $this->dispatch('notify', ['message' => __('Order restored!'), 'type' => 'success']);
    }

    public function pendingTransaction() 
    {
        $this->holdOrder();
    }
    
    public function recallTransaction() 
    {
        $this->showHeldOrdersModal = true;
    }

    public function editQty()
    {
        if (empty($this->cart)) return;
        
        // Get the last item's ID
        $lastItem = end($this->cart);
        if ($lastItem) {
            $this->dispatch('focus-input', 'qty-' . $lastItem['id']);
        }
    }

    public function updatedMemberSearch()
    {
        if (strlen($this->memberSearch) > 2) {
            $this->memberResults = Customer::where('name', 'like', '%' . $this->memberSearch . '%')
                ->orWhere('phone', 'like', '%' . $this->memberSearch . '%')
                ->orWhere('email', 'like', '%' . $this->memberSearch . '%')
                ->take(10)
                ->get()
                ->toArray();
        } else {
            $this->memberResults = [];
        }
    }

    public function selectMember($customerId = null)
    {
        if ($customerId) {
            // Selecting specific member from search
            $customer = Customer::find($customerId);
            if ($customer) {
                $this->selectedCustomerId = $customer->id;
                $this->selectedCustomerName = $customer->name;
                $this->showMemberModal = false;
                $this->memberSearch = '';
                $this->memberResults = [];
                $this->dispatch('notify', __('Member selected'));
                $this->dispatch('focus-input', 'barcode-input');
            }
        } else {
            // Opening the modal (F7 action)
            $this->showMemberModal = true;
            $this->dispatch('focus-input', 'member-search-input');
        }
    }

    public function saveNewCustomer()
    {
        $user = auth()->user();

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

        $this->selectedCustomerId = $customer->id;
        $this->selectedCustomerName = $customer->name;
        $this->showCreateCustomerModal = false;
        $this->showMemberModal = false;
        $this->newCustomer = ['name' => '', 'email' => '', 'phone' => '', 'address' => ''];
        $this->dispatch('notify', __('Customer created successfully!'));
    }

    public function discountItem()
    {
        if (empty($this->cart)) return;
        
        // Use selected item or last item
        if (!$this->selectedItemId) {
            $lastItem = end($this->cart);
            $this->selectedItemId = $lastItem['id'];
        }

        $this->discountType = 'item';
        $this->tempDiscount = $this->cart[$this->selectedItemId]['discount'];
        $this->showDiscountModal = true;
        $this->dispatch('focus-input', 'discount-input');
    }

    public function discountBill()
    {
        if (empty($this->cart)) return;
        
        $this->discountType = 'bill';
        $this->tempDiscount = $this->discountBill;
        $this->showDiscountModal = true;
        $this->dispatch('focus-input', 'discount-input');
    }

    public function applyDiscount()
    {
        $amount = floatval($this->tempDiscount);

        if ($this->discountType === 'item' && $this->selectedItemId && isset($this->cart[$this->selectedItemId])) {
            // Validate: Discount shouldn't exceed price * qty
            $maxDiscount = $this->cart[$this->selectedItemId]['price'] * $this->cart[$this->selectedItemId]['quantity'];
            if ($amount > $maxDiscount) {
                $this->dispatch('notify-error', __('Discount cannot exceed item total!'));
                return;
            }
            $this->cart[$this->selectedItemId]['discount'] = $amount;
            $this->cart[$this->selectedItemId]['subtotal'] = $this->calculateItemSubtotal($this->cart[$this->selectedItemId]);
        } elseif ($this->discountType === 'bill') {
            // Validate: Discount shouldn't exceed subtotal
            if ($amount > $this->subtotal) {
                $this->dispatch('notify-error', __('Discount cannot exceed subtotal!'));
                return;
            }
            $this->discountBill = $amount;
        }

        $this->showDiscountModal = false;
        $this->dispatch('notify', __('Discount applied'));
        $this->dispatch('focus-input', 'barcode-input');
    }
    
    public function voidItem()
    {
        if ($this->selectedItemId) {
            $this->removeItem($this->selectedItemId);
            $this->selectedItemId = null;
        } elseif (!empty($this->cart)) {
            // Remove last item if none selected
            $lastItem = end($this->cart);
            $this->removeItem($lastItem['id']);
        }
        $this->dispatch('focus-input', 'barcode-input');
    }

    public function selectRow($productId)
    {
        $this->selectedItemId = $productId;
    }
    public function openDrawer() 
    {
        $this->dispatch('notify', __('Opening Drawer...'));
        $this->dispatch('open-drawer');
    }

    public function reprintReceipt() 
    {
        if ($this->lastSale) {
            $this->showReceiptModal = true;
        } else {
            // Try to fetch the very last sale by this user
            $lastSale = Sale::where('user_id', auth()->id())->latest()->first();
            if ($lastSale) {
                $this->lastSale = $lastSale;
                $this->showReceiptModal = true;
            } else {
                $this->dispatch('notify-error', __('No recent sale found to reprint.'));
            }
        }
    }

};
?>

<div x-data="deviceManager" class="flex flex-col h-full bg-gray-100 font-sans text-gray-800">
    <style>
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Zebra Striping for Table */
        .zebra-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .zebra-table tbody tr:hover {
            background-color: #e0e7ff;
        }
    </style>

    <!-- Terminal Content (Beepos Style) -->
    <div class="flex-1 flex flex-col overflow-hidden bg-gray-100">

        <!-- Beepos Header Bar -->
        <div class="bg-secondary text-white h-16 flex items-center justify-between px-4 shadow-md shrink-0">
            <div class="flex items-center">
                <!-- Back Button -->
                <a href="{{ route('dashboard') }}" class="mr-6 text-gray-400 hover:text-white transition-colors flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> {{ __('Back') }}
                </a>

                <!-- Logo -->
                <div class="flex items-center mr-8">
                    <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center mr-2">
                        <i class="fas fa-cube text-white text-lg"></i>
                    </div>
                    <span class="text-2xl font-bold tracking-tighter">BEEPOS</span>
                </div>
            </div>

            <!-- Navbar Extras (Fullscreen, Devices) -->
            <div class="flex items-center space-x-6">
                <button onclick="toggleFullscreen()" class="text-gray-300 hover:text-white transition-colors" title="Toggle Fullscreen">
                    <i class="fas fa-expand text-xl"></i>
                </button>
                <div class="h-8 w-px bg-gray-600 mx-2"></div>
                <button @click="connectPrinter" id="btn-printer" class="relative text-gray-300 hover:text-white transition-colors group flex items-center" title="Connect Printer">
                    <i class="fas fa-print text-xl mr-2" :class="{'text-green-400': printerStatus.startsWith('Connected')}"></i>
                    <span class="text-sm font-medium hidden lg:inline">{{ __('Printer') }}</span>
                    <span class="absolute -top-1 -right-1 h-2.5 w-2.5 rounded-full border-2 border-slate-800" :class="printerStatus.startsWith('Connected') ? 'bg-green-500' : 'bg-red-500'"></span>
                </button>
                <button @click="connectScanner" id="btn-scanner" class="relative text-gray-300 hover:text-white transition-colors group flex items-center" title="Connect Scanner">
                    <i class="fas fa-barcode text-xl mr-2" :class="{'text-green-400': scannerStatus.startsWith('Connected')}"></i>
                    <span class="text-sm font-medium hidden lg:inline">{{ __('Scanner') }}</span>
                    <span class="absolute -top-1 -right-1 h-2.5 w-2.5 rounded-full border-2 border-slate-800" :class="scannerStatus.startsWith('Connected') ? 'bg-green-500' : 'bg-red-500'"></span>
                </button>
            </div>

            <!-- Info Section -->
            <div class="flex space-x-0 h-full">
                <div class="flex flex-col justify-center px-6 border-l border-slate-600 h-full">
                    <span class="text-gray-400 text-xs uppercase">{{ __('Customer:') }}</span>
                    <span class="font-bold text-lg">{{ __('CASH') }}</span>
                </div>
                <div class="flex flex-col justify-center px-6 border-l border-slate-600 h-full">
                    <span class="text-gray-400 text-xs uppercase">{{ __('Date:') }}</span>
                    <span class="font-bold text-lg" id="current-date">04 Sep 2017</span>
                </div>
                <div class="flex flex-col justify-center px-6 border-l border-slate-600 h-full text-right">
                    <span class="text-gray-400 text-xs uppercase">{{ __('Sale No:') }}</span>
                    <span class="font-bold text-lg">AUTO</span>
                </div>
                <div class="flex flex-col justify-center px-6 border-l border-slate-600 h-full text-right bg-slate-700">
                    <span class="text-gray-300 text-xs">{{ __('Cashier 1') }}</span>
                    <span class="font-bold text-lg">{{ __('Admin') }}</span>
                </div>
            </div>
        </div>

        <!-- Main Work Area -->
        <div class="flex-1 flex overflow-hidden">

            <!-- Left: Table & Input -->
            <div class="flex-1 flex flex-col bg-white border-r border-gray-300">
                <!-- Input Area -->
                <div class="p-4 bg-gray-50 border-b border-gray-200">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fas fa-barcode text-gray-500"></i>
                        </span>
                        <input type="text" wire:model="barcode" wire:keydown.enter="scanItem" id="barcode-input" class="w-full py-3 pl-10 pr-4 bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-lg" placeholder="{{ __('Scan Item or Enter Code (F2)...') }}" autofocus>
                    </div>
                </div>

                <!-- Table -->
                <div class="flex-1 overflow-auto">
                    <table class="w-full text-left border-collapse zebra-table">
                        <thead class="bg-gray-100 sticky top-0 z-10">
                            <tr>
                                <th class="p-3 text-xs font-bold text-gray-500 uppercase border-b border-gray-300 w-12">#</th>
                                <th class="p-3 text-xs font-bold text-gray-500 uppercase border-b border-gray-300">{{ __('Item Code') }}</th>
                                <th class="p-3 text-xs font-bold text-gray-500 uppercase border-b border-gray-300">{{ __('Item Name') }}</th>
                                <th class="p-3 text-xs font-bold text-gray-500 uppercase border-b border-gray-300 text-right">{{ __('Qty') }}</th>
                                <th class="p-3 text-xs font-bold text-gray-500 uppercase border-b border-gray-300 text-right">{{ __('Price') }}</th>
                                <th class="p-3 text-xs font-bold text-gray-500 uppercase border-b border-gray-300 text-right">{{ __('Disc.') }}</th>
                                <th class="p-3 text-xs font-bold text-gray-500 uppercase border-b border-gray-300 text-right">{{ __('Subtotal') }}</th>
                                <th class="p-3 text-xs font-bold text-gray-500 uppercase border-b border-gray-300 text-center">{{ __('Act') }}</th>
                            </tr>
                        </thead>
                        <tbody id="cart-table-body" class="text-sm">
                            @forelse($cart as $item)
                            <tr class="border-b border-gray-200 cursor-pointer {{ $selectedItemId === $item['id'] ? 'bg-indigo-100' : '' }}" 
                                wire:key="cart-item-{{ $item['id'] }}"
                                wire:click="selectRow('{{ $item['id'] }}')">
                                <td class="p-3 text-gray-500">{{ $loop->iteration }}</td>
                                <td class="p-3 font-mono text-gray-600">{{ $item['code'] }}</td>
                                <td class="p-3 font-medium text-gray-900">{{ $item['name'] }}</td>
                                <td class="p-3 text-right">
                                    <input type="number" 
                                           id="qty-{{ $item['id'] }}"
                                           value="{{ $item['quantity'] }}" 
                                           wire:change="updateQty('{{ $item['id'] }}', $event.target.value)"
                                           class="w-16 p-1 text-right border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500"
                                           onclick="event.stopPropagation()">
                                </td>
                                <td class="p-3 text-right font-mono">Rp. {{ number_format($item['price'], 0, ',', '.') }}</td>
                                <td class="p-3 text-right text-red-500">Rp. {{ number_format($item['discount'], 0, ',', '.') }}</td>
                                <td class="p-3 text-right font-bold font-mono">Rp. {{ number_format($item['subtotal'], 0, ',', '.') }}</td>
                                <td class="p-3 text-center">
                                    <button wire:click.stop="removeItem('{{ $item['id'] }}')" class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="p-8 text-center text-gray-400">
                                    <i class="fas fa-shopping-basket text-4xl mb-3"></i>
                                    <p>{{ __('Cart is empty. Scan an item to start.') }}</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right: Summary & Keypad -->
            <div class="w-80 bg-gray-100 flex flex-col border-l border-gray-300">

                <!-- Total Display -->
                <div class="bg-secondary text-white p-6 text-right">
                    <div class="text-sm text-gray-400 uppercase mb-1">{{ __('Total Amount') }}</div>
                    <div class="text-4xl font-bold font-mono tracking-wider">
                        Rp. {{ number_format($this->total, 0, ',', '.') }}
                    </div>
                </div>

                <!-- Summary Details -->
                <div class="p-4 space-y-2 bg-white border-b border-gray-200">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">{{ __('Subtotal') }}</span>
                        <span class="font-bold">{{ number_format($this->subtotal, 0, '.', ',') }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">{{ __('Tax') }}</span>
                        <span class="font-bold">{{ number_format($this->taxAmount, 0, '.', ',') }}</span>
                    </div>
                    <div class="flex justify-between text-sm text-red-500">
                        <span class="text-red-500">{{ __('Discount') }}</span>
                        <span class="font-bold">-{{ number_format($this->discountBill, 0, '.', ',') }}</span>
                    </div>
                </div>

                <!-- Function Keys / Actions -->
                <div class="p-4 flex-1 overflow-y-auto grid grid-cols-2 gap-2 content-start">
                    @foreach($functionKeys as $key)
                    <button wire:click="{{ $key['action'] }}" class="p-3 bg-white border border-gray-300 rounded shadow-sm hover:bg-gray-50 text-left transition-colors">
                        <div class="text-xs text-indigo-600 font-bold mb-1">{{ $key['key'] }}</div>
                        <div class="text-sm font-medium text-gray-700">{{ $key['label'] }}</div>
                    </button>
                    @endforeach
                </div>

                <!-- Pay Button -->
                <div class="p-4 bg-gray-200 border-t border-gray-300">
                    <button class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-md transition-colors flex items-center justify-between px-6">
                        <span class="font-bold text-lg">{{ __('PAYMENT') }}</span>
                        <span class="bg-white text-indigo-600 px-2 py-1 rounded font-bold text-sm">F12</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Bottom Status Bar -->
        <div class="bg-gray-800 text-gray-400 text-xs py-1 px-4 flex justify-between">
            <div>
                <span>{{ __('Connected: Online') }}</span> |
                <span>{{ __('Printer: Ready') }}</span>
            </div>
            <div>
                <span>{{ __('Version 2.0.1') }}</span>
            </div>
        </div>

    </div>

    <!-- Payment Modal -->
    <!-- Payment Modal -->
    @if($showPaymentModal)
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center" @click.self="$set('showPaymentModal', false)">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <h2 class="text-2xl font-bold mb-4">{{ __('Payment') }}</h2>
            <div class="space-y-4">
                <div class="flex justify-between text-lg">
                    <span>{{ __('Total Due:') }}</span>
                    <span class="font-bold font-mono">{{ number_format($this->total, 0, '.', ',') }}</span>
                </div>

                <!-- Payment Methods -->
                <div class="grid grid-cols-2 gap-2">
                    @foreach($paymentMethods as $method)
                    <button wire:click="selectPaymentMethod('{{ $method['id'] }}')"
                        class="p-3 border rounded-lg flex flex-col items-center justify-center transition-all {{ $paymentMethod === $method['id'] ? 'bg-indigo-50 border-indigo-500 text-indigo-700 ring-1 ring-indigo-500' : 'border-gray-200 hover:bg-gray-50' }}">
                        <i class="fas {{ $method['icon'] }} text-xl mb-1"></i>
                        <span class="text-sm font-medium">{{ $method['name'] }}</span>
                    </button>
                    @endforeach
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Cash Received') }}</label>
                    <input type="number" wire:model.live="receivedAmount" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-lg p-2" autofocus>
                </div>
                <div class="flex justify-between text-lg text-green-600">
                    <span>{{ __('Change:') }}</span>
                    <span class="font-bold font-mono">Rp. {{ number_format($this->change, 0, ',', '.') }}</span>
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button wire:click="$set('showPaymentModal', false)" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    {{ __('Cancel') }} (Esc)
                </button>
                <button wire:click="completePayment" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">
                    {{ __('Complete Payment') }} (Enter)
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Receipt Modal -->
    @if($showReceiptModal && $lastSale)
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center" @click.self="$set('showReceiptModal', false)">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-sm p-6 flex flex-col max-h-[90vh]">
            <div class="text-center mb-4">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-2">
                    <i class="fas fa-check text-green-600 text-xl"></i>
                </div>
                <h2 class="text-xl font-bold">{{ __('Payment Successful!') }}</h2>
                <p class="text-gray-500 text-sm">{{ $lastSale->invoice_number }}</p>
            </div>
            
            <div class="border-t border-b border-gray-200 py-4 my-2 overflow-y-auto flex-1">
                <div class="flex justify-between mb-2">
                    <span class="text-gray-600">{{ __('Date') }}</span>
                    <span class="font-medium">{{ $lastSale->created_at->format('d M Y H:i') }}</span>
                </div>
                <div class="flex justify-between mb-2">
                    <span class="text-gray-600">{{ __('Customer') }}</span>
                    <span class="font-medium">{{ $lastSale->customer ? $lastSale->customer->name : 'Walk-in' }}</span>
                </div>
                <div class="flex justify-between mb-4">
                    <span class="text-gray-600">{{ __('Payment') }}</span>
                    <span class="font-medium uppercase">{{ $lastSale->payment_method }}</span>
                </div>
                
                <div class="space-y-2">
                    <div class="flex justify-between font-bold text-lg">
                        <span>{{ __('Total') }}</span>
                        <span>{{ number_format($lastSale->total_amount, 0, '.', ',') }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>{{ __('Paid') }}</span>
                        <span>{{ number_format($lastSale->cash_received, 0, '.', ',') }}</span>
                    </div>
                    <div class="flex justify-between text-green-600 font-medium">
                        <span>{{ __('Change') }}</span>
                        <span>{{ number_format($lastSale->change_amount, 0, '.', ',') }}</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col space-y-2 mt-4">
                <button onclick="window.print()" class="w-full py-2 bg-indigo-600 text-white rounded-lg font-bold hover:bg-indigo-700">
                    <i class="fas fa-print mr-2"></i> {{ __('Print Receipt') }}
                </button>
                <button wire:click="$set('showReceiptModal', false)" class="w-full py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    {{ __('Close') }}
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Held Orders Modal -->
    @if($showHeldOrdersModal)
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center" @click.self="$set('showHeldOrdersModal', false)">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl p-6 max-h-[80vh] flex flex-col">
            <h2 class="text-xl font-bold mb-4">{{ __('Recall Transaction') }}</h2>
            
            <div class="overflow-y-auto flex-1">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-3">{{ __('Date') }}</th>
                            <th class="p-3">{{ __('Customer') }}</th>
                            <th class="p-3">{{ __('Note') }}</th>
                            <th class="p-3 text-right">{{ __('Amount') }}</th>
                            <th class="p-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->heldOrders as $order)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-3">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td class="p-3 font-medium">{{ $order->customer ? $order->customer->name : 'Walk-in' }}</td>
                            <td class="p-3 text-gray-500 italic">{{ Str::limit($order->notes, 30) }}</td>
                            <td class="p-3 text-right font-bold">{{ number_format($order->total_amount, 0, '.', ',') }}</td>
                            <td class="p-3 text-right">
                                <button wire:click="restoreOrder({{ $order->id }})" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                                    {{ __('Restore') }}
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-gray-500">
                                {{ __('No held transactions found.') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4 flex justify-end">
                <button wire:click="$set('showHeldOrdersModal', false)" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    {{ __('Close') }} (Esc)
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Product Search Modal -->
    @if($showProductSearchModal)
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center" @click.self="$set('showProductSearchModal', false)">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl p-6 max-h-[80vh] flex flex-col">
            <h2 class="text-xl font-bold mb-4">{{ __('Select Product') }}</h2>
            <div class="overflow-y-auto flex-1">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-2">{{ __('Code') }}</th>
                            <th class="p-2">{{ __('Name') }}</th>
                            <th class="p-2 text-right">{{ __('Price') }}</th>
                            <th class="p-2 text-right">{{ __('Stock') }}</th>
                            <th class="p-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($searchResults as $result)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-2 font-mono">{{ $result['sku'] }}</td>
                            <td class="p-2">{{ $result['name'] }}</td>
                            <td class="p-2 text-right">{{ number_format($result['price'], 0, '.', ',') }}</td>
                            <td class="p-2 text-right">{{ $result['stock'] }}</td>
                            <td class="p-2 text-right">
                                <button wire:click="selectProduct({{ $result['id'] }})" class="px-3 py-1 bg-indigo-600 text-white rounded text-sm hover:bg-indigo-700">
                                    {{ __('Select') }}
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4 flex justify-end">
                <button wire:click="$set('showProductSearchModal', false)" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    {{ __('Close') }} (Esc)
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Member Search Modal -->
    @if($showMemberModal)
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center" @click.self="$set('showMemberModal', false)">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl p-6 max-h-[80vh] flex flex-col">
            <h2 class="text-xl font-bold mb-4">{{ __('Select Member') }}</h2>
            <div class="mb-4 flex gap-2">
                <input type="text" id="member-search-input" wire:model.live.debounce.300ms="memberSearch" placeholder="Search Name, Phone, Email..." class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500">
                <button wire:click="$set('showCreateCustomerModal', true)" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 whitespace-nowrap">
                    <i class="fas fa-plus"></i> {{ __('New') }}
                </button>
            </div>
            <div class="overflow-y-auto flex-1">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-2">{{ __('Name') }}</th>
                            <th class="p-2">{{ __('Phone') }}</th>
                            <th class="p-2">{{ __('Email') }}</th>
                            <th class="p-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($memberResults as $member)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-2 font-medium">{{ $member['name'] }}</td>
                            <td class="p-2">{{ $member['phone'] }}</td>
                            <td class="p-2 text-sm text-gray-500">{{ $member['email'] }}</td>
                            <td class="p-2 text-right">
                                <button wire:click="selectMember({{ $member['id'] }})" class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700">
                                    {{ __('Select') }}
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="p-4 text-center text-gray-500">{{ __('No members found') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4 flex justify-end">
                <button wire:click="$set('showMemberModal', false)" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    {{ __('Close') }} (Esc)
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Discount Modal -->
    @if($showDiscountModal)
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center" @click.self="$set('showDiscountModal', false)">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-sm p-6">
            <h2 class="text-xl font-bold mb-4">{{ $discountType === 'item' ? __('Item Discount') : __('Bill Discount') }}</h2>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Discount Amount (Rp)') }}</label>
                <input type="number" id="discount-input" wire:model="tempDiscount" wire:keydown.enter="applyDiscount" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 text-lg">
            </div>
            <div class="flex justify-end space-x-3">
                <button wire:click="$set('showDiscountModal', false)" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    {{ __('Cancel') }}
                </button>
                <button wire:click="applyDiscount" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">
                    {{ __('Apply') }} (Enter)
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Create Customer Modal -->
    @if($showCreateCustomerModal)
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center" @click.self="$set('showCreateCustomerModal', false)">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <h2 class="text-xl font-bold mb-4">{{ __('Create New Customer') }}</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Name') }}</label>
                    <input type="text" wire:model="newCustomer.name" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500">
                    @error('newCustomer.name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Phone') }}</label>
                    <input type="text" wire:model="newCustomer.phone" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500">
                    @error('newCustomer.phone') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Email') }}</label>
                    <input type="email" wire:model="newCustomer.email" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500">
                    @error('newCustomer.email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Address') }}</label>
                    <textarea wire:model="newCustomer.address" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button wire:click="$set('showCreateCustomerModal', false)" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    {{ __('Cancel') }}
                </button>
                <button wire:click="saveNewCustomer" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">
                    {{ __('Save Customer') }}
                </button>
            </div>
        </div>
    </div>
    @endif

    <script>
        // Set current date
        const dateOptions = { day: '2-digit', month: 'short', year: 'numeric' };
        document.getElementById('current-date').textContent = new Date().toLocaleDateString('en-GB', dateOptions);

        // Keyboard Shortcuts
        document.addEventListener('keydown', function(event) {
            // F1: New Transaction
            if (event.key === 'F1') {
                event.preventDefault();
                @this.call('newTransaction');
            }
            // F2: Focus Barcode (Search)
            else if (event.key === 'F2') {
                event.preventDefault();
                document.getElementById('barcode-input').focus();
            }
            // F3: Edit Qty
            else if (event.key === 'F3') {
                event.preventDefault();
                @this.call('editQty');
            }
            // F4: Discount Item
            else if (event.key === 'F4') {
                event.preventDefault();
                @this.call('discountItem');
            }
            // F5: Discount Bill
            else if (event.key === 'F5') {
                event.preventDefault();
                @this.call('discountBill');
            }
            // F6: Void Item
            else if (event.key === 'F6') {
                event.preventDefault();
                @this.call('voidItem');
            }
            // F7: Select Member
            else if (event.key === 'F7') {
                event.preventDefault();
                @this.call('selectMember');
            }
            // F8: Pending (Hold)
            else if (event.key === 'F8') {
                event.preventDefault();
                @this.call('pendingTransaction');
            }
            // F9: Recall
            else if (event.key === 'F9') {
                event.preventDefault();
                @this.call('recallTransaction');
            }
            // F10: Drawer
            else if (event.key === 'F10') {
                event.preventDefault();
                @this.call('openDrawer');
            }
            // F11: Reprint
            else if (event.key === 'F11') {
                event.preventDefault();
                @this.call('reprintReceipt');
            }
            // F12: Payment
            else if (event.key === 'F12') {
                event.preventDefault();
                @this.call('processPayment');
            }
        });

        // Handle Focus Event
        document.addEventListener('livewire:initialized', () => {
            @this.on('focus-input', (inputId) => {
                setTimeout(() => {
                    document.getElementById(inputId).focus();
                }, 50);
            });

            const showToast = (message, type = 'success') => {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: type,
                        title: message,
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                    });
                } else {
                    console.log(type + ': ' + message);
                }
            };

            @this.on('notify', (data) => {
                const payload = data[0];
                if (typeof payload === 'object' && payload !== null) {
                    showToast(payload.message, payload.type || 'success');
                } else {
                    showToast(payload, 'success');
                }
            });

            @this.on('notify-error', (data) => {
                const payload = data[0];
                const message = (typeof payload === 'object') ? payload.message : payload;
                showToast(message, 'error');
            });
        });
        
        // Listen for barcode scans from pos-devices.js
        window.addEventListener('barcode-scanned', (event) => {
             @this.set('barcode', event.detail);
             @this.call('scanItem');
         });
     </script>
 </div>
