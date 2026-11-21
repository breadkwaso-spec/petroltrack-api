<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetrolTrack — Staff Account Approvals</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f3f4f6; margin: 0; padding: 1.5rem; }
        .container { max-width: 900px; margin: 0 auto; }
        .card { background: #fff; padding: 1.25rem; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 1rem; }
        table { width: 100%; border-collapse: collapse; margin-top: .75rem; }
        th, td { padding: .65rem; border-bottom: 1px solid #e5e7eb; text-align: left; }
        button, select, .btn { padding: .45rem .75rem; border-radius: 6px; border: 1px solid #d1d5db; min-height: 38px; }
        .btn-approve { background: #16a34a; color: #fff; border: 0; cursor: pointer; font-weight: 600; }
        .btn-reject { background: #dc2626; color: #fff; border: 0; cursor: pointer; font-weight: 600; }
        .btn-back { background: #e5e7eb; color: #111827; text-decoration: none; display: inline-flex; align-items: center; font-weight: 600; }
        .alert-status { background: #ecfdf5; color: #065f46; padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1rem; }
    </style>
</head>
<body>
<div class="container">
    <div style="margin-bottom:1rem;">
        <a href="{{ route('dashboard') }}" class="btn btn-back">← Back to Dashboard</a>
    </div>

    @if (session('status'))
        <div class="alert-status">{{ session('status') }}</div>
    @endif

    <div class="card">
        <h3 style="margin-top:0;">Pending Staff Registrations</h3>
        @if ($pendingUsers->isEmpty())
            <p style="color:#6b7280;">No pending accounts waiting for approval.</p>
        @else
            <table>
                <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Full Name</th>
                    <th>Assign Role & Approve</th>
                    <th>Reject</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($pendingUsers as $u)
                    <tr>
                        <td>{{ $u->employee_id }}</td>
                        <td>{{ $u->full_name }}</td>
                        <td>
                            <form method="POST" action="{{ route('owner.accounts.approve', $u) }}" style="display:flex; gap:.4rem; align-items:center; margin:0;">
                                @csrf
                                @method('PATCH')
                                <select name="role">
                                    <option value="Attendant">Attendant</option>
                                    <option value="Owner">Owner</option>
                                </select>
                                <button type="submit" class="btn-approve">Approve</button>
                            </form>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('owner.accounts.reject', $u) }}" style="margin:0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-reject">Reject</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Existing Staff Accounts</h3>
        <table>
            <thead>
            <tr>
                <th>Employee ID</th>
                <th>Full Name</th>
                <th>Role</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($staffUsers as $u)
                <tr>
                    <td>{{ $u->employee_id }}</td>
                    <td>{{ $u->full_name }}</td>
                    <td>{{ $u->role }}</td>
                    <td>{{ $u->status }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
</body>
</html>