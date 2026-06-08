<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Services\ShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    protected $shiftService;

    public function __construct(ShiftService $shiftService)
    {
        $this->shiftService = $shiftService;
    }

    public function openShift(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'opening_cash' => 'required|numeric|min:0',
            'opening_notes' => 'nullable|string',
        ]);

        // Check if already open
        $existingShift = $this->shiftService->getOpenShift();
        if ($existingShift) {
            return response()->json(['error' => 'You already have an open shift'], 400);
        }

        $shift = $this->shiftService->openShift([
            'cashier_id' => auth()->id(),
            'opening_cash' => $validated['opening_cash'],
            'opening_notes' => $validated['opening_notes'] ?? null,
            'opened_by' => auth()->id(),
        ]);

        return response()->json($shift, 201);
    }

    public function closeShift(Request $request): JsonResponse
    {
        $shift = $this->shiftService->getOpenShift();

        if (!$shift) {
            return response()->json(['error' => 'No open shift found'], 404);
        }

        $validated = $request->validate([
            'closing_cash_counted' => 'required|numeric|min:0',
            'closing_notes' => 'nullable|string',
        ]);

        $this->shiftService->closeShift($shift, [
            'closing_cash_counted' => $validated['closing_cash_counted'],
            'closing_notes' => $validated['closing_notes'] ?? null,
            'closed_by' => auth()->id(),
        ]);

        return response()->json($shift);
    }

    public function currentShift(): JsonResponse
    {
        $shift = $this->shiftService->getOpenShift();

        if (!$shift) {
            return response()->json(['error' => 'No open shift'], 404);
        }

        return response()->json($shift);
    }

    public function summary(Shift $shift): JsonResponse
    {
        $summary = $this->shiftService->getShiftSummary($shift);
        return response()->json($summary);
    }

    public function shiftHistory(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date') ?? now()->startOfMonth();
        $endDate = $request->get('end_date') ?? now();

        $shifts = Shift::where('cashier_id', auth()->id())
            ->whereBetween('opened_at', [$startDate, $endDate])
            ->orderBy('opened_at', 'desc')
            ->paginate(20);

        return response()->json($shifts);
    }
}
