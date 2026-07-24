<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Safo Configuration
    |--------------------------------------------------------------------------
    */

    'default_delivery_fee' => env('DEFAULT_DELIVERY_FEE', 500),
    'free_delivery_threshold' => env('FREE_DELIVERY_THRESHOLD', 10000),
    'currency' => env('DEFAULT_CURRENCY', 'YER'),
    'currency_symbol' => env('DEFAULT_CURRENCY_SYMBOL', '﷼'),

    'max_cart_items' => 100,
    'max_upload_size_kb' => env('MAX_UPLOAD_SIZE', 5120),
    'allowed_image_types' => explode(',', env('ALLOWED_IMAGE_TYPES', 'jpg,jpeg,png,webp')),

    'otp_length' => 6,
    'otp_expiry_minutes' => 5,
    'otp_max_attempts' => 3,
    'otp_resend_cooldown_seconds' => 60,
];
