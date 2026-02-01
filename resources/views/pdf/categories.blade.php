<!DOCTYPE html>
<html>
<head>
    <title>Categories Report</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 2px 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .footer { position: fixed; bottom: 0; width: 100%; font-size: 10px; text-align: right; color: #666; }
        .emoji { font-family: "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Categories Report</h1>
        <p>Generated on {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Icon</th>
                <th>Description</th>
                <th>Items Count</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($categories as $category)
            <tr>
                <td>{{ $category->name }}</td>
                <td class="emoji">{{ $category->icon }}</td>
                <td>{{ $category->description ?? '-' }}</td>
                <td>{{ $category->products()->count() }}</td>
                <td>{{ $category->created_at ? $category->created_at->format('d/m/Y') : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Page <span class="page-number"></span>
    </div>
</body>
</html>
