<!DOCTYPE html>
<html>
<head>
    <title>Inventory Valuation Report</title>
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
        <h2>Inventory Valuation Report</h2>
        <p>Generated on {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th class="amount">Stock</th>
                <th class="amount">Cost</th>
                <th class="amount">Price</th>
                <th class="amount">Cost Value</th>
                <th class="amount">Sales Value</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
                <tr>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->category->name ?? 'Uncategorized' }}</td>
                    <td class="amount">{{ $product->stock }}</td>
                    <td class="amount">{{ number_format($product->cost, 2) }}</td>
                    <td class="amount">{{ number_format($product->price, 2) }}</td>
                    <td class="amount">{{ number_format($product->stock * $product->cost, 2) }}</td>
                    <td class="amount">{{ number_format($product->stock * $product->price, 2) }}</td>
                </tr>
            @endforeach
            <tr style="font-weight: bold; background-color: #f9f9f9;">
                <td colspan="5" style="text-align: right;">Totals</td>
                <td class="amount">{{ number_format($totalCostValue, 2) }}</td>
                <td class="amount">{{ number_format($totalSalesValue, 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
