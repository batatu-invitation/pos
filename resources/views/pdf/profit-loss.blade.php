<!DOCTYPE html>
<html>
<head>
    <title>Profit & Loss Statement</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .section-title { font-weight: bold; font-size: 14px; margin-top: 15px; margin-bottom: 5px; border-bottom: 1px solid #ddd; padding-bottom: 3px; }
        .row { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .label { text-align: left; }
        .amount { text-align: right; }
        .total-row { font-weight: bold; border-top: 1px solid #000; margin-top: 5px; padding-top: 5px; }
        .net-profit { font-size: 16px; font-weight: bold; margin-top: 20px; padding: 10px; background-color: #f2f2f2; border: 1px solid #ddd; text-align: center; }
        .positive { color: green; }
        .negative { color: red; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Profit & Loss Statement</h2>
        <p>Period: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
    </div>

    <div>
        <div class="section-title">Revenue</div>
        @foreach($revenueItems as $item)
            <div class="row">
                <span class="label">{{ $item['name'] }}</span>
                <span class="amount">Rp. {{ number_format($item['amount'], 0, ',', '.') }}</span>
            </div>
        @endforeach
        <div class="row total-row">
            <span class="label">Total Revenue</span>
            <span class="amount">Rp. {{ number_format($totalRevenue, 0, ',', '.') }}</span>
        </div>
    </div>

    <div>
        <div class="section-title">Operating Expenses</div>
        @foreach($expenseItems as $item)
            <div class="row">
                <span class="label">{{ $item['name'] }}</span>
                <span class="amount">Rp. {{ number_format($item['amount'], 0, ',', '.') }}</span>
            </div>
        @endforeach
        <div class="row total-row">
            <span class="label">Total Expenses</span>
            <span class="amount">Rp. {{ number_format($totalExpenses, 0, ',', '.') }}</span>
        </div>
    </div>

    <div class="net-profit">
        Net Profit:
        <span class="{{ $netProfit >= 0 ? 'positive' : 'negative' }}">
            Rp. {{ number_format($netProfit, 0, ',', '.') }}
        </span>
    </div>
</body>
</html>
