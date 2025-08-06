<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Book;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function createOrder(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Create order
            $order = Order::create([
                'user_id' => auth()->id(),
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'],
                'shipping_address' => $data['shipping_address'],
                'shipping_city' => $data['shipping_city'],
                'shipping_postal_code' => $data['shipping_postal_code'] ?? null,
                'shipping_country' => $data['shipping_country'] ?? 'Morocco',
                'notes' => $data['notes'] ?? null,
                'whatsapp_message' => $data['whatsapp_message'] ?? null,
                'subtotal' => 0,
                'tax_amount' => 0,
                'shipping_amount' => $this->calculateShipping($data),
                'total_amount' => 0,
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $data['payment_method'] ?? 'cash_on_delivery',
            ]);

            $subtotal = 0;

            // Create order items
            foreach ($data['items'] as $item) {
                $book = Book::findOrFail($item['book_id']);
                $itemTotal = $book->final_price * $item['quantity'];
                
                OrderItem::create([
                    'order_id' => $order->id,
                    'book_id' => $book->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $book->final_price,
                    'total_price' => $itemTotal,
                ]);

                $subtotal += $itemTotal;
                
                // Update book sales count
                $book->increment('sales_count', $item['quantity']);
                
                // Update stock if tracked
                if ($book->stock_quantity > 0) {
                    $book->decrement('stock_quantity', $item['quantity']);
                }
            }

            // Calculate totals
            $taxAmount = $subtotal * 0.20; // 20% VAT
            $totalAmount = $subtotal + $taxAmount + $order->shipping_amount;

            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
            ]);

            return $order->load(['items.book']);
        });
    }

    private function calculateShipping(array $data)
    {
        // Free shipping for orders over 500 MAD
        $freeShippingThreshold = Setting::where('key', 'free_shipping_threshold')->value('value') ?? 500;
        $defaultShippingCost = Setting::where('key', 'shipping_cost')->value('value') ?? 30;
        
        // Calculate subtotal from items
        $subtotal = 0;
        foreach ($data['items'] as $item) {
            $book = Book::find($item['book_id']);
            if ($book) {
                $subtotal += $book->final_price * $item['quantity'];
            }
        }

        return $subtotal >= $freeShippingThreshold ? 0 : $defaultShippingCost;
    }
}
