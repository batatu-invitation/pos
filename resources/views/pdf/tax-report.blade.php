<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Tax Report</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .amount {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Tax Report</h2>
        <p>Period: {{ $start }} to {{ $end }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Tax Name</th>
                <th>Rate</th>
                <th class="amount">Taxable Amount</th>
                <th class="amount">Tax Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($details as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['rate'] > 0 ? $row['rate'] . '%' : '-' }}</td>
                    <td class="amount">Rp. {{ number_format($row['taxable'], 0, ',', '.') }}</td>
                    <td class="amount">Rp. {{ number_format($row['tax'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2" style="text-align: right;">Total</th>
                <th class="amount">Rp. {{ number_format(array_sum(array_column($details, 'taxable')), 0, ',', '.') }}</th>
                <th class="amount">Rp. {{ number_format(array_sum(array_column($details, 'tax')), 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
