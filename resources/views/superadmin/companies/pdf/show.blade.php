<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $company->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .meta { color: #555; margin-bottom: 18px; }
        .section { margin-bottom: 18px; }
        .label { color: #555; font-size: 11px; text-transform: uppercase; }
        .value { font-size: 14px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>{{ $company->name }}</h1>
    <div class="meta">
        {{ $company->slug }} · {{ ucfirst($company->status) }} · Generated {{ $generatedAt->toDayDateTimeString() }}
    </div>

    <div class="section">
        <div class="label">Counts</div>
        <table>
            <tr>
                <th>Users</th>
                <th>Leads</th>
                <th>Customers</th>
                <th>Tasks</th>
                <th>Roles</th>
            </tr>
            <tr>
                <td>{{ $company->users_count }}</td>
                <td>{{ $company->leads_count }}</td>
                <td>{{ $company->customers_count }}</td>
                <td>{{ $company->tasks_count }}</td>
                <td>{{ $company->roles_count }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="label">Company admins</div>
        @if ($admins->isEmpty())
            <div class="value">No admin users assigned.</div>
        @else
            <table>
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($admins as $admin)
                    <tr>
                        <td>{{ $admin->name }}</td>
                        <td>{{ $admin->email }}</td>
                        <td>{{ $admin->status }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</body>
</html>
