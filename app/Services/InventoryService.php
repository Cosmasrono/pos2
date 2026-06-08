<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockTake;
use App\Models\StockTakeItem;

class InventoryService
{
    public function addProduct(array $data): Product
    {
        $product = Product::create($data);

        // Record initial stock movement
        if ($data['quantity_in_stock'] > 0) {
            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'purchase',
                'quantity' => $data['quantity_in_stock'],
                'notes' => 'Initial stock',
                'user_id' => auth()->id(),
            ]);
        }

        return $product;
    }

    public function adjustStock(Product $product, int $quantity, string $type, string $notes = ''): void
    {
        $product->quantity_in_stock += $quantity;
        $product->save();

        StockMovement::create([
            'product_id' => $product->id,
            'type' => $type,
            'quantity' => $quantity,
            'notes' => $notes,
            'user_id' => auth()->id(),
        ]);
    }

    public function getLowStockProducts(): array
    {
        return Product::whereRaw('quantity_in_stock <= reorder_level')
            ->where('is_active', true)
            ->get()
            ->toArray();
    }

    public function getStockValue(): float
    {
        return Product::where('is_active', true)
            ->selectRaw('SUM(quantity_in_stock * cost_price) as total_value')
            ->first()
            ->total_value ?? 0;
    }

    public function startStockTake(): StockTake
    {
        $stockTake = StockTake::create([
            'started_at' => now(),
            'created_by' => auth()->id(),
        ]);

        // Create items for all active products
        foreach (Product::where('is_active', true)->get() as $product) {
            StockTakeItem::create([
                'stock_take_id' => $stockTake->id,
                'product_id' => $product->id,
                'system_quantity' => $product->quantity_in_stock,
                'counted_quantity' => 0,
            ]);
        }

        return $stockTake;
    }

    public function completeStockTake(StockTake $stockTake): void
    {
        foreach ($stockTake->items as $item) {
            $difference = $item->counted_quantity - $item->system_quantity;

            if ($difference !== 0) {
                $product = $item->product;
                $product->quantity_in_stock = $item->counted_quantity;
                $product->save();

                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'adjustment',
                    'quantity' => $difference,
                    'notes' => "Stock take adjustment - Counted: {$item->counted_quantity}, System: {$item->system_quantity}",
                    'user_id' => auth()->id(),
                ]);
            }
        }

        $stockTake->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => auth()->id(),
        ]);
    }

    public function getProductsByCategory(int $categoryId): array
    {
        return Product::where('category_id', $categoryId)
            ->where('is_active', true)
            ->get()
            ->toArray();
    }

    public function searchProduct(string $query): array
    {
        return Product::where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('sku', 'like', "%{$query}%")
                  ->orWhere('barcode', 'like', "%{$query}%");
            })
            ->get()
            ->toArray();
    }

    public function getProductByBarcode(string $barcode): ?Product
    {
        return Product::where('barcode', $barcode)
            ->where('is_active', true)
            ->first();
    }

    public function getStockMovementHistory(Product $product, int $days = 30): array
    {
        return $product->stockMovements()
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }
}
