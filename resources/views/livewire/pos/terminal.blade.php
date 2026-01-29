<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.pos')]
#[Title('POS Terminal - Modern POS')]
class extends Component
{
    public $barcode = '';

    public $cart = [
        ['code' => '8993001', 'name' => 'Indomie Goreng', 'qty' => 5, 'price' => 2500, 'discount' => 0, 'subtotal' => 12500],
        ['code' => '8993002', 'name' => 'Coca Cola 330ml', 'qty' => 2, 'price' => 5000, 'discount' => 0, 'subtotal' => 10000],
        ['code' => '8993003', 'name' => 'Chitato BBQ', 'qty' => 1, 'price' => 9500, 'discount' => 0, 'subtotal' => 9500],
    ];

    public $functionKeys = [
        ['key' => 'F1', 'label' => 'New Trans', 'action' => 'newTransaction', 'color' => 'indigo'],
        ['key' => 'F2', 'label' => 'Search Item', 'action' => 'searchItem', 'color' => 'indigo'],
        ['key' => 'F3', 'label' => 'Qty', 'action' => 'editQty', 'color' => 'indigo'],
        ['key' => 'F4', 'label' => 'Disc Item', 'action' => 'discountItem', 'color' => 'indigo'],
        ['key' => 'F5', 'label' => 'Disc Bill', 'action' => 'discountBill', 'color' => 'indigo'],
        ['key' => 'F6', 'label' => 'Void Item', 'action' => 'voidItem', 'color' => 'indigo'],
        ['key' => 'F7', 'label' => 'Member', 'action' => 'selectMember', 'color' => 'indigo'],
        ['key' => 'F8', 'label' => 'Pending', 'action' => 'pendingTransaction', 'color' => 'indigo'],
        ['key' => 'F9', 'label' => 'Recall', 'action' => 'recallTransaction', 'color' => 'indigo'],
        ['key' => 'F10', 'label' => 'Drawer', 'action' => 'openDrawer', 'color' => 'indigo'],
        ['key' => 'F11', 'label' => 'Reprint', 'action' => 'reprintReceipt', 'color' => 'indigo'],
        ['key' => 'F12', 'label' => 'Payment', 'action' => 'processPayment', 'color' => 'indigo'], // Handled by big button usually, but keeping in list for completeness if needed
    ];

    public function mount()
    {
        $this->functionKeys = [
            ['key' => 'F1', 'label' => __('New Trans'), 'action' => 'newTransaction', 'color' => 'indigo'],
            ['key' => 'F2', 'label' => __('Search Item'), 'action' => 'searchItem', 'color' => 'indigo'],
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

    public function newTransaction() {
        // Logic for new transaction
    }

    public function searchItem() {
        // Logic for search item
    }

    // ... other methods placeholders
};
?>

<div class="flex flex-col h-full bg-gray-100 font-sans text-gray-800">
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
                <button onclick="connectDevice('printer')" id="btn-printer" class="relative text-gray-300 hover:text-white transition-colors group flex items-center" title="Connect Printer">
                    <i class="fas fa-print text-xl mr-2"></i>
                    <span class="text-sm font-medium hidden lg:inline">{{ __('Printer') }}</span>
                    <span id="status-printer" class="absolute -top-1 -right-1 h-2.5 w-2.5 bg-red-500 rounded-full border-2 border-slate-800"></span>
                </button>
                <button onclick="connectDevice('scanner')" id="btn-scanner" class="relative text-gray-300 hover:text-white transition-colors group flex items-center" title="Connect Scanner">
                    <i class="fas fa-barcode text-xl mr-2"></i>
                    <span class="text-sm font-medium hidden lg:inline">{{ __('Scanner') }}</span>
                    <span id="status-scanner" class="absolute -top-1 -right-1 h-2.5 w-2.5 bg-red-500 rounded-full border-2 border-slate-800"></span>
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
                        <input type="text" wire:model="barcode" id="barcode-input" class="w-full py-3 pl-10 pr-4 bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-lg" placeholder="{{ __('Scan Item or Enter Code (F2)...') }}" autofocus>
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
                            @foreach($cart as $index => $item)
                            <tr class="border-b border-gray-200">
                                <td class="p-3 text-gray-500">{{ $index + 1 }}</td>
                                <td class="p-3 font-mono text-gray-600">{{ $item['code'] }}</td>
                                <td class="p-3 font-medium text-gray-900">{{ $item['name'] }}</td>
                                <td class="p-3 text-right">
                                    <input type="number" value="{{ $item['qty'] }}" class="w-16 p-1 text-right border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500">
                                </td>
                                <td class="p-3 text-right font-mono">Rp. {{ number_format($item['price'], 0, '.', ',') }}</td>
                                <td class="p-3 text-right text-red-500">Rp. {{ number_format($item['discount'], 0, '.', ',') }}</td>
                                <td class="p-3 text-right font-bold font-mono">Rp. {{ number_format($item['subtotal'], 0, '.', ',') }}</td>
                                <td class="p-3 text-center">
                                    <button class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            @endforeach
                            <!-- Empty State if needed -->
                            @if(empty($cart))
                            <tr>
                                <td colspan="8" class="p-8 text-center text-gray-400">
                                    <i class="fas fa-shopping-basket text-4xl mb-3"></i>
                                    <p>{{ __('Cart is empty. Scan an item to start.') }}</p>
                                </td>
                            </tr>
                            @endif
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
                        {{ number_format(collect($cart)->sum('subtotal'), 0, '.', ',') }}
                    </div>
                </div>

                <!-- Summary Details -->
                <div class="p-4 space-y-2 bg-white border-b border-gray-200">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">{{ __('Subtotal') }}</span>
                        <span class="font-bold">{{ number_format(collect($cart)->sum('subtotal'), 0, '.', ',') }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">{{ __('Tax') }}</span>
                        <span class="font-bold">0</span>
                    </div>
                    <div class="flex justify-between text-sm text-red-500">
                        <span class="text-red-500">{{ __('Discount') }}</span>
                        <span class="font-bold">-0</span>
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
                // Trigger Livewire action or navigation
                // Livewire.dispatch('processPayment');
                // For now, let's just log or alert
                console.log('Payment triggered');
            }
        });
    </script>
</div>
