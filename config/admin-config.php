<?php
return [
    'payment_methods' => [
        'bank_transfer' => '郵便振込',
        'cash_on_delivery' => '代引き',
    ],
    'shipping' => [
        'cash_on_delivery' => [
            'base_fee' => 770,
            'free_threshold' => 11000,
            'cod_fee' => 330,
        ],
        'bank_transfer' => [
            'base_fee' => 770,
            'remote_fee' => 1100,
            'remote_areas' => ['北海道', '沖縄', '福岡', '佐賀', '長崎', '熊本', '大分', '宮崎', '鹿児島'],
            'free_threshold' => 11000,
        ],
    ],
    'email' => [
        'send_to_customer' => true,
        'send_to_admin' => true,
        'admin_email' => 'admin@example.com',
        'from_email' => 'shop@trextacy.com',
        'subject' => 'ご注文ありがとうございます - 注文番号: {ORDER_NUMBER}',
    ],
    'currency' => '円',
    'tax_included' => true,
    // パスワード保護用の設定を追加
    'admin_password' => password_hash('admin123', PASSWORD_DEFAULT), // 初期パスワード: admin123
];
?>