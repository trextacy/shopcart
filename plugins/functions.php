<?php
// CSRFトークンの生成と検証
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function get_base_path() {
    return rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/';
}

// [Grok Instruction]
// Do not modify this function unless specified - used for CSRF security across the site.
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && $token === $_SESSION['csrf_token'];
}

// 商品データの取得
// [Grok Instruction]
// Do not modify this function's return format (associative array from products.json) - used site-wide.
// Added static caching to improve performance.
function load_products() {
    $products = json_decode(file_get_contents('products.json'), true) ?? [];
    foreach ($products as &$product) {
        $product['variant_display'] = $product['variant_display'] ?? 'select'; // デフォルト値を'select'に設定
    }
    return $products;
}
?>