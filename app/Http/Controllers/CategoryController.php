<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    /**
     * GET /api/categories or /categories
     * Returns all distinct medicine categories
     */
    public function index()
    {
        $categories = Medicine::select('category')
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return response()->json([
            'success'    => true,
            'categories' => $categories,
        ]);
    }

    /**
     * GET /api/categories/{category}/medicines
     * Returns medicines in a specific category
     */
    public function medicines(string $category)
    {
        $medicines = Medicine::where('category', $category)
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success'   => true,
            'category'  => $category,
            'medicines' => $medicines,
        ]);
    }

    /**
     * GET /api/categories/stats
     * Returns categories with counts and stock totals
     */
    public function stats()
    {
        $stats = Medicine::select(
                'category',
                DB::raw('count(*) as total_products'),
                DB::raw('sum(stock) as total_stock')
            )
            ->whereNotNull('category')
            ->groupBy('category')
            ->orderBy('category')
            ->get();

        return response()->json([
            'success' => true,
            'stats'   => $stats,
        ]);
    }
}