<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetrolTrack — Fuel Prices & Stock</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f3f4f6; margin: 0; padding: 1.5rem; }
        .container { max-width: 1050px; margin: 0 auto; }
        .card { background: #fff; padding: 1.25rem; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 1rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: .85rem; }
        label { display: block; font-weight: 600; margin-bottom: .3rem; font-size: .9rem; }
        input, select { width: 100%; padding: .55rem .65rem; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; min-height: 40px; }
        button, .btn { padding: .6rem 1rem; border-radius: 6px; border: 0; font-weight: 600; cursor: pointer; min-height: 40px; text-decoration: none; display: inline-flex; align-items: center; }
        .btn-primary { background: #1d4ed8; color: #fff; }
        .btn-success { background: #16a34a; color: #fff; }
        .btn-back { background: #e5e7eb; color: #111827; }
        table { width: 100%; border-collapse: collapse; margin-top: .75rem; font-size: .9rem; }
        th, td { padding: .6rem; border-bottom: 1px solid #e5e7eb; text-align: left; }
        .alert-status { background: #ecfdf5; color: #065f46; padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .alert-error { background: #fef2f2; color: #991b1b; padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .low-stock { color: #b91c1c; font-weight: 700; }
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
        <h3 style="margin-top:0;">Current Fuel Stock & Active Prices</h3>
        <table>
            <thead>
            <tr>
                <th>Fuel Grade</th>
                <th>Current Price / L</th>
                <th>Liters on Hand</th>
                <th>Low Stock Threshold</th>
                <th>Stock Status</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($grades as $g)
                @php
                    $onHand = (float) ($g->stock?->liters_on_hand ?? 0);
                    $threshold = (float) ($g->stock?->low_stock_threshold ?? 500);
                    $isLow = $onHand <= $threshold;
                @endphp
                <tr>
                    <td><strong>{{ $g->name }}</strong></td>
                    <td>₱{{ number_format($g->currentPrice?->price_per_liter ?? 0, 2) }}</td>
                    <td>{{ number_format($onHand, 2) }} L</td>
                    <td>{{ number_format($threshold, 2) }} L</td>
                    <td><span class="{{ $isLow ? 'low-stock' : '' }}">{{ $isLow ? 'Low Stock' : 'Normal' }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="grid">
        <div class="card">
            <h3 style="margin-top:0;">Update Fuel Price</h3>
            <form method="POST" action="{{ route('owner.inventory.prices.store') }}">
                @csrf
                <label for="price_grade_id">Fuel Grade</label>
                <select id="price_grade_id" name="grade_id" required>
                    @foreach ($grades as $g)
                        <option value="{{ $g->id }}">{{ $g->name }}</option>
                    @endforeach
                </select>

                <label for="price_per_liter" style="margin-top:.75rem;">New Price per Liter (₱)</label>
                <input id="price_per_liter" type="number" step="0.01" min="0.01" name="price_per_liter" required>

                <div style="margin-top:1rem;">
                    <button type="submit" class="btn btn-primary">Save Price</button>
                </div>
            </form>
        </div>

        <div class="card">
            <h3 style="margin-top:0;">Record Fuel Delivery</h3>
            <form method="POST" action="{{ route('owner.inventory.deliveries.store') }}">
                @csrf
                <label for="del_grade_id">Fuel Grade</label>
                <select id="del_grade_id" name="grade_id" required>
                    @foreach ($grades as $g)
                        <option value="{{ $g->id }}">{{ $g->name }}</option>
                    @endforeach
                </select>

                <label for="liters" style="margin-top:.75rem;">Delivered Liters</label>
                <input id="liters" type="number" step="0.01" min="0.01" name="liters" required>

                <label for="cost_per_liter" style="margin-top:.75rem;">Cost per Liter (₱)</label>
                <input id="cost_per_liter" type="number" step="0.01" min="0" name="cost_per_liter" required>

                <label for="supplier" style="margin-top:.75rem;">Supplier Name</label>
                <input id="supplier" type="text" name="supplier" required>

                <div style="margin-top:1rem;">
                    <button type="submit" class="btn btn-success">Record Delivery</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Recent Fuel Deliveries</h3>
        <table>
            <thead>
            <tr>
                <th>Delivered At</th>
                <th>Grade</th>
                <th>Liters</th>
                <th>Cost / L</th>
                <th>Supplier</th>
                <th>Recorded By</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($recentDeliveries as $d)
                <tr>
                    <td>{{ $d->delivered_at?->format('M d, Y h:i A') }}</td>
                    <td>{{ $d->grade?->name }}</td>
                    <td>{{ number_format($d->liters, 2) }} L</td>
                    <td>₱{{ number_format($d->cost_per_liter, 2) }}</td>
                    <td>{{ $d->supplier }}</td>
                    <td>{{ $d->recorder?->full_name }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="color:#6b7280;">No fuel deliveries recorded yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
</body>
</html>