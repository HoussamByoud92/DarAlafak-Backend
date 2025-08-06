<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;
use Illuminate\Support\Carbon;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $settings = [
            [
                'key' => 'site_name',
                'value' => 'دار الآفاق المغربية',
                'type' => 'string',
                'description' => 'Site name',
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'site_description',
                'value' => 'دار نشر متخصصة في الكتب القانونية والأكاديمية',
                'type' => 'string',
                'description' => 'Site description',
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'contact_email',
                'value' => 'info@daralafaq.ma',
                'type' => 'string',
                'description' => 'Contact email',
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'contact_phone',
                'value' => '+212 5 37 XX XX XX',
                'type' => 'string',
                'description' => 'Contact phone',
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'contact_address',
                'value' => 'الرباط، المغرب',
                'type' => 'string',
                'description' => 'Contact address',
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'whatsapp_number',
                'value' => '+212 6 XX XX XX XX',
                'type' => 'string',
                'description' => 'WhatsApp number',
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'facebook_url',
                'value' => 'https://facebook.com/daralafaq',
                'type' => 'string',
                'description' => 'Facebook URL',
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'instagram_url',
                'value' => 'https://instagram.com/daralafaq',
                'type' => 'string',
                'description' => 'Instagram URL',
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'twitter_url',
                'value' => 'https://twitter.com/daralafaq',
                'type' => 'string',
                'description' => 'Twitter URL',
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'shipping_cost',
                'value' => '30.00',
                'type' => 'string',
                'description' => 'Default shipping cost',
                'is_public' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'free_shipping_threshold',
                'value' => '500.00',
                'type' => 'string',
                'description' => 'Free shipping threshold',
                'is_public' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'tax_rate',
                'value' => '0.20',
                'type' => 'string',
                'description' => 'Tax rate (VAT)',
                'is_public' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'currency',
                'value' => 'MAD',
                'type' => 'string',
                'description' => 'Default currency',
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'books_per_page',
                'value' => '12',
                'type' => 'integer',
                'description' => 'Books per page',
                'is_public' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'featured_books_count',
                'value' => '8',
                'type' => 'integer',
                'description' => 'Featured books count on homepage',
                'is_public' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'recent_books_count',
                'value' => '6',
                'type' => 'integer',
                'description' => 'Recent books count on homepage',
                'is_public' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        Setting::upsert($settings, ['key'], ['value', 'type', 'description', 'is_public', 'updated_at']);
    }
}
