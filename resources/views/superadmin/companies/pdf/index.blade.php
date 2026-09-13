<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Companies Export</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        .meta { color: #555; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>Companies</h1>
    <div class="meta">
        Generated {{ $generatedAt->toDayDateTimeString() }}
        @if (!empty($filters['search'])) · Search: {{ $filters['search'] }} @endif
        @if (!empty($filters['status'])) · Status: {{ $filters['status'] }} @endif
    </div>

    <table>
        <thead>
        <tr>
            <th>Name</th>
            <th>Slug</th>
            <th>Status</th>
            <th>Users</th>
            <th>Leads</th>
            <th>Customers</th>
            <th>Tasks</th>
            <th>Created</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($companies as $company)
            <tr>
                <td>{{ $company->name }}</td>
                <td>{{ $company->slug }}</td>
                <td>{{ $company->status }}</td>
                <td>{{ $company->users_count }}</td>
                <td>{{ $company->leads_count }}</td>
                <td>{{ $company->customers_count }}</td>
                <td>{{ $company->tasks_count }}</td>
                <td>{{ optional($company->created_at)->toDateString() }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="8">No companies found.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
