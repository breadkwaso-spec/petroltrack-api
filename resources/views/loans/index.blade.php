<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetrolTrack — Borrowers & Loans</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f3f4f6; margin: 0; padding: 1.5rem; }
        .container { max-width: 1100px; margin: 0 auto; }
        .card { background: #fff; padding: 1.25rem; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 1rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: .85rem; }
        label { display: block; font-weight: 600; margin-bottom: .3rem; font-size: .9rem; }
        input, select { width: 100%; padding: .5rem .65rem; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; min-height: 38px; }
        button, .btn { padding: .5rem .85rem; border-radius: 6px; border: 0; font-weight: 600; cursor: pointer; min-height: 38px; text-decoration: none; display: inline-flex; align-items: center; font-size: .88rem; }
        .btn-primary { background: #1d4ed8; color: #fff; }
        .btn-success { background: #16a34a; color: #fff; }
        .btn-back { background: #e5e7eb; color: #111827; }
        table { width: 100%; border-collapse: collapse; margin-top: .75rem; font-size: .88rem; }
        th, td { padding: .6rem; border-bottom: 1px solid #e5e7eb; text-align: left; vertical-align: top; }
        .alert-status { background: #ecfdf5; color: #065f46; padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .alert-error { background: #fef2f2; color: #991b1b; padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .badge-paid { background: #dcfce7; color: #166534; padding: .2rem .5rem; border-radius: 999px; font-weight: 600; font-size: .78rem; }
        .badge-unpaid { background: #fee2e2; color: #991b1b; padding: .2rem .5rem; border-radius: 999px; font-weight: 600; font-size: .78rem; }
        .badge-partial { background: #fef3c7; color: #92400e; padding: .2rem .5rem; border-radius: 999px; font-weight: 600; font-size: .78rem; }
        .badge-voided { background: #e5e7eb; color: #374151; padding: .2rem .5rem; border-radius: 999px; font-weight: 600; font-size: .78rem; }
    </style>
</head>
<body>
<div class="container">
    <div style="margin-bottom:1rem; display:flex; justify-content:space-between; align-items:center;">
        <a href="{{ route('dashboard') }}" class="btn btn-back">← Back to Dashboard</a>
        <a href="{{ route('sales.index') }}" class="btn btn-primary">Go to Sales Logbook</a>
    </div>

    @if (session('status'))
        <div class="alert-status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    <div class="card">
        <h3 style="margin-top:0;">Add New Borrower</h3>
        <form method="POST" action="{{ route('borrowers.store') }}">
            @csrf
            <div class="grid">
                <div>
                    <label for="full_name">Full Name / Business</label>
                    <input id="full_name" type="text" name="full_name" value="{{ old('full_name') }}" placeholder="e.g. Pedro Reyes" required>
                </div>
                <div>
                    <label for="plate_no">Vehicle / Plate No.</label>
                    <input id="plate_no" type="text" name="plate_no" value="{{ old('plate_no') }}" placeholder="e.g. VCT-4821" required>
                </div>
                <div>
                    <label for="contact_no">Contact No. (optional)</label>
                    <input id="contact_no" type="text" name="contact_no" value="{{ old('contact_no') }}" placeholder="e.g. 0917-123-4567">
                </div>
                <div>
                    <label for="credit_limit">Credit Limit ₱ (optional)</label>
                    <input id="credit_limit" type="number" step="0.01" min="0" name="credit_limit" value="{{ old('credit_limit') }}" placeholder="e.g. 5000.00">
                </div>
            </div>
            <div style="margin-top:1rem;">
                <button type="submit" class="btn btn-primary">Save Borrower</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Borrower Directory</h3>
        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>Plate No.</th>
                <th>Contact</th>
                <th>Credit Limit</th>
                <th>Open Balance</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($borrowers as $b)
                <tr>
                    <td><strong>{{ $b->full_name }}</strong></td>
                    <td>{{ $b->plate_no }}</td>
                    <td>{{ $b->contact_no ?? '—' }}</td>
                    <td>{{ $b->credit_limit !== null ? '₱' . number_format($b->credit_limit, 2) : 'No limit' }}</td>
                    <td><strong>₱{{ number_format($b->total_balance ?? 0, 2) }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="5" style="color:#6b7280;">No borrowers added yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Fuel Credit / Loans & Payments</h3>
        <table>
            <thead>
            <tr>
                <th>Loan #</th>
                <th>Borrower</th>
                <th>Sale Details</th>
                <th>Total Loan</th>
                <th>Balance</th>
                <th>Status</th>
                <th>Record Payment</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($loans as $loan)
                @php
                    $badgeClass = match($loan->status) {
                        'PAID' => 'badge-paid',
                        'PARTIAL' => 'badge-partial',
                        'VOIDED' => 'badge-voided',
                        default => 'badge-unpaid',
                    };
                @endphp
                <tr>
                    <td>#{{ $loan->id }}</td>
                    <td>
                        <strong>{{ $loan->borrower?->full_name }}</strong><br>
                        <span style="color:#6b7280;">{{ $loan->borrower?->plate_no }}</span>
                    </td>
                    <td>
                        {{ $loan->sale?->grade?->name ?? 'Fuel' }} · {{ number_format($loan->sale?->liters ?? 0, 2) }} L
                    </td>
                    <td>₱{{ number_format($loan->amount, 2) }}</td>
                    <td><strong>₱{{ number_format($loan->balance, 2) }}</strong></td>
                    <td><span class="{{ $badgeClass }}">{{ $loan->status }}</span></td>
                    <td>
                        @if (!in_array($loan->status, ['PAID', 'VOIDED'], true) && (float) $loan->balance > 0)
                            <form method="POST" action="{{ route('loans.payments.store', $loan) }}" style="display:flex; flex-wrap:wrap; gap:.35rem; align-items:center;">
                                @csrf
                                <input type="number" step="0.01" min="0.01" max="{{ $loan->balance }}" name="amount" placeholder="Amount ₱" style="width:105px;" required>
                                <select name="payment_method" style="width:115px;">
                                    <option value="Cash">Cash</option>
                                    <option value="Card">Card</option>
                                    <option value="GCash">GCash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                </select>
                                <input type="text" name="reference_no" placeholder="Ref # (opt.)" style="width:105px;">
                                <button type="submit" class="btn btn-success">Pay</button>
                            </form>
                        @else
                            <span style="color:#6b7280;">{{ $loan->status === 'VOIDED' ? 'Voided' : 'Settled' }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" style="color:#6b7280;">No fuel loans issued yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
</body>
</html>