<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\CartItem;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    /**
     * Open a new shift
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'opening_cash' => 'required|numeric|min:0',
            'opening_notes' => 'nullable|string|max:1000'
        ]);

        // Check if user has an active shift
        $activeShift = auth()->user()->shifts()
            ->where('status', 'open')
            ->first();
        
        if ($activeShift) {
            return back()->with('error', 'You already have an active shift. Close it first.');
        }

        // Determine branch: use user's branch if they have one, otherwise use main branch
        $branchId = auth()->user()->branch_id;
        if (!$branchId) {
            $mainBranch = \App\Models\Branch::where('is_main', true)->first();
            $branchId = $mainBranch?->id;
        }

        $shift = Shift::create([
            'cashier_id' => auth()->id(),
            'branch_id' => $branchId,
            'opened_by' => auth()->id(),
            'opening_cash' => $validated['opening_cash'],
            'opening_notes' => $validated['opening_notes'] ?? null,
            'opened_at' => now(),
            'status' => 'open'
        ]);

        return back()->with('success', 'Shift opened successfully with opening balance: KES ' . number_format($validated['opening_cash'], 2));
    }

    /**
     * Close the current shift
     */
    public function close(Request $request)
    {
        $shift = auth()->user()->shifts()
            ->where('status', 'open')
            ->first();

        if (!$shift) {
            return back()->with('error', 'No active shift to close.');
        }

        $validated = $request->validate([
            'closing_cash' => 'required|numeric|min:0',
            'closing_notes' => 'nullable|string|max:1000'
        ]);

        // Calculate shift totals from sales
        $cashSales = $shift->sales()
            ->where('primary_payment_method', 'cash')
            ->sum('total_amount');
        $mpesaSales = $shift->sales()
            ->where('primary_payment_method', 'mpesa')
            ->sum('total_amount');
        $cardSales = $shift->sales()
            ->where('primary_payment_method', 'card')
            ->sum('total_amount');

        $expectedClosing = $shift->opening_cash + $cashSales;

        $shift->update([
            'closing_cash_counted' => $validated['closing_cash'],
            'closing_notes' => $validated['closing_notes'] ?? null,
            'closed_at' => now(),
            'closed_by' => auth()->id(),
            'total_cash_sales' => $cashSales,
            'total_mpesa_sales' => $mpesaSales,
            'total_card_sales' => $cardSales,
            'expected_closing_cash' => $expectedClosing,
            'cash_shortage_overage' => $validated['closing_cash'] - $expectedClosing,
            'status' => 'closed'
        ]);

        // Clear the cart for this cashier
        CartItem::where('user_id', auth()->id())->delete();

        $variance = $validated['closing_cash'] - $expectedClosing;

        return back()->with('success', 'Shift closed. Variance: KES ' . number_format($variance, 2) . ' (' . ($variance >= 0 ? 'Over' : 'Short') . ')');
    }

    /**
     * Get active shift for current user
     */
    public function getActive()
    {
        $shift = auth()->user()->shifts()
            ->where('status', 'open')
            ->first();

        return response()->json([
            'has_active_shift' => $shift !== null,
            'shift' => $shift
        ]);
    }
}
