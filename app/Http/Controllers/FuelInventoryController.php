<?php

namespace App\Http\Controllers;

use App\Models\FuelDelivery;
use App\Models\FuelGrade;
use App\Models\FuelPrice;
use App\Models\FuelStock;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FuelInventoryController extends Controller
{
    public function index()
    {
        $grades = FuelGrade::with(['currentPrice', 'stock'])
            ->orderBy('name')
            ->get();

        $recentDeliveries = FuelDelivery::with(['grade', 'recorder'])
            ->latest('delivered_at')
            ->limit(15)
            ->get();

        $recentPrices = FuelPrice::with(['grade', 'setter'])
            ->latest('effective_from')
            ->limit(15)
            ->get();

        return view('owner.inventory', compact('grades', 'recentDeliveries', 'recentPrices'));
    }

    public function updatePrice(Request $request)
    {
        $validated = $request->validate([
            'grade_id' => ['required', 'exists:fuel_grades,id'],
            'price_per_liter' => ['required', 'numeric', 'gt:0', 'max:999999.99'],
            'effective_from' => ['nullable', 'date'],
        ]);

        $price = FuelPrice::create([
            'grade_id' => $validated['grade_id'],
            'price_per_liter' => round((float) $validated['price_per_liter'], 2),
            'effective_from' => $validated['effective_from'] ?? now(),
            'set_by' => auth()->id(),
        ]);

        AuditLogger::log('FUEL_PRICE_UPDATED', 'FuelPrice', $price->id);

        return back()->with('status', 'Updated fuel price to ₱' . number_format($price->price_per_liter, 2) . '/L.');
    }

    public function storeDelivery(Request $request)
    {
        $validated = $request->validate([
            'grade_id' => ['required', 'exists:fuel_grades,id'],
            'liters' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'cost_per_liter' => ['required', 'numeric', 'gte:0', 'max:999999.99'],
            'supplier' => ['required', 'string', 'max:150'],
            'delivered_at' => ['nullable', 'date'],
        ]);

        $delivery = DB::transaction(function () use ($validated) {
            $liters = round((float) $validated['liters'], 2);
            $costPerLiter = round((float) $validated['cost_per_liter'], 2);
            $deliveredAt = $validated['delivered_at'] ?? now();

            $delivery = FuelDelivery::create([
                'grade_id' => $validated['grade_id'],
                'liters' => $liters,
                'cost_per_liter' => $costPerLiter,
                'supplier' => trim($validated['supplier']),
                'delivered_at' => $deliveredAt,
                'recorded_by' => auth()->id(),
            ]);

            $stock = FuelStock::where('grade_id', $validated['grade_id'])->lockForUpdate()->first();
            if ($stock) {
                $stock->update([
                    'liters_on_hand' => round((float) $stock->liters_on_hand + $liters, 2),
                ]);
            } else {
                FuelStock::create([
                    'grade_id' => $validated['grade_id'],
                    'liters_on_hand' => $liters,
                    'low_stock_threshold' => 500.00,
                ]);
            }

            AuditLogger::log('FUEL_DELIVERY_RECORDED', 'FuelDelivery', $delivery->id);

            return $delivery;
        });

        return back()->with('status', "Recorded delivery of {$delivery->liters} L from {$delivery->supplier}.");
    }
}