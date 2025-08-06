<?php

use App\Models\Setting;

if (!function_exists('setting')) {
    /**
     * Get a setting value by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function setting($key, $default = null)
    {
        return Setting::get($key, $default);
    }
}

if (!function_exists('format_price')) {
    /**
     * Format price with currency
     *
     * @param float $price
     * @param string $currency
     * @return string
     */
    function format_price($price, $currency = 'MAD')
    {
        return number_format($price, 2) . ' ' . $currency;
    }
}

if (!function_exists('generate_order_number')) {
    /**
     * Generate unique order number
     *
     * @return string
     */
    function generate_order_number()
    {
        return 'ORD-' . date('Y') . '-' . strtoupper(uniqid());
    }
}

if (!function_exists('calculate_discount_percentage')) {
    /**
     * Calculate discount percentage
     *
     * @param float $originalPrice
     * @param float $discountPrice
     * @return int
     */
    function calculate_discount_percentage($originalPrice, $discountPrice)
    {
        if ($originalPrice <= 0 || $discountPrice >= $originalPrice) {
            return 0;
        }
        
        return round((($originalPrice - $discountPrice) / $originalPrice) * 100);
    }
}
