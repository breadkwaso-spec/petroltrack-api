<?php

namespace App\Http\Controllers;

use App\Models\FuelGrade;
use App\Models\FuelSale;
use App\Models\Loan;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->query('period', 'daily');
        if (!in_array($period, ['daily', 'weekly', 'monthly', 'yearly'], true)) {
            $period = 'daily';
        }

        $selectedDate = $request->query('date', now()->toDateString());
        try {
            $anchor = Carbon::parse($selectedDate);
        } catch (\Throwable $e) {
            $anchor = now();
            $selectedDate = $anchor->toDateString();
        }

        [$start, $end, $periodLabel] = match ($period) {
            'weekly' => [
                $anchor->copy()->startOfWeek(),
                $anchor->copy()->endOfWeek(),
                'Week of ' . $anchor->copy()->startOfWeek()->format('M d, Y') . ' – ' . $anchor->copy()->endOfWeek()->format('M d, Y'),
            ],
            'monthly' => [
                $anchor->copy()->startOfMonth(),
                $anchor->copy()->endOfMonth(),
                $anchor->format('F Y'),
            ],
            'yearly' => [
                $anchor->copy()->startOfYear(),
                $anchor->copy()->endOfYear(),
                'Year ' . $anchor->format('Y'),
            ],
            default => [
                $anchor->copy()->startOfDay(),
                $anchor->copy()->endOfDay(),
                $anchor->format('F d, Y'),
            ],
        };

        $validSalesQuery = FuelSale::with(['grade', 'pump'])
            ->where('status', 'Valid')
            ->whereBetween('created_at', [$start, $end]);

        $sales = (clone $validSalesQuery)->latest('id')->get();

        $summary = [
            'transactions' => $sales->count(),
            'total_liters' => round((float) $sales->sum('liters'), 2),
            'total_revenue' => round((float) $sales->sum('total_amount'), 2),
            'cash_revenue' => round((float) $sales->where('payment_method', 'Cash')->sum('total_amount'), 2),
            'card_revenue' => round((float) $sales->where('payment_method', 'Card')->sum('total_amount'), 2),
            'loan_revenue' => round((float) $sales->where('payment_method', 'Loan')->sum('total_amount'), 2),
            'voided_count' => FuelSale::where('status', 'Voided')
                ->whereBetween('created_at', [$start, $end])
                ->count(),
            'outstanding_loans' => round((float) Loan::whereNotIn('status', ['PAID', 'VOIDED'])->sum('balance'), 2),
        ];

        $gradeBreakdown = FuelGrade::orderBy('name')->get()->map(function ($grade) use ($sales) {
            $gradeSales = $sales->where('grade_id', $grade->id);
            return [
                'name' => $grade->name,
                'transactions' => $gradeSales->count(),
                'liters' => round((float) $gradeSales->sum('liters'), 2),
                'revenue' => round((float) $gradeSales->sum('total_amount'), 2),
            ];
        });

        return view('owner.reports', compact(
            'period',
            'selectedDate',
            'periodLabel',
            'summary',
            'gradeBreakdown',
            'sales'
        ));
    }
}