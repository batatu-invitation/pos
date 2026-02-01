<!DOCTYPE html>
<html>
<head>
    <title>Products Report</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 20px; }
        .meta { margin-bottom: 20px; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Products Report</h2>
    </div>
    <div class="meta">
        <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>SKU</th>
                <th>Category</th>
                <th>Price</th>
                <th>Cost</th>
                <th>Margin</th>
                <th>Stock</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
                <tr>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->sku }}</td>
                    <td>{{ $product->category->name ?? 'Uncategorized' }}</td>
                    <td class="text-right">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($product->cost, 0, ',', '.') }}</td>
                    <td class="text-right">{{ $product->margin }}%</td>
                    <td class="text-right">{{ $product->stock }}</td>
                    <td>{{ $product->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
