<?php

namespace App\Http\Controllers;

use App\Models\FuelSale;
use App\Models\FuelStock;
use App\Models\Loan;
use App\Models\VoidRequest;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoidRequestController extends Controller
{
    public function index()
    {
        $pendingRequests = VoidRequest::with(['sale.grade', 'sale.pump', 'requester'])
            ->where('status', 'Pending')
            ->latest('id')
            ->get();

        $reviewedRequests = VoidRequest::with(['sale.grade', 'sale.pump', 'requester'])
            ->where('status', '!=', 'Pending')
            ->latest('id')
            ->limit(25)
            ->get();

        return view('owner.voids', compact('pendingRequests', 'reviewedRequests'));
    }

    public function store(Request $request, FuelSale $sale)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        if ($sale->status !== 'Valid') {
            return back()->withErrors(['void' => 'Only Valid fuel sales can be requested for voiding.']);
        }

        $existingPending = VoidRequest::where('fuel_sale_id', $sale->id)
            ->where('status', 'Pending')
            ->exists();

        if ($existingPending) {
            return back()->withErrors(['void' => 'A pending void request already exists for this sale.']);
        }

        $voidRequest = VoidRequest::create([
            'fuel_sale_id' => $sale->id,
            'requested_by' => $request->user()->id,
            'reason' => trim($validated['reason']),
            'status' => 'Pending',
        ]);

        AuditLogger::log('VOID_REQUESTED', 'VoidRequest', $voidRequest->id, $request->user()->id);

        return back()->with('status', 'Void request submitted to the Station Owner for review.');
    }

    public function approve(Request $request, VoidRequest $voidRequest)
    {
        if ($voidRequest->status !== 'Pending') {
            return back()->withErrors(['void' => 'This void request has already been reviewed.']);
        }

        try {
            DB::transaction(function () use ($request, $voidRequest) {
                $lockedRequest = VoidRequest::where('id', $voidRequest->id)->lockForUpdate()->firstOrFail();
                $sale = FuelSale::where('id', $lockedRequest->fuel_sale_id)->lockForUpdate()->firstOrFail();

                if ($sale->status !== 'Valid') {
                    throw new \RuntimeException('Sale is no longer Valid.');
                }

                $loan = Loan::withCount('payments')->where('fuel_sale_id', $sale->id)->lockForUpdate()->first();
                if ($loan && ((int) $loan->payments_count > 0 || $loan->status === 'PAID')) {
                    throw new \RuntimeException('Cannot void a loan sale that has already been paid or settled.');
                }

                $sale->update([
                    'status' => 'Voided',
                ]);

                $stock = FuelStock::where('grade_id', $sale->grade_id)->lockForUpdate()->first();
                if ($stock) {
                    $stock->update([
                        'liters_on_hand' => round((float) $stock->liters_on_hand + (float) $sale->liters, 2),
                    ]);
                }

                if ($loan) {
                    $loan->update([
                        'balance' => 0.00,
                        'status' => 'VOIDED',
                    ]);
                }

                $lockedRequest->update([
                    'status' => 'Approved',
                ]);

                AuditLogger::log('VOID_APPROVED', 'VoidRequest', $lockedRequest->id, $request->user()->id);
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['void' => $e->getMessage()]);
        }

        return back()->with('status', 'Void request approved. Fuel stock has been restored.');
    }

    public function reject(Request $request, VoidRequest $voidRequest)
    {
        if ($voidRequest->status !== 'Pending') {
            return back()->withErrors(['void' => 'This void request has already been reviewed.']);
        }

        $voidRequest->update([
            'status' => 'Rejected',
        ]);

        AuditLogger::log('VOID_REJECTED', 'VoidRequest', $voidRequest->id, $request->user()->id);

        return back()->with('status', 'Void request rejected. Sale remains Valid.');
    }
}