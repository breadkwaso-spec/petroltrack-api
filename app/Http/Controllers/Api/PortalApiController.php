<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Borrower;
use App\Models\FuelDelivery;
use App\Models\FuelGrade;
use App\Models\FuelPrice;
use App\Models\FuelSale;
use App\Models\FuelStock;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Pump;
use App\Models\Shift;
use App\Models\User;
use App\Models\VoidRequest;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PortalApiController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('employee_id', trim($validated['employee_id']))->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid employee ID or password.'], 422);
        }

        if ($user->status !== 'Active') {
            return response()->json(['message' => 'Account is ' . $user->status . ' and awaiting Owner approval.'], 403);
        }

        $plainToken = Str::random(60);
        Cache::put('api_token:' . hash('sha256', $plainToken), $user->id, now()->addHours(12));

        $activeShift = Shift::where('user_id', $user->id)->whereNull('ended_at')->latest('id')->first();

        return response()->json([
            'token' => $plainToken,
            'user' => [
                'id' => $user->id,
                'employee_id' => $user->employee_id,
                'full_name' => $user->full_name,
                'role' => $user->role,
                'status' => $user->status,
            ],
            'active_shift' => $activeShift,
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'string', 'max:50', 'unique:users,employee_id'],
            'full_name' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $user = User::create([
            'employee_id' => strtoupper(trim($validated['employee_id'])),
            'full_name' => trim($validated['full_name']),
            'password' => Hash::make($validated['password']),
            'role' => 'Attendant',
            'status' => 'Pending',
        ]);

        AuditLogger::log('ACCOUNT_REGISTERED', 'User', $user->id, $user->id);

        return response()->json([
            'message' => 'Registration submitted. Awaiting Station Owner approval.',
            'user' => $user->only(['id', 'employee_id', 'full_name', 'role', 'status']),
        ], 201);
    }

    public function portalState(Request $request)
    {
        $grades = FuelGrade::with(['stock', 'currentPrice'])
            ->where('active', true)
            ->get()
            ->map(function ($g) {
                $latestPrice = $g->currentPrice
                    ?? FuelPrice::where('grade_id', $g->id)->latest('effective_from')->latest('id')->first();

                return [
                    'id' => $g->id,
                    'name' => $g->name,
                    'price_per_liter' => $latestPrice ? (float) $latestPrice->price_per_liter : 0.0,
                    'liters_on_hand' => $g->stock ? (float) $g->stock->liters_on_hand : 0.0,
                    'low_stock_threshold' => $g->stock ? (float) $g->stock->low_stock_threshold : 0.0,
                ];
            });

        $pumps = Pump::where('active', true)->orderBy('pump_no')->get(['id', 'pump_no']);

        $borrowers = Borrower::with(['loans' => fn ($q) => $q->whereNotIn('status', ['PAID', 'VOIDED'])])
            ->orderBy('full_name')
            ->get()
            ->map(function ($b) {
                $openBalance = round((float) $b->loans->sum('balance'), 2);
                $creditLimit = (float) $b->credit_limit;
                return [
                    'id' => $b->id,
                    'full_name' => $b->full_name,
                    'plate_no' => $b->plate_no,
                    'contact_no' => $b->contact_no,
                    'credit_limit' => $creditLimit,
                    'open_balance' => $openBalance,
                    'available_credit' => round(max(0, $creditLimit - $openBalance), 2),
                ];
            });

        $sales = FuelSale::with(['grade', 'pump', 'user', 'loan.borrower'])
            ->latest('id')
            ->limit(30)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'entry_id' => $s->entry_id,
                'grade' => $s->grade?->name,
                'pump_no' => $s->pump?->pump_no,
                'attendant' => $s->user?->full_name,
                'liters' => (float) $s->liters,
                'unit_price' => (float) $s->unit_price,
                'total_amount' => (float) $s->total_amount,
                'payment_method' => $s->payment_method,
                'status' => $s->status,
                'source' => $s->source,
                'borrower' => $s->loan?->borrower?->full_name,
                'recorded_at' => optional($s->recorded_at)->toIso8601String(),
            ]);

        $loans = Loan::with(['borrower', 'sale.grade'])
            ->latest('id')
            ->limit(40)
            ->get()
            ->map(fn ($l) => [
                'id' => $l->id,
                'borrower_id' => $l->borrower_id,
                'borrower_name' => $l->borrower?->full_name,
                'plate_no' => $l->borrower?->plate_no,
                'grade' => $l->sale?->grade?->name,
                'liters' => $l->sale ? (float) $l->sale->liters : 0.0,
                'amount' => (float) $l->amount,
                'balance' => (float) $l->balance,
                'status' => $l->status,
                'issued_at' => optional($l->issued_at)->toIso8601String(),
            ]);

        $validSales = FuelSale::where('status', 'Valid');
        $analytics = [
            'today_revenue' => round((float) (clone $validSales)->whereDate('recorded_at', today())->sum('total_amount'), 2),
            'today_liters' => round((float) (clone $validSales)->whereDate('recorded_at', today())->sum('liters'), 2),
            'total_revenue' => round((float) (clone $validSales)->sum('total_amount'), 2),
            'total_liters' => round((float) (clone $validSales)->sum('liters'), 2),
            'by_payment' => [
                'Cash' => round((float) (clone $validSales)->where('payment_method', 'Cash')->sum('total_amount'), 2),
                'Card' => round((float) (clone $validSales)->where('payment_method', 'Card')->sum('total_amount'), 2),
                'Loan' => round((float) (clone $validSales)->where('payment_method', 'Loan')->sum('total_amount'), 2),
            ],
        ];

        $ownerData = [
            'pending_voids' => VoidRequest::with(['sale.grade', 'sale.pump', 'requester'])
                ->where('status', 'Pending')
                ->latest('id')
                ->get()
                ->map(fn ($v) => [
                    'id' => $v->id,
                    'fuel_sale_id' => $v->fuel_sale_id,
                    'grade' => $v->sale?->grade?->name,
                    'liters' => $v->sale ? (float) $v->sale->liters : 0,
                    'total_amount' => $v->sale ? (float) $v->sale->total_amount : 0,
                    'payment_method' => $v->sale?->payment_method,
                    'requested_by' => $v->requester?->full_name,
                    'reason' => $v->reason,
                    'status' => $v->status,
                ]),
            'pending_accounts' => User::where('status', 'Pending')
                ->latest('id')
                ->get(['id', 'employee_id', 'full_name', 'role', 'status', 'created_at']),
            'audit_logs' => AuditLog::with('user')
                ->latest('id')
                ->limit(25)
                ->get()
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'user' => $a->user?->full_name ?? 'System',
                    'action' => $a->action,
                    'entity' => $a->entity,
                    'entity_id' => $a->entity_id,
                    'logged_at' => (string) $a->logged_at,
                ]),
        ];

        return response()->json([
            'grades' => $grades,
            'pumps' => $pumps,
            'borrowers' => $borrowers,
            'sales' => $sales,
            'loans' => $loans,
            'analytics' => $analytics,
            'owner' => $ownerData,
        ]);
    }

    public function startShift(Request $request)
    {
        $user = $request->user();
        $existing = Shift::where('user_id', $user->id)->whereNull('ended_at')->first();
        if ($existing) {
            return response()->json(['shift' => $existing, 'message' => 'Shift already active.']);
        }

        $shift = Shift::create([
            'user_id' => $user->id,
            'started_at' => now(),
        ]);

        AuditLogger::log('SHIFT_STARTED', 'Shift', $shift->id, $user->id);

        return response()->json(['shift' => $shift, 'message' => 'Shift started.']);
    }

    public function endShift(Request $request)
    {
        $user = $request->user();
        $shift = Shift::where('user_id', $user->id)->whereNull('ended_at')->latest('id')->first();
        if (!$shift) {
            return response()->json(['message' => 'No active shift found.'], 422);
        }

        $shift->update(['ended_at' => now()]);
        AuditLogger::log('SHIFT_ENDED', 'Shift', $shift->id, $user->id);

        return response()->json(['shift' => $shift, 'message' => 'Shift ended.']);
    }

    public function storeSale(Request $request)
    {
        $validated = $request->validate([
            'pump_id' => ['required', 'exists:pumps,id'],
            'grade_id' => ['required', 'exists:fuel_grades,id'],
            'liters' => ['required', 'numeric', 'min:0.1', 'max:5000'],
            'payment_method' => ['required', 'in:Cash,Card,Loan'],
            'source' => ['nullable', 'string', 'max:50'],
            'borrower_id' => ['required_if:payment_method,Loan', 'nullable', 'exists:borrowers,id'],
        ]);

        $user = $request->user();
        $shift = Shift::where('user_id', $user->id)->whereNull('ended_at')->latest('id')->first();
        if (!$shift) {
            $shift = Shift::create(['user_id' => $user->id, 'started_at' => now()]);
        }

        try {
            $sale = DB::transaction(function () use ($validated, $user, $shift) {
                $priceRow = FuelPrice::where('grade_id', $validated['grade_id'])
                    ->latest('effective_from')
                    ->latest('id')
                    ->firstOrFail();

                $stock = FuelStock::where('grade_id', $validated['grade_id'])->lockForUpdate()->firstOrFail();
                $liters = round((float) $validated['liters'], 2);

                if ((float) $stock->liters_on_hand < $liters) {
                    throw new \RuntimeException('Insufficient fuel stock on hand.');
                }

                $unitPrice = (float) $priceRow->price_per_liter;
                $totalAmount = round($liters * $unitPrice, 2);

                if ($validated['payment_method'] === 'Loan') {
                    $borrower = Borrower::where('id', $validated['borrower_id'])->lockForUpdate()->firstOrFail();
                    $openBalance = (float) Loan::where('borrower_id', $borrower->id)
                        ->whereNotIn('status', ['PAID', 'VOIDED'])
                        ->sum('balance');

                    if (($openBalance + $totalAmount) > (float) $borrower->credit_limit) {
                        throw new \RuntimeException('Credit limit exceeded for ' . $borrower->full_name . '.');
                    }
                }

                $sale = FuelSale::create([
                    'entry_id' => (string) Str::uuid(),
                    'shift_id' => $shift->id,
                    'user_id' => $user->id,
                    'pump_id' => $validated['pump_id'],
                    'grade_id' => $validated['grade_id'],
                    'liters' => $liters,
                    'unit_price' => $unitPrice,
                    'total_amount' => $totalAmount,
                    'payment_method' => $validated['payment_method'],
                    'status' => 'Valid',
                    'recorded_at' => now(),
                    'synced_at' => now(),
                    'source' => strtolower((string) ($validated['source'] ?? '')) === 'offline' ? 'Offline' : 'Online',
                ]);

                $stock->update([
                    'liters_on_hand' => round((float) $stock->liters_on_hand - $liters, 2),
                ]);

                if ($validated['payment_method'] === 'Loan') {
                    Loan::create([
                        'borrower_id' => $validated['borrower_id'],
                        'fuel_sale_id' => $sale->id,
                        'amount' => $totalAmount,
                        'balance' => $totalAmount,
                        'status' => 'UNPAID',
                        'issued_at' => now(),
                    ]);
                }

                AuditLogger::log('SALE_RECORDED', 'FuelSale', $sale->id, $user->id);

                return $sale;
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Fuel sale recorded.', 'sale' => $sale], 201);
    }

    public function requestVoid(Request $request, FuelSale $sale)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        if ($sale->status !== 'Valid') {
            return response()->json(['message' => 'Only Valid sales can be requested for voiding.'], 422);
        }

        if (VoidRequest::where('fuel_sale_id', $sale->id)->where('status', 'Pending')->exists()) {
            return response()->json(['message' => 'A pending void request already exists for this sale.'], 422);
        }

        $vr = VoidRequest::create([
            'fuel_sale_id' => $sale->id,
            'requested_by' => $request->user()->id,
            'reason' => trim($validated['reason']),
            'status' => 'Pending',
        ]);

        AuditLogger::log('VOID_REQUESTED', 'VoidRequest', $vr->id, $request->user()->id);

        return response()->json(['message' => 'Void request submitted for Owner review.', 'void_request' => $vr], 201);
    }

    public function storeBorrower(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'plate_no' => ['required', 'string', 'max:30'],
            'contact_no' => ['nullable', 'string', 'max:30'],
            'credit_limit' => ['required', 'numeric', 'min:0'],
        ]);

        $borrower = Borrower::create([
            'full_name' => trim($validated['full_name']),
            'plate_no' => strtoupper(trim($validated['plate_no'])),
            'contact_no' => $validated['contact_no'] ?? null,
            'credit_limit' => round((float) $validated['credit_limit'], 2),
        ]);

        AuditLogger::log('BORROWER_CREATED', 'Borrower', $borrower->id, $request->user()->id);

        return response()->json(['message' => 'Borrower saved.', 'borrower' => $borrower], 201);
    }

    public function recordPayment(Request $request, Loan $loan)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        if (in_array($loan->status, ['PAID', 'VOIDED'], true) || (float) $loan->balance <= 0) {
            return response()->json(['message' => 'This loan is already settled or voided.'], 422);
        }

        $payAmount = round((float) $validated['amount'], 2);
        if ($payAmount > (float) $loan->balance) {
            return response()->json(['message' => 'Payment exceeds remaining loan balance.'], 422);
        }

        DB::transaction(function () use ($loan, $payAmount, $request) {
            $lockedLoan = Loan::where('id', $loan->id)->lockForUpdate()->firstOrFail();
            $newBalance = round((float) $lockedLoan->balance - $payAmount, 2);

            LoanPayment::create([
                'loan_id' => $lockedLoan->id,
                'received_by' => $request->user()->id,
                'amount' => $payAmount,
                'paid_at' => now(),
            ]);

            $lockedLoan->update([
                'balance' => max(0, $newBalance),
                'status' => $newBalance <= 0 ? 'PAID' : 'PARTIAL',
                'settled_at' => $newBalance <= 0 ? now() : null,
            ]);

            AuditLogger::log('LOAN_PAYMENT_RECORDED', 'Loan', $lockedLoan->id, $request->user()->id);
        });

        return response()->json(['message' => 'Loan payment recorded.']);
    }

    public function approveVoid(Request $request, VoidRequest $voidRequest)
    {
        if ($request->user()->role !== 'Owner') {
            return response()->json(['message' => 'Owner access required.'], 403);
        }

        try {
            DB::transaction(function () use ($request, $voidRequest) {
                $lockedRequest = VoidRequest::where('id', $voidRequest->id)->lockForUpdate()->firstOrFail();
                if ($lockedRequest->status !== 'Pending') {
                    throw new \RuntimeException('Void request already reviewed.');
                }

                $sale = FuelSale::where('id', $lockedRequest->fuel_sale_id)->lockForUpdate()->firstOrFail();
                $loan = Loan::withCount('payments')->where('fuel_sale_id', $sale->id)->lockForUpdate()->first();

                if ($loan && ((int) $loan->payments_count > 0 || $loan->status === 'PAID')) {
                    throw new \RuntimeException('Cannot void a loan sale that has already been paid or settled.');
                }

                $sale->update(['status' => 'Voided']);

                $stock = FuelStock::where('grade_id', $sale->grade_id)->lockForUpdate()->first();
                if ($stock) {
                    $stock->update([
                        'liters_on_hand' => round((float) $stock->liters_on_hand + (float) $sale->liters, 2),
                    ]);
                }

                if ($loan) {
                    $loan->update(['balance' => 0.00, 'status' => 'VOIDED']);
                }

                $lockedRequest->update([
                    'status' => 'Approved',
                    'decided_by' => $request->user()->id,
                    'decided_at' => now(),
                ]);

                AuditLogger::log('VOID_APPROVED', 'VoidRequest', $lockedRequest->id, $request->user()->id);
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Void approved and stock restored.']);
    }

    public function rejectVoid(Request $request, VoidRequest $voidRequest)
    {
        if ($request->user()->role !== 'Owner') {
            return response()->json(['message' => 'Owner access required.'], 403);
        }

        if ($voidRequest->status !== 'Pending') {
            return response()->json(['message' => 'Void request already reviewed.'], 422);
        }

        $voidRequest->update([
            'status' => 'Rejected',
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
        ]);

        AuditLogger::log('VOID_REJECTED', 'VoidRequest', $voidRequest->id, $request->user()->id);

        return response()->json(['message' => 'Void request rejected.']);
    }

    public function updatePrice(Request $request)
    {
        if ($request->user()->role !== 'Owner') {
            return response()->json(['message' => 'Owner access required.'], 403);
        }

        $validated = $request->validate([
            'grade_id' => ['required', 'exists:fuel_grades,id'],
            'price_per_liter' => ['required', 'numeric', 'min:1', 'max:999'],
        ]);

        $price = FuelPrice::create([
            'grade_id' => $validated['grade_id'],
            'price_per_liter' => round((float) $validated['price_per_liter'], 2),
            'effective_from' => now(),
            'set_by' => $request->user()->id,
        ]);

        AuditLogger::log('PRICE_UPDATED', 'FuelPrice', $price->id, $request->user()->id);

        return response()->json(['message' => 'Fuel price updated.', 'price' => $price]);
    }

    public function storeDelivery(Request $request)
    {
        if ($request->user()->role !== 'Owner') {
            return response()->json(['message' => 'Owner access required.'], 403);
        }

        $validated = $request->validate([
            'grade_id' => ['required', 'exists:fuel_grades,id'],
            'liters' => ['required', 'numeric', 'min:1', 'max:100000'],
            'cost_per_liter' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'supplier' => ['nullable', 'string', 'max:120'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            $stock = FuelStock::where('grade_id', $validated['grade_id'])->lockForUpdate()->firstOrFail();
            $liters = round((float) $validated['liters'], 2);
            $stock->update([
                'liters_on_hand' => round((float) $stock->liters_on_hand + $liters, 2),
            ]);

            if (class_exists(FuelDelivery::class)) {
                FuelDelivery::create([
                    'grade_id' => $validated['grade_id'],
                    'liters' => $liters,
                    'cost_per_liter' => isset($validated['cost_per_liter']) ? round((float) $validated['cost_per_liter'], 2) : 0.00,
                    'supplier' => $validated['supplier'] ?? 'Station Supplier',
                    'recorded_by' => $request->user()->id,
                    'delivered_at' => now(),
                ]);
            }

            AuditLogger::log('DELIVERY_RECORDED', 'FuelStock', $validated['grade_id'], $request->user()->id);
        });

        return response()->json(['message' => 'Fuel delivery added to stock.']);
    }

    public function approveAccount(Request $request, User $user)
    {
        if ($request->user()->role !== 'Owner') {
            return response()->json(['message' => 'Owner access required.'], 403);
        }

        $role = in_array($request->input('role'), ['Attendant', 'Owner'], true)
            ? $request->input('role')
            : ($user->role ?: 'Attendant');

        $user->update([
            'role' => $role,
            'status' => 'Active',
            'approved_by' => $request->user()->id,
        ]);

        AuditLogger::log('ACCOUNT_APPROVED', 'User', $user->id, $request->user()->id);

        return response()->json(['message' => 'Account approved.']);
    }

    public function rejectAccount(Request $request, User $user)
    {
        if ($request->user()->role !== 'Owner') {
            return response()->json(['message' => 'Owner access required.'], 403);
        }

        $id = $user->id;
        $user->delete();

        AuditLogger::log('ACCOUNT_REJECTED', 'User', $id, $request->user()->id);

        return response()->json(['message' => 'Pending account rejected and removed.']);
    }

    public function destroyBorrower(Request $request, Borrower $borrower)
    {
        $openBalance = round((float) Loan::where('borrower_id', $borrower->id)
            ->whereNotIn('status', ['PAID', 'VOIDED'])
            ->sum('balance'), 2);

        if ($openBalance > 0) {
            return response()->json([
                'message' => 'Cannot remove ' . $borrower->full_name . ' while an unpaid balance of ₱' . number_format($openBalance, 2) . ' remains.',
            ], 422);
        }

        $borrower->delete();
        AuditLogger::log('BORROWER_DELETED', 'Borrower', $borrower->id, $request->user()->id);

        return response()->json(['message' => 'Borrower removed.']);
    }
}