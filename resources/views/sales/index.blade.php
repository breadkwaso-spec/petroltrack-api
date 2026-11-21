<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetrolTrack — Fuel Sales Logbook</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f3f4f6; margin: 0; padding: 1.5rem; }
        .container { max-width: 1150px; margin: 0 auto; }
        .card { background: #fff; padding: 1.25rem; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 1rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: .85rem; }
        label { display: block; font-weight: 600; margin-bottom: .3rem; font-size: .9rem; }
        input, select { width: 100%; padding: .5rem .65rem; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; min-height: 38px; }
        button, .btn { padding: .5rem .85rem; border-radius: 6px; border: 0; font-weight: 600; cursor: pointer; min-height: 38px; text-decoration: none; display: inline-flex; align-items: center; font-size: .85rem; }
        .btn-primary { background: #1d4ed8; color: #fff; }
        .btn-warn { background: #d97706; color: #fff; }
        .btn-back { background: #e5e7eb; color: #111827; }
        table { width: 100%; border-collapse: collapse; margin-top: .75rem; font-size: .88rem; }
        th, td { padding: .6rem; border-bottom: 1px solid #e5e7eb; text-align: left; vertical-align: middle; }
        .alert-status { background: #ecfdf5; color: #065f46; padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .alert-error { background: #fef2f2; color: #991b1b; padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .alert-warn { background: #fffbeb; color: #92400e; padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1rem; }
    </style>
</head>
<body>
<div class="container">
    <div style="margin-bottom:1rem; display:flex; justify-content:space-between; align-items:center;">
        <a href="{{ route('dashboard') }}" class="btn btn-back">← Back to Dashboard</a>
        <div><strong>Active Shift:</strong> {{ $activeShift ? 'Shift #' . $activeShift->id : 'None' }}</div>
    </div>

    @if (session('status'))
        <div class="alert-status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    @unless ($activeShift)
        <div class="alert-warn">No active shift found. Start a shift on the dashboard before recording new sales.</div>
    @endunless

    <div class="card">
        <h3 style="margin-top:0;">Current Fuel Prices & Stock</h3>
        <div class="grid">
            @foreach ($grades as $g)
                <div style="border:1px solid #e5e7eb; border-radius:8px; padding:.75rem;">
                    <strong>{{ $g->name }}</strong><br>
                    <span>Price: <strong>₱{{ number_format($g->currentPrice?->price_per_liter ?? 0, 2) }}</strong> / L</span><br>
                    <span>Stock: <strong>{{ number_format($g->stock?->liters_on_hand ?? 0, 2) }}</strong> L</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Record Fuel Sale</h3>
        <form method="POST" action="{{ route('sales.store') }}">
            @csrf
            <div class="grid">
                <div>
                    <label for="pump_id">Pump</label>
                    <select id="pump_id" name="pump_id" required>
                        @foreach ($pumps as $pump)
                            <option value="{{ $pump->id }}" @selected(old('pump_id') == $pump->id)>{{ $pump->pump_no }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="grade_id">Fuel Grade</label>
                    <select id="grade_id" name="grade_id" required>
                        @foreach ($grades as $grade)
                            <option value="{{ $grade->id }}" @selected(old('grade_id') == $grade->id)>
                                {{ $grade->name }} (₱{{ number_format($grade->currentPrice?->price_per_liter ?? 0, 2) }}/L)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="liters">Liters</label>
                    <input id="liters" type="number" step="0.01" min="0.01" name="liters" value="{{ old('liters') }}" required>
                </div>

                <div>
                    <label for="payment_method">Payment Method</label>
                    <select id="payment_method" name="payment_method" required>
                        <option value="Cash" @selected(old('payment_method') === 'Cash')>Cash</option>
                        <option value="Card" @selected(old('payment_method') === 'Card')>Card</option>
                        <option value="Loan" @selected(old('payment_method') === 'Loan')>Loan</option>
                    </select>
                </div>

                <div>
                    <label for="source">Entry Source</label>
                    <select id="source" name="source" required>
                        <option value="Online" @selected(old('source') === 'Online')>Online</option>
                        <option value="Offline" @selected(old('source') === 'Offline')>Offline</option>
                    </select>
                </div>

                <div>
                    <label for="borrower_id">Borrower (if Loan)</label>
                    <select id="borrower_id" name="borrower_id">
                        <option value="">— Select Borrower —</option>
                        @foreach ($borrowers as $borrower)
                            <option value="{{ $borrower->id }}" @selected(old('borrower_id') == $borrower->id)>
                                {{ $borrower->full_name }} ({{ $borrower->plate_no }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="due_date">Loan Due Date (optional)</label>
                    <input id="due_date" type="date" name="due_date" value="{{ old('due_date') }}">
                </div>
            </div>

            <div style="margin-top:1rem;">
                <button type="submit" class="btn btn-primary" @unless($activeShift) disabled @endunless>Save Fuel Sale</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Recent Sales Logbook</h3>
        <table>
            <thead>
            <tr>
                <th>Time</th>
                <th>Entry ID</th>
                <th>Pump</th>
                <th>Grade</th>
                <th>Liters</th>
                <th>Unit Price</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Source</th>
                <th>Status</th>
                <th>Void Request</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($sales as $sale)
                <tr>
                    <td>{{ $sale->recorded_at?->format('M d, h:i A') }}</td>
                    <td style="font-family:monospace; font-size:.8rem;">{{ \Illuminate\Support\Str::limit($sale->entry_id, 13) }}</td>
                    <td>{{ $sale->pump?->pump_no }}</td>
                    <td>{{ $sale->grade?->name }}</td>
                    <td>{{ number_format($sale->liters, 2) }} L</td>
                    <td>₱{{ number_format($sale->unit_price, 2) }}</td>
                    <td><strong>₱{{ number_format($sale->total_amount, 2) }}</strong></td>
                    <td>{{ $sale->payment_method }}</td>
                    <td>{{ $sale->source }}</td>
                    <td><strong>{{ $sale->status }}</strong></td>
                    <td>
                        @if ($sale->status === 'Valid')
                            <form method="POST" action="{{ route('sales.void-requests.store', $sale) }}" style="display:flex; gap:.35rem; align-items:center; margin:0;">
                                @csrf
                                <input type="text" name="reason" placeholder="Void reason..." style="width:130px;" required>
                                <button type="submit" class="btn btn-warn">Request Void</button>
                            </form>
                        @else
                            <span style="color:#6b7280;">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="11" style="color:#6b7280;">No fuel sales recorded yet.</td></tr>
            @endforelse
            </tbody>
        </table>

        <div style="margin-top:.75rem;">
            {{ $sales->links() }}
        </div>
    </div>
</div>
</body>
</html>