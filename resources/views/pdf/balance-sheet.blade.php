<!DOCTYPE html>
<html>
<head>
    <title>Balance Sheet</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .section-header { font-weight: bold; margin-top: 15px; margin-bottom: 5px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 5px; text-align: left; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .pl-4 { padding-left: 20px; }
        .bg-gray { background-color: #f3f4f6; }
        .border-t { border-top: 1px solid #000; }
        .double-border-t { border-top: 3px double #000; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Balance Sheet</h2>
        <p>As of {{ \Carbon\Carbon::parse($date)->format('d F Y') }}</p>
    </div>

    <!-- Assets -->
    <div class="section-header">ASSETS</div>
    <table class="w-full">
        @foreach($assets as $groupName => $items)
            <tr>
                <td colspan="2" class="bold bg-gray">{{ $groupName }}</td>
            </tr>
            @foreach($items as $item)
                <tr>
                    <td class="pl-4">{{ $item['name'] }}</td>
                    <td class="text-right">{{ number_format($item['amount'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        @endforeach
        <tr>
            <td class="bold border-t">TOTAL ASSETS</td>
            <td class="text-right bold border-t">{{ number_format($totalAssets, 0, ',', '.') }}</td>
        </tr>
    </table>

    <br>

    <!-- Liabilities -->
    <div class="section-header">LIABILITIES</div>
    <table class="w-full">
        @foreach($liabilities as $groupName => $items)
            <tr>
                <td colspan="2" class="bold bg-gray">{{ $groupName }}</td>
            </tr>
            @foreach($items as $item)
                <tr>
                    <td class="pl-4">{{ $item['name'] }}</td>
                    <td class="text-right">{{ number_format($item['amount'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        @endforeach
        <tr>
            <td class="bold border-t">TOTAL LIABILITIES</td>
            <td class="text-right bold border-t">{{ number_format($totalLiabilities, 0, ',', '.') }}</td>
        </tr>
    </table>

    <br>

    <!-- Equity -->
    <div class="section-header">EQUITY</div>
    <table class="w-full">
        @foreach($equity as $groupName => $items)
            <tr>
                <td colspan="2" class="bold bg-gray">{{ $groupName }}</td>
            </tr>
            @foreach($items as $item)
                <tr>
                    <td class="pl-4">{{ $item['name'] }}</td>
                    <td class="text-right">{{ number_format($item['amount'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        @endforeach
        <tr>
            <td class="bold border-t">TOTAL EQUITY</td>
            <td class="text-right bold border-t">{{ number_format($totalEquity, 0, ',', '.') }}</td>
        </tr>
    </table>

    <br>
    <table class="w-full">
        <tr>
            <td class="bold bg-gray">TOTAL LIABILITIES & EQUITY</td>
            <td class="text-right bold bg-gray">{{ number_format($totalLiabilities + $totalEquity, 0, ',', '.') }}</td>
        </tr>
    </table>

</body>
</html>
