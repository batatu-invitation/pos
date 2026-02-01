<!DOCTYPE html>
<html>
<head>
    <title>Expenses Report</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 20px; }
        .amount { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Expenses Report</h2>
        <p>Period: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Category</th>
                <th>Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($expenses as $expense)
                <tr>
                    <td>{{ $expense->date->format('d/m/Y') }}</td>
                    <td>{{ $expense->description }}</td>
                    <td>{{ $expense->category }}</td>
                    <td class="amount">{{ number_format($expense->amount, 2) }}</td>
                    <td>{{ ucfirst($expense->status) }}</td>
                </tr>
            @endforeach
            <tr style="font-weight: bold; background-color: #f9f9f9;">
                <td colspan="3" style="text-align: right;">Total Expenses</td>
                <td class="amount">{{ number_format($totalAmount, 2) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
