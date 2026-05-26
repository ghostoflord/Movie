<?php

return [
    'tmn_code' => env('VNPAY_TMN_CODE', ''),
    'hash_secret' => env('VNPAY_HASH_SECRET', ''),
    'url' => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
    'return_url' => env('VNPAY_RETURN_URL', ''),
    'ipn_url' => env('VNPAY_IPN_URL', ''),
    'timezone' => env('VNPAY_TIMEZONE', 'Asia/Ho_Chi_Minh'),
    'expire_minutes' => (int) env('VNPAY_EXPIRE_MINUTES', 15),
    'skip_ipn' => filter_var(env('VNPAY_SKIP_IPN', true), FILTER_VALIDATE_BOOL),
    'append_user_id_to_return' => filter_var(env('VNPAY_APPEND_USER_ID_TO_RETURN', false), FILTER_VALIDATE_BOOL),
    'frontend_url' => rtrim((string) env('FRONTEND_URL', 'http://localhost:3001'), '/'),

    'packages' => [
        // Alias theo controller mẫu (monthly/yearly)
        'monthly' => [
            'label' => 'VIP 1 thang',
            'months' => 1,
            'days' => 30,
            'amount' => (int) env('VNPAY_VIP_MONTHLY_AMOUNT', 79000),
        ],
        'yearly' => [
            'label' => 'VIP 1 nam',
            'months' => 12,
            'days' => 365,
            'amount' => (int) env('VNPAY_VIP_YEARLY_AMOUNT', 790000),
        ],
    ],

    'vip_role' => 'VIP',
    'default_role_after_expire' => 'USER',
    'protected_roles' => ['ADMIN', 'SUPER_ADMIN'],
];
