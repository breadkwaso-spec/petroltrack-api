<?php

namespace App\Http\Controllers;

use App\Models\Borrower;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
    public function index()
    {
        $borrowers = Borrower::withCount('loans')
            ->withSum(['loans as total_balance' => fn ($q) => $q->whereNotIn('status', ['PAID', 'VOIDED'])], 'balance')
            ->orderBy('full_name')
            ->get();

        $loans = Loan::with(['borrower', 'sale.grade', 'sale.pump'])
            ->latest('id')
            ->get();

        return view('loans.index', compact('borrowers', 'loans'));
    }

    public function storeBorrower(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'plate_no' => ['required', 'string', 'max:30'],
            'contact_no' => ['nullable', 'string', 'max:40'],
            'credit_limit' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
        ]);

        $borrower = Borrower::create([
            'full_name' => trim($validated['full_name']),
            'plate_no' => strtoupper(trim($validated['plate_no'])),
            'contact_no' => !empty($validated['contact_no']) ? trim($validated['contact_no']) : null,
            'credit_limit' => isset($validated['credit_limit']) && $validated['credit_limit'] !== ''
                ? round((float) $validated['credit_limit'], 2)
                : null,
        ]);

        AuditLogger::log('BORROWER_CREATED', 'Borrower', $borrower->id);

        return back()->with('status', "Added borrower {$borrower->full_name} ({$borrower->plate_no}).");
    }

    public function recordPayment(Request $request, Loan $loan)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'reference_no' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            DB::transaction(function () use ($validated, $loan) {
                $lockedLoan = Loan::where('id', $loan->id)->lockForUpdate()->firstOrFail();

                if (in_array($lockedLoan->status, ['PAID', 'VOIDED'], true) || (float) $lockedLoan->balance <= 0) {
                    throw new \RuntimeException('This loan is already settled or voided.');
                }

                $paymentAmount = round((float) $validated['amount'], 2);
                $currentBalance = round((float) $lockedLoan->balance, 2);

                if ($paymentAmount > $currentBalance) {
                    throw new \RuntimeException(
                        "Payment amount (₱" . number_format($paymentAmount, 2) . ") exceeds remaining balance (₱" . number_format($currentBalance, 2) . ")."
                    );
                }

                $payment = LoanPayment::create([
                    'loan_id' => $lockedLoan->id,
                    'amount' => $paymentAmount,
                    'paid_at' => now(),
                    'received_by' => auth()->id(),
                ]);

                $newBalance = round($currentBalance - $paymentAmount, 2);
                $isPaid = $newBalance <= 0;

                $lockedLoan->update([
                    'balance' => $newBalance,
                    'status' => $isPaid ? 'PAID' : 'PARTIAL',
                    'settled_at' => $isPaid ? now() : null,
                ]);

                AuditLogger::log('LOAN_PAYMENT_RECORDED', 'LoanPayment', $payment->id);
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        return back()->with('status', 'Loan payment recorded successfully.');
    }
}