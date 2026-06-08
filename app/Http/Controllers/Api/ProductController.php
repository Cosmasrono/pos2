<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index(): JsonResponse
    {
        $products = Product::with('category')
            ->where('is_active', true)
            ->get()
            ->map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->sku,
                    'barcode' => $product->barcode,
                    'price' => (float) $product->selling_price,
                    'stock' => (int) $product->quantity_in_stock,
                    'category_id' => $product->category_id,
                    'description' => $product->description,
                    'cost_price' => (float) $product->cost_price,
                ];
            });

        return response()->json(['data' => $products], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'sku' => 'required|unique:products',
            'barcode' => 'nullable|unique:products',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'quantity_in_stock' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
        ]);

        $product = $this->inventoryService->addProduct($validated);

        return response()->json($product, 201);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load('category', 'stockMovements');
        return response()->json($product);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string',
            'cost_price' => 'numeric|min:0',
            'selling_price' => 'numeric|min:0',
            'reorder_level' => 'integer|min:0',
            'category_id' => 'exists:categories,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $product->update($validated);

        return response()->json($product);
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $query = trim($query);
        
        if (empty($query) || strlen($query) < 1) {
            return response()->json(['data' => []], 200);
        }
        
        try {
            $results = Product::where('is_active', true)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('sku', 'like', "%{$query}%")
                      ->orWhere('barcode', 'like', "%{$query}%");
                })
                ->take(15)
                ->get()
                ->map(function($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'code' => $product->sku,
                        'barcode' => $product->barcode,
                        'price' => (float) $product->selling_price,
                        'stock' => $product->quantity_in_stock,
                        'category_id' => $product->category_id,
                    ];
                });
                
            return response()->json(['data' => $results], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Search failed', 'message' => $e->getMessage()], 500);
        }
    }

    public function byBarcode(Request $request): JsonResponse
    {
        $product = $this->inventoryService->getProductByBarcode($request->get('barcode'));

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return response()->json($product);
    }

    public function lowStock(): JsonResponse
    {
        $products = $this->inventoryService->getLowStockProducts();
        return response()->json($products);
    }

    public function stockValue(): JsonResponse
    {
        $value = $this->inventoryService->getStockValue();
        return response()->json(['total_stock_value' => $value]);
    }
}
