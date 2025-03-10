<?php
session_start();

$cart_items = isset($_SESSION['cart_items']) ? $_SESSION['cart_items'] : [];

$json_file = file_get_contents('products.json');
$products = json_decode($json_file, true);

if ($products === null) {
    die('products.json の読み込みに失敗しました。JSON形式が正しいか確認してください。');
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'get_total') {
    $total_price = 0;
    foreach ($cart_items as $item_key => $item) {
        $product_id = $item['product_id'];
        $size = $item['size'];
        $quantity = $item['quantity'];

        if (isset($products[$product_id]['sizes'][$size])) {
            $item_price = $products[$product_id]['sizes'][$size]['price'];
            $subtotal = $item_price * $quantity;
            $total_price += $subtotal;
        }
    }

    // 合計金額を JSON 形式でレスポンス
    header('Content-Type: application/json');
    echo json_encode(['total_price' => $total_price]);
    exit;
}
?>