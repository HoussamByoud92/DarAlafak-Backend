<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:254',
            'customer_phone' => 'required|string|max:20',
            'shipping_address' => 'required|string',
            'shipping_city' => 'required|string|max:100',
            'shipping_postal_code' => 'nullable|string|max:20',
            'shipping_country' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'whatsapp_message' => 'nullable|string',
            'payment_method' => 'nullable|in:cash_on_delivery,bank_transfer,credit_card',
            'items' => 'required|array|min:1',
            'items.*.book_id' => 'required|exists:books,id',
            'items.*.quantity' => 'required|integer|min:1',
        ];
    }

    public function messages()
    {
        return [
            'customer_name.required' => 'اسم العميل مطلوب',
            'customer_email.required' => 'البريد الإلكتروني مطلوب',
            'customer_phone.required' => 'رقم الهاتف مطلوب',
            'shipping_address.required' => 'عنوان الشحن مطلوب',
            'shipping_city.required' => 'مدينة الشحن مطلوبة',
            'items.required' => 'يجب إضافة كتاب واحد على الأقل',
        ];
    }
}
