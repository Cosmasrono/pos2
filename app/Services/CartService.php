<?php

namespace App\Services;

use App\Models\CartItem;
use Illuminate\Support\Facades\DB;

class CartService
{
    /**
     * Merge guest cart items into a user's cart.
     */
    public function mergeGuestCart(string $sessionId, int $userId): void
    {
        DB::transaction(function () use ($sessionId, $userId) {
            $guestItems = CartItem::where('session_id', $sessionId)->get();

            foreach ($guestItems as $guestItem) {
                $existingItem = CartItem::where('user_id', $userId)
                    ->where('product_id', $guestItem->product_id)
                    ->first();

                if ($existingItem) {
                    // Update quantity if product already exists in user's cart
                    $existingItem->update([
                        'quantity' => $existingItem->quantity + $guestItem->quantity
                    ]);
                    $guestItem->delete();
                } else {
                    // Reassign the guest item to the user
                    $guestItem->update([
                        'user_id' => $userId,
                        'session_id' => null
                    ]);
                }
            }
        });
    }
}
