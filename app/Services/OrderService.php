<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Book;
use Illuminate\Support\Str;

class OrderService
{
    public function createOrder(array $data)
    {
        // Calculate order totals
        $subtotal = 0;
        $items = [];
        
        foreach ($data['items'] as $itemData) {
            $book = Book::find($itemData['book_id']);
            if ($book) {
                $itemTotal = $book->price * $itemData['quantity'];
                $subtotal += $itemTotal;
                
                $items[] = [
                    'book' => $book,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $book->price,
                    'total_price' => $itemTotal
                ];
            }
        }
        
        // Create order
        $order = Order::create([
            'order_number' => 'ORD-' . Str::upper(Str::random(8)),
            'user_id' => auth()->check() ? auth()->id() : null,
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => $data['payment_method'] ?? 'cash_on_delivery',
            'subtotal' => $subtotal,
            'tax_amount' => 0,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => $subtotal,
            'currency' => 'MAD',
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'customer_phone' => $data['customer_phone'],
            'shipping_address' => $data['shipping_address'],
            'shipping_city' => $data['shipping_city'],
            'shipping_postal_code' => $data['shipping_postal_code'] ?? null,
            'shipping_country' => $data['shipping_country'] ?? 'Morocco',
            'notes' => $data['notes'] ?? null,
            'whatsapp_message' => $data['whatsapp_message'] ?? null,
        ]);
        
        // Create order items
        foreach ($items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'book_id' => $item['book']->id,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['total_price']
            ]);
            
            // Update book stock and sales count
            $item['book']->decrement('stock_quantity', $item['quantity']);
            $item['book']->increment('sales_count', $item['quantity']);
        }
        
        return $order->load('items.book');
    }
}