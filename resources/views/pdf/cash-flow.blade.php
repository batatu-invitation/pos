<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Cash Flow Statement</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border-bottom: 1px solid #ddd;
            padding: 4px;
            text-align: left;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .section-title {
            font-weight: bold;
            background-color: #f2f2f2;
        }
        .amount {
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            background-color: #e6e6e6;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Cash Flow Statement</h2>
        <p>Period: {{ $startDate }} to {{ $endDate }}</p>
    </div>

    @php
        function calculateTotal($activities) {
            $total = 0;
            foreach ($activities as $activity) {
                if ($activity['type'] === 'positive') {
                    $total += $activity['amount'];
                } else {
                    $total -= $activity['amount'];
                }
            }
            return $total;
        }
        $totalOperating = calculateTotal($operatingActivities);
        $totalInvesting = calculateTotal($investingActivities);
        $totalFinancing = calculateTotal($financingActivities);
        $netCashFlow = $totalOperating + $totalInvesting + $totalFinancing;
    @endphp

    <table>
        <tbody>
            <tr class="section-title">
                <td colspan="2">Operating Activities</td>
            </tr>
            @foreach($operatingActivities as $activity)
                <tr>
                    <td>{{ $activity['name'] }}</td>
                    <td class="amount">
                        {{ $activity['type'] === 'negative' ? '(' : '' }}Rp. {{ number_format($activity['amount'], 0, ',', '.') }}{{ $activity['type'] === 'negative' ? ')' : '' }}
                    </td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td>Net Cash from Operating Activities</td>
                <td class="amount">
                    {{ $totalOperating < 0 ? '(' : '' }}Rp. {{ number_format(abs($totalOperating), 0, ',', '.') }}{{ $totalOperating < 0 ? ')' : '' }}
                </td>
            </tr>

            <tr class="section-title">
                <td colspan="2">Investing Activities</td>
            </tr>
            @foreach($investingActivities as $activity)
                <tr>
                    <td>{{ $activity['name'] }}</td>
                    <td class="amount">
                        {{ $activity['type'] === 'negative' ? '(' : '' }}Rp. {{ number_format($activity['amount'], 0, ',', '.') }}{{ $activity['type'] === 'negative' ? ')' : '' }}
                    </td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td>Net Cash from Investing Activities</td>
                <td class="amount">
                    {{ $totalInvesting < 0 ? '(' : '' }}Rp. {{ number_format(abs($totalInvesting), 0, ',', '.') }}{{ $totalInvesting < 0 ? ')' : '' }}
                </td>
            </tr>

            <tr class="section-title">
                <td colspan="2">Financing Activities</td>
            </tr>
            @foreach($financingActivities as $activity)
                <tr>
                    <td>{{ $activity['name'] }}</td>
                    <td class="amount">
                        {{ $activity['type'] === 'negative' ? '(' : '' }}Rp. {{ number_format($activity['amount'], 0, ',', '.') }}{{ $activity['type'] === 'negative' ? ')' : '' }}
                    </td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td>Net Cash from Financing Activities</td>
                <td class="amount">
                    {{ $totalFinancing < 0 ? '(' : '' }}Rp. {{ number_format(abs($totalFinancing), 0, ',', '.') }}{{ $totalFinancing < 0 ? ')' : '' }}
                </td>
            </tr>
        </tbody>
        <tfoot>
             <tr class="total-row" style="background-color: #ccc; border-top: 2px solid #000;">
                <td>Net Increase/Decrease in Cash</td>
                <td class="amount">
                    {{ $netCashFlow < 0 ? '(' : '' }}Rp. {{ number_format(abs($netCashFlow), 0, ',', '.') }}{{ $netCashFlow < 0 ? ')' : '' }}
                </td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
