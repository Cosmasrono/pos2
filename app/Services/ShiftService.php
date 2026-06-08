<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\Sale;
use App\Models\CartItem;

class ShiftService
{
    public function openShift(array $data): Shift
    {
        // Close any existing open shifts for this cashier
        Shift::where('cashier_id', $data['cashier_id'])
            ->where('status', 'open')
            ->update(['status' => 'closed']);

        return Shift::create([
            'cashier_id' => $data['cashier_id'],
            'opened_at' => now(),
            'opened_by' => $data['opened_by'],
            'opening_cash' => $data['opening_cash'],
            'opening_notes' => $data['opening_notes'] ?? null,
            'status' => 'open',
        ]);
    }

    public function closeShift(Shift $shift, array $data): void
    {
        // Calculate sales during shift
        $sales = Sale::where('shift_id', $shift->id)
            ->where('status', 'completed')
            ->get();

        $totalCashSales = $sales->sum('cash_paid');
        $totalMpesaSales = $sales->sum('mpesa_paid');
        $totalCardSales = $sales->sum('card_paid');

        // Handle refunds
        $totalRefunds = Sale::where('shift_id', $shift->id)
            ->where('status', 'refunded')
            ->sum('cash_paid');

        $expectedClosing = $shift->opening_cash + $totalCashSales - $totalRefunds;
        $shortage = $expectedClosing - $data['closing_cash_counted'];

        $shift->update([
            'closed_at' => now(),
            'closed_by' => $data['closed_by'],
            'closing_cash_counted' => $data['closing_cash_counted'],
            'closing_notes' => $data['closing_notes'] ?? null,
            'total_cash_sales' => $totalCashSales,
            'total_mpesa_sales' => $totalMpesaSales,
            'total_card_sales' => $totalCardSales,
            'total_refunds' => $totalRefunds,
            'expected_closing_cash' => $expectedClosing,
            'cash_shortage_overage' => $shortage,
            'status' => abs($shortage) > 0 ? 'discrepancy' : 'closed',
        ]);

        // Clear the cart for this cashier
        CartItem::where('user_id', $shift->cashier_id)->delete();
    }

    public function getOpenShift(): ?Shift
    {
        return Shift::where('cashier_id', auth()->id())
            ->where('status', 'open')
            ->first();
    }

    public function getShiftSummary(Shift $shift): array
    {
        return [
            'opening_cash' => $shift->opening_cash,
            'total_cash_sales' => $shift->total_cash_sales,
            'total_mpesa_sales' => $shift->total_mpesa_sales,
            'total_card_sales' => $shift->total_card_sales,
            'total_refunds' => $shift->total_refunds,
            'expected_closing' => $shift->expected_closing_cash,
            'actual_closing' => $shift->closing_cash_counted,
            'shortage_overage' => $shift->cash_shortage_overage,
            'transaction_count' => $shift->sales()->where('status', 'completed')->count(),
        ];
    }
}
