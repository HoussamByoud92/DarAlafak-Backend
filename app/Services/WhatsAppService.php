<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public function sendOrderNotification(Order $order)
    {
        try {
            $message = $this->formatOrderMessage($order);
            $whatsappNumber = config('services.whatsapp.number');
            
            // Send to admin WhatsApp
            $this->sendMessage($whatsappNumber, $message);
            
            Log::info('WhatsApp order notification sent', ['order_id' => $order->id]);
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp notification', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function formatOrderMessage(Order $order)
    {
        $message = "🆕 *طلب جديد*\n\n";
        $message .= "📋 *رقم الطلب:* {$order->order_number}\n";
        $message .= "👤 *العميل:* {$order->customer_name}\n";
        $message .= "📧 *البريد:* {$order->customer_email}\n";
        $message .= "📱 *الهاتف:* {$order->customer_phone}\n";
        $message .= "📍 *العنوان:* {$order->shipping_address}, {$order->shipping_city}\n\n";
        
        $message .= "📚 *الكتب المطلوبة:*\n";
        foreach ($order->items as $item) {
            $message .= "• {$item->book->title} × {$item->quantity} - {$item->total_price} د.م\n";
        }
        
        $message .= "\n💰 *المجموع:* {$order->total_amount} د.م\n";
        
        if ($order->notes) {
            $message .= "\n📝 *ملاحظات:* {$order->notes}\n";
        }
        
        return $message;
    }

    private function sendMessage($number, $message)
    {
        // Implementation depends on your WhatsApp API provider
        // This is a generic example
        
        $apiUrl = config('services.whatsapp.api_url');
        $apiToken = config('services.whatsapp.api_token');
        
        Http::post($apiUrl, [
            'token' => $apiToken,
            'to' => $number,
            'body' => $message
        ]);
    }
}
