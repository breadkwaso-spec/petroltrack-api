<?php

namespace App\Http\Controllers;

use App\Models\Borrower;
use App\Models\FuelGrade;
use App\Models\FuelSale;
use App\Models\FuelStock;
use App\Models\Loan;
use App\Models\Pump;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FuelSaleController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $activeShift = $user->activeShift;

        $pumps = Pump::orderBy('pump_no')->get();
        $grades = FuelGrade::with(['stock', 'currentPrice'])->orderBy('name')->get();
        $borrowers = Borrower::orderBy('full_name')->get();

        $sales = FuelSale::with(['grade', 'pump', 'shift'])
            ->latest('id')
            ->paginate(20);

        $pendingSaleIds = \App\Models\VoidRequest::where('status', 'Pending')
            ->pluck('fuel_sale_id')
            ->all();

        return view('sales.index', compact(
            'user',
            'activeShift',
            'pumps',
            'grades',
            'borrowers',
            'sales',
            'pendingSaleIds'
        ));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $activeShift = $user->activeShift;

        if (!$activeShift) {
            return back()->withErrors(['sale' => 'You must have an active shift before recording a fuel sale.'])->withInput();
        }

        $validated = $request->validate([
            'pump_id' => ['required', 'exists:pumps,id'],
            'grade_id' => ['required', 'exists:fuel_grades,id'],
            'liters' => ['required', 'numeric', 'gt:0', 'max:99999.99'],
            'payment_method' => ['required', 'in:Cash,Card,Loan'],
            'entry_source' => ['nullable', 'in:Online,Offline'],
            'source' => ['nullable', 'in:Online,Offline'],
            'borrower_id' => ['nullable', 'required_if:payment_method,Loan', 'exists:borrowers,id'],
            'due_date' => ['nullable', 'date'],
        ]);

        try {
            DB::transaction(function () use ($validated, $user, $activeShift) {
                $grade = FuelGrade::with('currentPrice')
                    ->where('id', $validated['grade_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $stock = FuelStock::where('grade_id', $grade->id)->lockForUpdate()->first();

                $liters = round((float) $validated['liters'], 2);
                $unitPrice = round((float) ($grade->currentPrice?->price_per_liter ?? 0), 2);
                $totalAmount = round($liters * $unitPrice, 2);

                if ($unitPrice <= 0) {
                    throw new \RuntimeException("Fuel grade {$grade->name} does not have an active price configured.");
                }

                if (!$stock || (float) $stock->liters_on_hand < $liters) {
                    $available = $stock ? number_format((float) $stock->liters_on_hand, 2) : '0.00';
                    throw new \RuntimeException("Insufficient fuel stock for {$grade->name} ({$available} L available).");
                }

                $borrower = null;
                if ($validated['payment_method'] === 'Loan') {
                    $borrower = Borrower::where('id', $validated['borrower_id'])->lockForUpdate()->firstOrFail();

                    if ($borrower->credit_limit !== null && (float) $borrower->credit_limit > 0) {
                        $creditLimit = round((float) $borrower->credit_limit, 2);
                        $openBalance = round((float) Loan::where('borrower_id', $borrower->id)
                            ->whereNotIn('status', ['PAID', 'VOIDED'])
                            ->sum('balance'), 2);

                        if (round($openBalance + $totalAmount, 2) > $creditLimit) {
                            $remainingCredit = max(0, round($creditLimit - $openBalance, 2));
                            throw new \RuntimeException(
                                "Loan sale (₱" . number_format($totalAmount, 2) . ") exceeds {$borrower->full_name}'s credit limit of ₱" .
                                number_format($creditLimit, 2) . " (Available credit: ₱" . number_format($remainingCredit, 2) . ")."
                            );
                        }
                    }
                }

                $now = now();
                $source = $validated['entry_source'] ?? $validated['source'] ?? 'Online';

                $sale = FuelSale::create([
                    'entry_id' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'shift_id' => $activeShift->id,
                    'pump_id' => $validated['pump_id'],
                    'grade_id' => $grade->id,
                    'liters' => $liters,
                    'unit_price' => $unitPrice,
                    'total_amount' => $totalAmount,
                    'payment_method' => $validated['payment_method'],
                    'source' => $source,
                    'recorded_at' => $now,
                    'synced_at' => $source === 'Online' ? $now : null,
                    'status' => 'Valid',
                ]);

                $stock->update([
                    'liters_on_hand' => round((float) $stock->liters_on_hand - $liters, 2),
                ]);

                if ($validated['payment_method'] === 'Loan' && $borrower) {
                    Loan::create([
                        'borrower_id' => $borrower->id,
                        'fuel_sale_id' => $sale->id,
                        'amount' => $totalAmount,
                        'balance' => $totalAmount,
                        'issued_at' => $now,
                        'due_date' => $validated['due_date'] ?? null,
                        'status' => 'UNPAID',
                    ]);
                }

                AuditLogger::log('FUEL_SALE_CREATED', 'FuelSale', $sale->id, $user->id);
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['sale' => $e->getMessage()])->withInput();
        }

        return back()->with('status', 'Fuel sale recorded successfully.');
    }
}