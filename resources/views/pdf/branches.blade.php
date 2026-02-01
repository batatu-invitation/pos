<!DOCTYPE html>
<html>
<head>
    <title>Branches Report</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 20px; }
        .badge { padding: 2px 5px; border-radius: 3px; font-size: 9px; }
        .badge-success { background-color: #d4edda; color: #155724; }
        .badge-danger { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Branches List</h2>
        <p>Generated on {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Code</th>
                <th>Type</th>
                <th>Domain</th>
                <th>Location</th>
                <th>Manager</th>
                <th>Contact</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($branches as $branch)
                <tr>
                    <td>{{ $branch->name }}</td>
                    <td>{{ $branch->code }}</td>
                    <td>{{ ucfirst($branch->type) }}</td>
                    <td>{{ $branch->domains->first()?->domain ?? '-' }}</td>
                    <td>{{ $branch->location }}</td>
                    <td>{{ $branch->manager }}</td>
                    <td>
                        {{ $branch->phone }}<br>
                        <small>{{ $branch->email }}</small>
                    </td>
                    <td>
                        <span class="badge {{ $branch->status === 'Active' ? 'badge-success' : 'badge-danger' }}">
                            {{ $branch->status }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
