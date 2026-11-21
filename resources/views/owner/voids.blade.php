<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetrolTrack — Void Requests</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f3f4f6; margin: 0; padding: 1.5rem; }
        .container { max-width: 1050px; margin: 0 auto; }
        .card { background: #fff; padding: 1.25rem; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 1rem; }
        table { width: 100%; border-collapse: collapse; margin-top: .75rem; font-size: .88rem; }
        th, td { padding: .6rem; border-bottom: 1px solid #e5e7eb; text-align: left; }
        button, .btn { padding: .45rem .8rem; border-radius: 6px; border: 0; font-weight: 600; cursor: pointer; min-height: 36px; text-decoration: none; display: inline-flex; align-items: center; font-size: .85rem; }
        .btn-approve { background: #16a34a; color: #fff; }
        .btn-reject { background: #dc2626; color: #fff; }
        .btn-back { background: #e5e7eb; color: #111827; }
        .alert-status { background: #ecfdf5; color: #065f46; padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .alert-error { background: #fef2f2; color: #991b1b; padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1rem; }
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

    @if ($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    <div class="card">
        <h3 style="margin-top:0;">Pending Void Requests</h3>
        @if ($pendingRequests->isEmpty())
            <p style="color:#6b7280;">No pending void requests.</p>
        @else
            <table>
                <thead>
                <tr>
                    <th>Requested By</th>
                    <th>Sale Details</th>
                    <th>Amount</th>
                    <th>Reason</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($pendingRequests as $req)
                    <tr>
                        <td>{{ $req->requester?->full_name }} ({{ $req->requester?->employee_id }})</td>
                        <td>{{ $req->sale?->grade?->name }} · {{ number_format($req->sale?->liters ?? 0, 2) }} L ({{ $req->sale?->pump?->pump_no }})</td>
                        <td><strong>₱{{ number_format($req->sale?->total_amount ?? 0, 2) }}</strong></td>
                        <td>{{ $req->reason }}</td>
                        <td style="display:flex; gap:.4rem;">
                            <form method="POST" action="{{ route('owner.voids.approve', $req) }}" style="margin:0;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn-approve">Approve Void</button>
                            </form>
                            <form method="POST" action="{{ route('owner.voids.reject', $req) }}" style="margin:0;">
                                @csrf
                                @method('PATCH')
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
        <h3 style="margin-top:0;">Reviewed Void Requests</h3>
        <table>
            <thead>
            <tr>
                <th>Request #</th>
                <th>Sale</th>
                <th>Reason</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($reviewedRequests as $req)
                <tr>
                    <td>#{{ $req->id }}</td>
                    <td>{{ $req->sale?->grade?->name }} · ₱{{ number_format($req->sale?->total_amount ?? 0, 2) }}</td>
                    <td>{{ $req->reason }}</td>
                    <td><strong>{{ $req->status }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="4" style="color:#6b7280;">No reviewed void requests yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
</body>
</html>