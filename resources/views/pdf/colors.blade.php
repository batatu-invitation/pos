<!DOCTYPE html>
<html>
<head>
    <title>Colors List</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Colors List</h2>
        <p>Generated on {{ now()->format('d/m/Y H:i') }}</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Class</th>
                <th>Type</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($colors as $color)
                <tr>
                    <td>{{ $color->name }}</td>
                    <td>{{ $color->class }}</td>
                    <td>{{ $color->tenant_id ? 'Custom' : 'Global' }}</td>
                    <td>{{ $color->created_at->format('d/m/Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
