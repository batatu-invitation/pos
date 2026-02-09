<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\Tax;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

new
#[Layout('components.layouts.pos')]
#[Title('POS Terminal - Modern POS')]
class extends Component
{
    public $barcode = '';
    public $cart = []; // [productId => [id, code, name, price, qty, discount, subtotal, stock]]
    public $selectedCustomerId = null;
    public $selectedCustomerName = 'Walk-in Customer';
    
    // Transaction Data
    public $taxRate = 0;
    public $discountBill = 0;
    public $note = '';
    
    // Payment
    public $receivedAmount = '';
    public $changeAmount = 0;
    public $showPaymentModal = false;

    public $functionKeys = [];

    public function mount()
    {
        $this->initializeFunctionKeys();
        $this->loadDefaultTax();
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
        $this->taxRate = $tax ? $tax->rate : 0;
    }

    // Modals & Selection
    public $showProductSearchModal = false;
    public $showDiscountModal = false;
    public $showMemberModal = false;
    public $discountType = 'item'; // 'item' or 'bill'
    public $selectedItemId = null; // For item actions
    public $tempDiscount = 0; // For modal input
    
    // Member Search
    public $memberSearch = '';
    public $memberResults = [];

    // --- Core POS Logic ---

    // Triggered by Enter on barcode input or scanner
    public function scanItem()
    {
        if (empty($this->barcode)) return;

        // 1. Try Exact SKU/Barcode Match
        $product = Product::where('sku', $this->barcode)->orWhere('id', $this->barcode)->first();

        if ($product) {
            $this->addToCart($product);
            $this->barcode = ''; 
            $this->dispatch('notify', ['message' => __('Item added'), 'type' => 'success']);
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
            $this->dispatch('notify', ['message' => __('Item added'), 'type' => 'success']);
        } elseif ($results->count() > 1) {
            $this->searchResults = $results->toArray(); // Convert to array for Livewire
            $this->showProductSearchModal = true;
        } else {
            $this->dispatch('notify', ['message' => __('Product not found!'), 'type' => 'error']);
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
            $this->dispatch('notify', ['message' => __('Item added'), 'type' => 'success']);
            $this->dispatch('focus-input', 'barcode-input');
        }
    }

    public function addToCart($product, $qty = 1)
    {
        if ($product->stock < $qty) {
            $this->dispatch('notify', ['message' => __('Not enough stock!'), 'type' => 'error']);
            return;
        }

        if (isset($this->cart[$product->id])) {
            $this->cart[$product->id]['qty'] += $qty;
            $this->cart[$product->id]['subtotal'] = $this->calculateItemSubtotal($this->cart[$product->id]);
        } else {
            $this->cart[$product->id] = [
                'id' => $product->id,
                'code' => $product->sku,
                'name' => $product->name,
                'price' => $product->price,
                'qty' => $qty,
                'discount' => 0,
                'subtotal' => $product->price * $qty,
                'stock' => $product->stock
            ];
        }
    }

    public function calculateItemSubtotal($item)
    {
        return ($item['price'] * $item['qty']) - $item['discount'];
    }

    public function removeItem($productId)
    {
        unset($this->cart[$productId]);
    }

    public function updateQty($productId, $qty)
    {
        if (isset($this->cart[$productId])) {
            if ($qty <= 0) {
                $this->removeItem($productId);
            } elseif ($qty <= $this->cart[$productId]['stock']) {
                $this->cart[$productId]['qty'] = $qty;
                $this->cart[$productId]['subtotal'] = $this->calculateItemSubtotal($this->cart[$productId]);
            } else {
                $this->dispatch('notify', ['message' => __('Not enough stock!'), 'type' => 'error']);
            }
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
        $this->reset(['cart', 'barcode', 'selectedCustomerId', 'selectedCustomerName', 'discountBill', 'receivedAmount', 'changeAmount']);
        $this->dispatch('notify', ['message' => __('New transaction started'), 'type' => 'info']);
    }

    public function focusSearch()
    {
        $this->dispatch('focus-input', 'barcode-input');
    }

    public function processPayment()
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', ['message' => __('Cart is empty!'), 'type' => 'warning']);
            return;
        }
        $this->showPaymentModal = true;
        $this->receivedAmount = ''; // Reset or set to total
    }
    
    public function updatedReceivedAmount()
    {
        $received = floatval($this->receivedAmount);
        $this->changeAmount = max(0, $received - $this->total);
    }

    public function completePayment()
    {
        if (empty($this->cart)) return;
        
        $user = auth()->user();

        DB::transaction(function () use ($user) {
            $sale = Sale::create([
                'invoice_number' => 'INV-' . strtoupper(uniqid()),
                'user_id' => $user->created_by ?? $user->id,
                'input_id' => $user->id,
                'customer_id' => $this->selectedCustomerId, // Will be null for walk-in if not set
                'subtotal' => $this->subtotal,
                'tax_id' => null, // Needs logic
                'tax' => $this->taxAmount,
                'discount' => $this->discountBill,
                'total_amount' => $this->total,
                'cash_received' => floatval($this->receivedAmount),
                'change_amount' => $this->changeAmount,
                'payment_method' => 'cash', // Default to cash for terminal for now
                'status' => 'completed',
                'notes' => $this->note,
            ]);

            foreach ($this->cart as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['id'],
                    'product_name' => $item['name'],
                    'quantity' => $item['qty'],
                    'price' => $item['price'],
                    'total_price' => $item['subtotal'],
                ]);

                $product = Product::find($item['id']);
                if ($product) {
                    $product->decrement('stock', $item['qty']);
                }
            }
        });

        $this->showPaymentModal = false;
        $this->newTransaction();
        $this->dispatch('notify', ['message' => __('Payment Successful!'), 'type' => 'success']);
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

    public function discountItem() {}
    public function discountBill() {}
    public function voidItem() {}
    public function selectMember() {}
    public function pendingTransaction() {}
    public function recallTransaction() {}
    public function openDrawer() {}
    public function reprintReceipt() {}

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
                            <tr class="border-b border-gray-200" wire:key="cart-item-{{ $item['id'] }}">
                                <td class="p-3 text-gray-500">{{ $loop->iteration }}</td>
                                <td class="p-3 font-mono text-gray-600">{{ $item['code'] }}</td>
                                <td class="p-3 font-medium text-gray-900">{{ $item['name'] }}</td>
                                <td class="p-3 text-right">
                                    <input type="number" 
                                           id="qty-{{ $item['id'] }}"
                                           value="{{ $item['qty'] }}" 
                                           wire:change="updateQty('{{ $item['id'] }}', $event.target.value)"
                                           class="w-16 p-1 text-right border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500">
                                </td>
                                <td class="p-3 text-right font-mono">Rp. {{ number_format($item['price'], 0, '.', ',') }}</td>
                                <td class="p-3 text-right text-red-500">Rp. {{ number_format($item['discount'], 0, '.', ',') }}</td>
                                <td class="p-3 text-right font-bold font-mono">Rp. {{ number_format($item['subtotal'], 0, '.', ',') }}</td>
                                <td class="p-3 text-center">
                                    <button wire:click="removeItem('{{ $item['id'] }}')" class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
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
                        {{ number_format($this->total, 0, '.', ',') }}
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
    @if($showPaymentModal)
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <h2 class="text-2xl font-bold mb-4">{{ __('Payment') }}</h2>
            
            <div class="space-y-4">
                <div class="flex justify-between text-lg">
                    <span>{{ __('Total Due:') }}</span>
                    <span class="font-bold font-mono">{{ number_format($this->total, 0, '.', ',') }}</span>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Cash Received') }}</label>
                    <input type="number" wire:model.live="receivedAmount" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-lg p-2" autofocus>
                </div>

                <div class="flex justify-between text-lg text-green-600">
                    <span>{{ __('Change:') }}</span>
                    <span class="font-bold font-mono">{{ number_format($this->changeAmount, 0, '.', ',') }}</span>
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

    <script>
        // Set current date
        const dateOptions = { day: '2-digit', month: 'short', year: 'numeric' };
        document.getElementById('current-date').textContent = new Date().toLocaleDateString('en-GB', dateOptions);

        // Keyboard Shortcuts
        document.addEventListener('keydown', function(event) {
            if (event.key === 'F2') {
                event.preventDefault();
                document.getElementById('barcode-input').focus();
            } else if (event.key === 'F12') {
                event.preventDefault();
                // Trigger Livewire action
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

            @this.on('notify', (data) => {
                // Use SweetAlert or Toastr if available, otherwise simple alert or console
                // Assuming SweetAlert2 is available globally as Swal (standard in this stack)
                if (typeof Swal !== 'undefined') {
                    const toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                    });
                    toast.fire({
                        icon: data[0].type || 'info',
                        title: data[0].message
                    });
                } else {
                    console.log(data[0].message);
                }
            });
        });
        
        // Listen for barcode scans from pos-devices.js
        window.addEventListener('barcode-scanned', (event) => {
             @this.set('barcode', event.detail);
             @this.call('scanItem');
         });
     </script>
 </div>
