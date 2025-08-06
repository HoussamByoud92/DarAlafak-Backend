<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && (auth()->user()->is_staff || auth()->user()->is_superuser);
    }

    public function rules()
    {
        $bookId = $this->route('book') ? $this->route('book')->id : null;
        
        return [
            'title' => 'required|string|max:500',
            'isbn' => 'nullable|string|max:20|unique:books,isbn,' . $bookId,
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lt:price',
            'pages' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'summary' => 'nullable|string',
            'front_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'back_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_published' => 'boolean',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
            'stock_quantity' => 'nullable|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string|max:50',
            'language' => 'nullable|string|max:50',
            'publication_date' => 'nullable|date',
            'edition' => 'nullable|integer|min:1',
            'category_id' => 'nullable|exists:categories,id',
            'physical_format_id' => 'nullable|exists:physical_formats,id',
            'publisher_id' => 'nullable|exists:publishers,id',
            'serie_id' => 'nullable|exists:series,id',
            'author_ids' => 'nullable|array',
            'author_ids.*' => 'exists:authors,id',
            'keyword_ids' => 'nullable|array',
            'keyword_ids.*' => 'exists:keywords,id',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'عنوان الكتاب مطلوب',
            'price.required' => 'سعر الكتاب مطلوب',
            'isbn.unique' => 'رقم ISBN موجود مسبقاً',
            'front_image.image' => 'يجب أن تكون صورة الغلاف الأمامي من نوع صورة',
            'back_image.image' => 'يجب أن تكون صورة الغلاف الخلفي من نوع صورة',
        ];
    }
}
