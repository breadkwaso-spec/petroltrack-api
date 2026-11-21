<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetrolTrack — Sales & Revenue Reports</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f3f4f6; margin: 0; padding: 1.5rem; }
        .container { max-width: 1100px; margin: 0 auto; }
        .card { background: #fff; padding: 1.25rem; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 1rem; }
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: .85rem; margin-bottom: 1rem; }
        .kpi { background: #fff; padding: 1rem 1.2rem; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .kpi-label { font-size: .82rem; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; }
        .kpi-value { font-size: 1.45rem; font-weight: 700; color: #111827; margin-top: .25rem; }
        .kpi-sub { font-size: .8rem; color: #4b5563; margin-top: .2rem; }
        .filter-bar { display: flex; flex-wrap: wrap; gap: .75rem; align-items: end; }
        label { display: block; font-weight: 600; margin-bottom: .3rem; font-size: .88rem; }
        input, select { padding: .5rem .65rem; border: 1px solid #d1d5db; border-radius: 6px; min-height: 38px; }
        button, .btn { padding: .5rem .9rem; border-radius: 6px; border: 0; font-weight: 600; cursor: pointer; min-height: 38px; text-decoration: none; display: inline-flex; align-items: center; font-size: .88rem; }
        .btn-primary { background: #1d4ed8; color: #fff; }
        .btn-back { background: #e5e7eb; color: #111827; }
        table { width: 100%; border-collapse: collapse; margin-top: .75rem; font-size: .88rem; }
        th, td { padding: .6rem; border-bottom: 1px solid #e5e7eb; text-align: left; }
        @media print { .no-print { display: none !important; } body { background: #fff; padding: 0; } .card, .kpi { box-shadow: none; border: 1px solid #e5e7eb; } }
    </style>
</head>
<body>
<div class="container">
    <div class="no-print" style="margin-bottom:1rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.5rem;">
        <a href="{{ route('dashboard') }}" class="btn btn-back">← Back to Dashboard</a>
        <button type="button" onclick="window.print()" class="btn btn-primary">Print Report</button>
    </div>

    <div class="card no-print">
        <h3 style="margin-top:0;">Filter Sales & Revenue Report</h3>
        <form method="GET" action="{{ route('owner.reports') }}" class="filter-bar">
            <div>
                <label for="period">Reporting Period</label>
                <select id="period" name="period">
                    <option value="daily" {{ $period === 'daily' ? 'selected' : '' }}>Daily</option>
                    <option value="weekly" {{ $period === 'weekly' ? 'selected' : '' }}>Weekly</option>
                    <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Monthly</option>
                    <option value="yearly" {{ $period === 'yearly' ? 'selected' : '' }}>Yearly</option>
                </select>
            </div>
            <div>
                <label for="date">Reference Date</label>
                <input id="date" type="date" name="date" value="{{ $selectedDate }}">
            </div>
            <div>
                <button type="submit" class="btn btn-primary">Generate Report</button>
            </div>
        </form>
    </div>

    <div style="margin-bottom:.75rem;">
        <h2 style="margin:0;">Sales & Revenue Summary — {{ $periodLabel }}</h2>
        <p style="margin:.25rem 0 0; color:#6b7280; font-size:.9rem;">
            Includes <strong>Valid</strong> fuel transactions only ({{ $summary['voided_count'] }} voided {{ $summary['voided_count'] === 1 ? 'sale' : 'sales' }} excluded).
        </p>
    </div>

    <div class="kpi-grid">
        <div class="kpi">
            <div class="kpi-label">Total Valid Revenue</div>
            <div class="kpi-value">₱{{ number_format($summary['total_revenue'], 2) }}</div>
            <div class="kpi-sub">{{ $summary['transactions'] }} valid {{ $summary['transactions'] === 1 ? 'sale' : 'sales' }}</div>
        </div>
        <div class="kpi">
            <div class="kpi-label">Total Volume Dispensed</div>
            <div class="kpi-value">{{ number_format($summary['total_liters'], 2) }} L</div>
            <div class="kpi-sub">Across all fuel grades</div>
        </div>
        <div class="kpi">
            <div class="kpi-label">Payment Breakdown</div>
            <div class="kpi-value" style="font-size:1.05rem; line-height:1.45;">
                Cash: ₱{{ number_format($summary['cash_revenue'], 2) }}<br>
                Card: ₱{{ number_format($summary['card_revenue'], 2) }}<br>
                Loan: ₱{{ number_format($summary['loan_revenue'], 2) }}
            </div>
        </div>
        <div class="kpi">
            <div class="kpi-label">Open Customer Loans</div>
            <div class="kpi-value">₱{{ number_format($summary['outstanding_loans'], 2) }}</div>
            <div class="kpi-sub">Current unpaid receivables</div>
        </div>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Sales by Fuel Grade</h3>
        <table>
            <thead>
            <tr>
                <th>Fuel Grade</th>
                <th>Transactions</th>
                <th>Volume Sold (L)</th>
                <th>Gross Revenue</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($gradeBreakdown as $row)
                <tr>
                    <td><strong>{{ $row['name'] }}</strong></td>
                    <td>{{ $row['transactions'] }}</td>
                    <td>{{ number_format($row['liters'], 2) }} L</td>
                    <td><strong>₱{{ number_format($row['revenue'], 2) }}</strong></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Valid Transactions in Period</h3>
        <table>
            <thead>
            <tr>
                <th>Date & Time</th>
                <th>Entry ID</th>
                <th>Pump</th>
                <th>Fuel Grade</th>
                <th>Liters</th>
                <th>Payment</th>
                <th>Total</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($sales as $sale)
                <tr>
                    <td>{{ $sale->created_at?->format('M d, Y h:i A') }}</td>
                    <td style="font-family:monospace; font-size:.82rem;">{{ \Illuminate\Support\Str::limit($sale->entry_id, 14) }}</td>
                    <td>{{ $sale->pump?->pump_no }}</td>
                    <td>{{ $sale->grade?->name }}</td>
                    <td>{{ number_format($sale->liters, 2) }} L</td>
                    <td>{{ $sale->payment_method }}</td>
                    <td><strong>₱{{ number_format($sale->total_amount, 2) }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="7" style="color:#6b7280;">No valid sales recorded for this period.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
</body>
</html>