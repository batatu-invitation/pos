<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Audit Logs</title>
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
    </style>
</head>
<body>
    <div class="header">
        <h2>Audit Logs Report</h2>
        <p>Generated on: {{ now()->format('d M Y, H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>User</th>
                <th>Event</th>
                <th>Description</th>
                <th>Subject</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
                <tr>
                    <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $log->causer ? $log->causer->name : 'System' }}</td>
                    <td>{{ ucfirst($log->event) }}</td>
                    <td>{{ $log->description }}</td>
                    <td>{{ class_basename($log->subject_type) }} #{{ $log->subject_id }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
