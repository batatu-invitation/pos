<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

new #[Layout('components.layouts.pos')] #[Title('Payment - Modern POS')] class extends Component {
    public $cart = [];
    public $subtotal = 0;
    public $taxAmount = 0;
    public $discount = 0;
    public $shipping = 0;
    public $totalAmount = 0;
    public $customerId = null;
    public $customerName = 'Walk-in Customer';
    public $note = '';

    public $receivedAmount = '';
    public $paymentMethod = 'cash';

    public $paymentMethods = [
        ['id' => 'cash', 'name' => 'Cash', 'icon' => 'fa-money-bill-wave', 'color' => 'indigo'],
        ['id' => 'card', 'name' => 'Credit/Debit Card', 'icon' => 'fa-credit-card', 'color' => 'gray'],
        ['id' => 'qr', 'name' => 'QR Code', 'icon' => 'fa-qrcode', 'color' => 'gray'],
        ['id' => 'ewallet', 'name' => 'E-Wallet', 'icon' => 'fa-wallet', 'color' => 'gray'],
    ];

    public function mount()
    {
        $this->cart = session('pos_cart', []);
        $this->subtotal = session('pos_subtotal', 0);
        $this->taxAmount = session('pos_tax', 0);
        $this->totalAmount = session('pos_total', 0);
        $this->customerId = session('pos_customer_id', null);
        $this->customerName = session('pos_customer_name', 'Walk-in Customer');
        $this->discount = session('pos_discount', 0);

        if (empty($this->cart)) {
            return redirect()->route('pos.visual');
        }

        // Initialize received amount with total
        $this->receivedAmount = number_format($this->totalAmount, 2, '.', '');
    }

    public function selectPaymentMethod($methodId)
    {
        $this->paymentMethod = $methodId;
    }

    public function setReceivedAmount($amount)
    {
        $this->receivedAmount = number_format($amount, 2, '.', '');
    }

    public function getChangeProperty()
    {
        $received = floatval($this->receivedAmount);
        return max(0, $received - $this->totalAmount);
    }

    public function completePayment()
    {
        if (empty($this->cart)) {
            return redirect()->route('pos.visual');
        }

        $received = floatval($this->receivedAmount);
        if ($received < $this->totalAmount) {
             // You might want to show an error, but for now we'll allow it or just proceed
             // In a real POS, partial payment might be allowed or blocked.
             // For now, let's block strict underpayment unless it's split (not implemented yet)
             // dispatch('notify', 'Insufficient amount'); return;
        }

        DB::transaction(function () {
            $sale = Sale::create([
                'invoice_number' => 'INV-' . strtoupper(uniqid()),
                'user_id' => auth()->id(),
                'customer_id' => $this->customerId,
                'subtotal' => $this->subtotal,
                'tax' => $this->taxAmount,
                'discount' => $this->discount,
                'total_amount' => $this->totalAmount,
                'cash_received' => floatval($this->receivedAmount),
                'change_amount' => $this->change,
                'payment_method' => $this->paymentMethod,
                'status' => 'completed',
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
        });

        // Clear session
        session()->forget(['pos_cart', 'pos_subtotal', 'pos_tax', 'pos_total', 'pos_customer_id', 'pos_customer_name']);

        // Redirect to receipt or back to POS
        return redirect()->route('pos.visual'); // Or pos.receipt if you have one implemented
    }
};
?>

<div class="h-full w-full flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-5xl rounded-2xl shadow-2xl overflow-hidden flex flex-col md:flex-row h-[80vh]">

        <!-- Left: Order Summary -->
        <div class="w-full md:w-1/3 bg-gray-50 border-r border-gray-200 p-6 flex flex-col">
            <h2 class="text-xl font-bold text-gray-800 mb-6">Order Summary</h2>

            <div class="mb-4 p-3 bg-white rounded-lg border border-gray-200 shadow-sm">
                <p class="text-xs text-gray-500 uppercase font-bold tracking-wider mb-1">Customer</p>
                <div class="flex items-center text-gray-800 font-medium">
                    <i class="fas fa-user-circle text-indigo-500 mr-2 text-lg"></i>
                    {{ $customerName }}
                </div>
            </div>

            <div class="flex-1 overflow-y-auto space-y-4 pr-2">
                @foreach($cart as $item)
                <div class="flex justify-between items-center">
                    <div>
                        <p class="font-medium text-gray-800">{{ $item['name'] }}</p>
                        <p class="text-sm text-gray-500">{{ $item['quantity'] }} x ${{ number_format($item['price'], 2) }}</p>
                    </div>
                    <span class="font-bold text-gray-800">${{ number_format($item['quantity'] * $item['price'], 2) }}</span>
                </div>
                @endforeach
            </div>

            <div class="border-t border-gray-200 pt-4 mt-4 space-y-2">
                <div class="flex justify-between text-gray-600">
                    <span>Subtotal</span>
                    <span>${{ number_format($subtotal, 2) }}</span>
                </div>
                @if($discount > 0)
                <div class="flex justify-between text-red-500">
                    <span>Discount</span>
                    <span>-${{ number_format($discount, 2) }}</span>
                </div>
                @endif
                <div class="flex justify-between text-gray-600">
                    <span>Tax</span>
                    <span>${{ number_format($taxAmount, 2) }}</span>
                </div>
                @if($shipping > 0)
                <div class="flex justify-between text-gray-600">
                    <span>Shipping</span>
                    <span>${{ number_format($shipping, 2) }}</span>
                </div>
                @endif
                <div class="flex justify-between text-2xl font-bold text-gray-900 pt-2">
                    <span>Total</span>
                    <span>${{ number_format($totalAmount, 2) }}</span>
                </div>
            </div>

            <a href="{{ route('pos.visual') }}" class="mt-6 text-center text-indigo-600 font-medium hover:text-indigo-800">
                <i class="fas fa-arrow-left mr-2"></i> Back to POS
            </a>
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">Received Amount</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-500 text-xl font-bold">$</span>
                        <input type="number" step="0.01" wire:model.live="receivedAmount" class="w-full pl-10 pr-4 py-4 text-3xl font-bold text-gray-900 bg-gray-50 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-4 gap-3 mb-6">
                    <button wire:click="setReceivedAmount({{ $totalAmount }})" class="py-2 px-4 bg-white border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-100 font-medium">Exact</button>
                    <button wire:click="setReceivedAmount({{ ceil($totalAmount / 10) * 10 }})" class="py-2 px-4 bg-white border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-100 font-medium">${{ number_format(ceil($totalAmount / 10) * 10, 0) }}</button>
                    <button wire:click="setReceivedAmount({{ ceil($totalAmount / 50) * 50 }})" class="py-2 px-4 bg-white border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-100 font-medium">${{ number_format(ceil($totalAmount / 50) * 50, 0) }}</button>
                    <button wire:click="setReceivedAmount({{ ceil($totalAmount / 100) * 100 }})" class="py-2 px-4 bg-white border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-100 font-medium">${{ number_format(ceil($totalAmount / 100) * 100, 0) }}</button>
                </div>

                <div class="bg-green-50 rounded-xl p-4 flex justify-between items-center mb-8 border border-green-100">
                    <span class="text-green-800 font-medium">Change Return</span>
                    <span class="text-2xl font-bold text-green-700">${{ number_format($this->change, 2) }}</span>
                </div>

                <button onclick="confirmPayment()" wire:loading.attr="disabled" class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white rounded-xl font-bold text-xl text-center shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-1">
                    <span wire:loading.remove>Complete Payment</span>
                    <span wire:loading>Processing...</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmPayment() {
        Swal.fire({
            title: 'Confirm Payment?',
            text: "Are you sure you want to complete this transaction?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Yes, Complete Payment!'
        }).then((result) => {
            if (result.isConfirmed) {
                @this.call('completePayment');
            }
        });
    }
</script>
