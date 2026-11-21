<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetrolTrack — Dashboard</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f3f4f6; margin: 0; padding: 1.5rem; }
        .container { max-width: 960px; margin: 0 auto; }
        .topbar { display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 1rem 1.25rem; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 1rem; flex-wrap: wrap; gap: .75rem; }
        .card { background: #fff; padding: 1.25rem; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 1rem; }
        button, .btn { padding: .6rem 1rem; border-radius: 6px; border: 0; font-weight: 600; cursor: pointer; min-height: 40px; text-decoration: none; display: inline-flex; align-items: center; font-size: .9rem; }
        .btn-primary { background: #16a34a; color: #fff; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-secondary { background: #e5e7eb; color: #111827; }
        .alert-status { background: #ecfdf5; color: #065f46; padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .alert-error { background: #fef2f2; color: #991b1b; padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .alert-warn { background: #fffbeb; color: #92400e; padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1rem; }
    </style>
</head>
<body>
<div class="container">
    <div class="topbar">
        <div>
            <strong>PetrolTrack</strong> — {{ $user->full_name }} ({{ $user->employee_id }}) · <em>{{ $user->role }}</em>
        </div>
        <div style="display:flex; gap:.5rem; align-items:center; flex-wrap:wrap;">
            <a class="btn btn-secondary" href="{{ route('sales.index') }}">Sales Logbook</a>
            <a class="btn btn-secondary" href="{{ route('loans.index') }}">Borrowers & Loans</a>
            @if ($user->isOwner())
                <a class="btn btn-secondary" href="{{ route('owner.reports') }}">Reports</a>
                <a class="btn btn-secondary" href="{{ route('owner.inventory') }}">Fuel & Stock</a>
                <a class="btn btn-secondary" href="{{ route('owner.voids') }}">
                    Void Requests @if(($pendingVoidsCount ?? 0) > 0) ({{ $pendingVoidsCount }}) @endif
                </a>
                <a class="btn btn-secondary" href="{{ route('owner.accounts') }}">
                    Account Approvals @if($pendingCount > 0) ({{ $pendingCount }}) @endif
                </a>
                <a class="btn btn-secondary" href="{{ route('owner.audit-logs') }}">Audit Logs</a>
            @endif
            <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                @csrf
                <button type="submit" class="btn btn-secondary">Logout</button>
            </form>
        </div>
    </div>

    @if (session('status'))
        <div class="alert-status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    @if (isset($lowStockGrades) && $lowStockGrades->isNotEmpty())
        <div class="alert-warn">
            <strong>Low Stock Alert:</strong>
            {{ $lowStockGrades->pluck('name')->join(', ') }} {{ $lowStockGrades->count() === 1 ? 'is' : 'are' }} at or below the low-stock threshold.
        </div>
    @endif

    @if (session('shift_summary'))
        @php $s = session('shift_summary'); @endphp
        <div class="card">
            <h3 style="margin-top:0;">Shift Summary (#{{ $s['shift_id'] }})</h3>
            <p><strong>Started:</strong> {{ $s['started_at'] }} | <strong>Ended:</strong> {{ $s['ended_at'] }}</p>
            <p><strong>Total Sales Count:</strong> {{ $s['total_sales_count'] }}</p>
            <p><strong>Cash Total:</strong> ₱{{ number_format($s['cash_total'], 2) }} | <strong>Card Total:</strong> ₱{{ number_format($s['card_total'], 2) }} | <strong>Credit Issued:</strong> ₱{{ number_format($s['credit_issued'], 2) }}</p>
        </div>
    @endif

    <div class="card">
        <h3 style="margin-top:0;">Shift Status</h3>
        @if ($activeShift)
            <p>Active shift started at <strong>{{ $activeShift->started_at->format('M d, Y h:i A') }}</strong>.</p>
            <form method="POST" action="{{ route('shifts.end') }}">
                @csrf
                <button type="submit" class="btn btn-danger">End Shift</button>
            </form>
        @else
            <p>You do not have an active shift. Start a shift before recording sales.</p>
            <form method="POST" action="{{ route('shifts.start') }}">
                @csrf
                <button type="submit" class="btn btn-primary">Start Shift</button>
            </form>
        @endif
    </div>
</div>
</body>
</html>