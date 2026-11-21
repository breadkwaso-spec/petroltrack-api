<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetrolTrack — Audit Logs</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f3f4f6; margin: 0; padding: 1.5rem; }
        .container { max-width: 1050px; margin: 0 auto; }
        .card { background: #fff; padding: 1.25rem; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 1rem; }
        table { width: 100%; border-collapse: collapse; margin-top: .75rem; font-size: .88rem; }
        th, td { padding: .55rem; border-bottom: 1px solid #e5e7eb; text-align: left; }
        select, button, .btn { padding: .45rem .75rem; border-radius: 6px; border: 1px solid #d1d5db; min-height: 36px; }
        .btn-primary { background: #1d4ed8; color: #fff; border: 0; font-weight: 600; cursor: pointer; }
        .btn-back { background: #e5e7eb; color: #111827; text-decoration: none; display: inline-flex; align-items: center; font-weight: 600; border: 0; }
    </style>
</head>
<body>
<div class="container">
    <div style="margin-bottom:1rem;">
        <a href="{{ route('dashboard') }}" class="btn btn-back">← Back to Dashboard</a>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">System Audit Logs</h3>

        <form method="GET" action="{{ route('owner.audit-logs') }}" style="display:flex; gap:.5rem; align-items:center;">
            <select name="action">
                <option value="">All Actions</option>
                @foreach ($actions as $act)
                    <option value="{{ $act }}" @selected($actionFilter === $act)>{{ $act }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn-primary">Filter</button>
        </form>

        <table>
            <thead>
            <tr>
                <th>Timestamp</th>
                <th>User</th>
                <th>Action</th>
                <th>Entity</th>
                <th>Entity ID</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td>{{ $log->logged_at?->format('M d, Y h:i:s A') }}</td>
                    <td>{{ $log->user?->full_name ?? 'System' }} @if($log->user) ({{ $log->user->employee_id }}) @endif</td>
                    <td><strong>{{ $log->action }}</strong></td>
                    <td>{{ $log->entity ?? '—' }}</td>
                    <td style="font-family:monospace;">{{ $log->entity_id ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="color:#6b7280;">No audit log entries found.</td></tr>
            @endforelse
            </tbody>
        </table>

        <div style="margin-top:.75rem;">
            {{ $logs->links() }}
        </div>
    </div>
</div>
</body>
</html>