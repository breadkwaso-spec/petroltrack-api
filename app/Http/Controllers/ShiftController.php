<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function start(Request $request)
    {
        $user = $request->user();

        if ($user->activeShift) {
            return back()->withErrors(['shift' => 'You already have an active shift in progress.']);
        }

        $shift = Shift::create([
            'user_id' => $user->id,
            'started_at' => now(),
            'ended_at' => null,
        ]);

        AuditLogger::log('SHIFT_STARTED', 'Shift', $shift->id, $user->id);

        return back()->with('status', 'Shift started at ' . $shift->started_at->format('M d, Y h:i A') . '.');
    }

    public function end(Request $request)
    {
        $user = $request->user();
        $shift = $user->activeShift;

        if (!$shift) {
            return back()->withErrors(['shift' => 'No active shift found to end.']);
        }

        $shift->update(['ended_at' => now()]);
        AuditLogger::log('SHIFT_ENDED', 'Shift', $shift->id, $user->id);

        $validSales = $shift->sales()->where('status', 'Valid')->get();
        $summary = [
            'shift_id' => $shift->id,
            'started_at' => $shift->started_at->format('M d, Y h:i A'),
            'ended_at' => $shift->ended_at->format('M d, Y h:i A'),
            'total_sales_count' => $validSales->count(),
            'cash_total' => $validSales->where('payment_method', 'Cash')->sum('total_amount'),
            'card_total' => $validSales->where('payment_method', 'Card')->sum('total_amount'),
            'credit_issued' => $validSales->where('payment_method', 'Loan')->sum('total_amount'),
        ];

        return back()
            ->with('status', 'Shift ended successfully.')
            ->with('shift_summary', $summary);
    }
}