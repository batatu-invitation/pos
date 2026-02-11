<!DOCTYPE html>
<html>
<head>
    <title>General Ledger</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .account-info { margin-bottom: 15px; font-size: 14px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 6px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f3f4f6; font-weight: bold; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .bg-gray { background-color: #f9fafb; }
    </style>
</head>
<body>
    <div class="header">
        <h2>General Ledger</h2>
        <p>Period: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
    </div>

    <div class="account-info">
        @if($selectedAccount)
            Account: {{ $selectedAccount->code }} - {{ $selectedAccount->name }}
        @else
            Account: All Accounts
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Reference</th>
                <th>Description</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            <!-- Opening Balance -->
            <tr class="bg-gray">
                <td>{{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}</td>
                <td></td>
                <td class="bold">Opening Balance</td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right bold">{{ number_format($openingBalance, 0, ',', '.') }}</td>
            </tr>

            <!-- Transactions -->
            @foreach($ledgerItems as $item)
                <tr>
                    <td>{{ $item['date']->format('d M Y') }}</td>
                    <td>{{ $item['reference'] }}</td>
                    <td>{{ $item['description'] }}</td>
                    <td class="text-right">{{ $item['debit'] > 0 ? number_format($item['debit'], 0, ',', '.') : '-' }}</td>
                    <td class="text-right">{{ $item['credit'] > 0 ? number_format($item['credit'], 0, ',', '.') : '-' }}</td>
                    <td class="text-right">{{ number_format($item['balance'], 0, ',', '.') }}</td>
                </tr>
            @endforeach

            <!-- Closing Balance -->
            <tr class="bg-gray bold">
                <td>{{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</td>
                <td></td>
                <td>Closing Balance</td>
                <td class="text-right">{{ number_format($totalPeriodDebits, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($totalPeriodCredits, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($closingBalance, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
