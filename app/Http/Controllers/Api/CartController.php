<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    /**
     * Get the identifier for the current user's cart (auth user ID or session ID).
     */
    protected function getCartIdentifier(): array
    {
        // Ensure session is started
        if (!session()->isStarted()) {
            session()->start();
        }

        if (Auth::check()) {
            Log::info('Cart: Using authenticated user', ['user_id' => Auth::id()]);
            return ['user_id' => Auth::id()];
        }
        
        $sessionId = session()->getId();
        Log::info('Cart: Using guest session', ['session_id' => $sessionId]);
        
        return ['session_id' => $sessionId];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $identifier = $this->getCartIdentifier();
        
        $items = CartItem::with('product')
            ->where(function($query) use ($identifier) {
                if (isset($identifier['user_id'])) {
                    $query->where('user_id', $identifier['user_id']);
                } else {
                    $query->where('session_id', $identifier['session_id']);
                }
            })
            ->get();

        Log::info('Cart items retrieved', [
            'count' => $items->count(),
            'identifier' => $identifier
        ]);

        return response()->json($items->map(function ($item) {
            return [
                'id' => $item->product_id,
                'cart_item_id' => $item->id,
                'name' => $item->product->name,
                'price' => (float)$item->product->selling_price,
                'quantity' => $item->quantity,
                'stock' => $item->product->quantity_in_stock,
            ];
        }));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
            ]);

            $identifier = $this->getCartIdentifier();
            
            Log::info('Adding to cart', [
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
                'identifier' => $identifier
            ]);
            
            // Check if item already exists in cart
            $cartItem = CartItem::where(function($query) use ($identifier) {
                    if (isset($identifier['user_id'])) {
                        $query->where('user_id', $identifier['user_id']);
                    } else {
                        $query->where('session_id', $identifier['session_id']);
                    }
                })
                ->where('product_id', $validated['product_id'])
                ->first();

            if ($cartItem) {
                // Add to existing quantity instead of replacing it
                $cartItem->update([
                    'quantity' => $cartItem->quantity + $validated['quantity']
                ]);
                Log::info('Updated existing cart item', ['cart_item_id' => $cartItem->id]);
            } else {
                // Create new cart item
                $cartItem = CartItem::create(
                    array_merge($identifier, [
                        'product_id' => $validated['product_id'], 
                        'quantity' => $validated['quantity']
                    ])
                );
                Log::info('Created new cart item', ['cart_item_id' => $cartItem->id]);
            }

            // Load the product relationship
            $cartItem->load('product');

            // Return formatted response to match index() format
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $cartItem->product_id,
                    'cart_item_id' => $cartItem->id,
                    'name' => $cartItem->product->name,
                    'price' => (float)$cartItem->product->selling_price,
                    'quantity' => $cartItem->quantity,
                    'stock' => $cartItem->product->quantity_in_stock,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error adding to cart', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add item to cart: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $productId)
    {
        try {
            $validated = $request->validate([
                'quantity' => 'required|integer|min:1',
            ]);

            $identifier = $this->getCartIdentifier();

            $cartItem = CartItem::where(function($query) use ($identifier) {
                    if (isset($identifier['user_id'])) {
                        $query->where('user_id', $identifier['user_id']);
                    } else {
                        $query->where('session_id', $identifier['session_id']);
                    }
                })
                ->where('product_id', $productId)
                ->firstOrFail();

            $cartItem->update(['quantity' => $validated['quantity']]);
            $cartItem->load('product');

            Log::info('Cart item quantity updated', [
                'cart_item_id' => $cartItem->id,
                'new_quantity' => $validated['quantity']
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $cartItem->product_id,
                    'cart_item_id' => $cartItem->id,
                    'name' => $cartItem->product->name,
                    'price' => (float)$cartItem->product->selling_price,
                    'quantity' => $cartItem->quantity,
                    'stock' => $cartItem->product->quantity_in_stock,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating cart item', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update cart item'
            ], 500);
        }
    }
    

 
    public function destroy($productId)
    {
        try {
            $identifier = $this->getCartIdentifier();

            $deleted = CartItem::where(function($query) use ($identifier) {
                    if (isset($identifier['user_id'])) {
                        $query->where('user_id', $identifier['user_id']);
                    } else {
                        $query->where('session_id', $identifier['session_id']);
                    }
                })
                ->where('product_id', $productId)
                ->delete();

            Log::info('Cart item removed', [
                'product_id' => $productId,
                'deleted' => $deleted
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error removing cart item', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove cart item'
            ], 500);
        }
    }

    /**
     * Clear the entire cart for the user.
     */
    public function clear()
    {
        try {
            $identifier = $this->getCartIdentifier();

            $deleted = CartItem::where(function($query) use ($identifier) {
                    if (isset($identifier['user_id'])) {
                        $query->where('user_id', $identifier['user_id']);
                    } else {
                        $query->where('session_id', $identifier['session_id']);
                    }
                })->delete();
            
            Log::info('Cart cleared', [
                'items_deleted' => $deleted,
                'identifier' => $identifier
            ]);
                
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error clearing cart', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cart'
            ], 500);
        }
    }
}