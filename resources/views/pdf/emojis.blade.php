<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Emojis List</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 20px; }
        .emoji { font-size: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Emojis List</h2>
        <p>Generated on {{ now()->format('d/m/Y H:i') }}</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Icon</th>
                <th>Name</th>
                <th>Type</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($emojis as $emoji)
                <tr>
                    <td class="emoji">{{ $emoji->icon }}</td>
                    <td>{{ $emoji->name }}</td>
                    <td>{{ $emoji->tenant_id ? 'Custom' : 'Global' }}</td>
                    <td>{{ $emoji->created_at->format('d/m/Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
