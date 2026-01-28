<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Receipt {{ $sale->invoice_number }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        .mb-4 { margin-bottom: 1rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mt-4 { margin-top: 1rem; }
        .border-dashed { border-bottom: 1px dashed #000; margin: 10px 0; }
        .flex { display: table; width: 100%; }
        .justify-between { display: table; width: 100%; }
        .row { display: table-row; }
        .cell { display: table-cell; }
        .w-full { width: 100%; }
        .text-xs { font-size: 10px; }
        .text-sm { font-size: 12px; }
        .text-xl { font-size: 16px; }
    </style>
</head>
<body>
    <div class="text-center mb-4">
        <h2 class="text-xl font-bold uppercase">POS Pro Store</h2>
        <p class="text-xs">123 Business Street, City, Country</p>
        <p class="text-xs">Tel: +1 234 567 890</p>
    </div>

    <div class="border-dashed"></div>

    <table class="w-full text-xs mb-2">
        <tr>
            <td class="text-left">Date: {{ $sale->created_at->format('Y-m-d') }}</td>
            <td class="text-right">Time: {{ $sale->created_at->format('h:i A') }}</td>
        </tr>
        <tr>
            <td class="text-left">Order: {{ $sale->invoice_number }}</td>
            <td class="text-right">Cashier: {{ $sale->user->name ?? 'Admin' }}</td>
        </tr>
    </table>

    <div class="border-dashed"></div>

    <table class="w-full text-xs">
        @foreach($sale->items as $item)
        <tr>
            <td class="text-left" colspan="2">{{ $item->product_name }}</td>
        </tr>
        <tr>
            <td class="text-left">{{ $item->quantity }} x Rp. {{ number_format($item->price, 2) }}</td>
            <td class="text-right">Rp. {{ number_format($item->total_price, 2) }}</td>
        </tr>
        @endforeach
    </table>

    <div class="border-dashed"></div>

    <table class="w-full font-bold text-sm">
        <tr>
            <td class="text-left">TOTAL</td>
            <td class="text-right">Rp. {{ number_format($sale->total_amount, 2) }}</td>
        </tr>
    </table>

    <table class="w-full text-xs mt-4">
        <tr>
            <td class="text-left">CASH</td>
            <td class="text-right">Rp. {{ number_format($sale->cash_received, 2) }}</td>
        </tr>
        <tr>
            <td class="text-left">CHANGE</td>
            <td class="text-right">Rp. {{ number_format($sale->change_amount, 2) }}</td>
        </tr>
    </table>

    <div class="border-dashed"></div>

    <div class="text-center text-xs">
        <p class="mb-2">Thank you for your purchase!</p>
        <p>Please visit us again.</p>
        <div class="mt-4">
            <p class="text-xs mt-1">{{ $sale->invoice_number }}</p>
        </div>
    </div>
</body>
</html>
