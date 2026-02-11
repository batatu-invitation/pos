<!DOCTYPE html>
<html>
<head>
    <title>Trial Balance</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f3f4f6; font-weight: bold; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .bg-gray { background-color: #f9fafb; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Trial Balance</h2>
        <p>As of {{ \Carbon\Carbon::parse($asOfDate)->format('d M Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Account Name</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report['lines'] as $line)
                <tr>
                    <td>{{ $line['code'] }}</td>
                    <td>{{ $line['name'] }}</td>
                    <td class="text-right">{{ $line['debit'] > 0 ? number_format($line['debit'], 0, ',', '.') : '-' }}</td>
                    <td class="text-right">{{ $line['credit'] > 0 ? number_format($line['credit'], 0, ',', '.') : '-' }}</td>
                </tr>
            @endforeach
            <tr class="bg-gray bold">
                <td colspan="2" class="text-right">TOTAL</td>
                <td class="text-right">{{ number_format($report['total_debit'], 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($report['total_credit'], 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
