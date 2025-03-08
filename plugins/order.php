<?php
// カートクリア
function clear_cart() {
    unset($_SESSION['cart']);
    unset($_SESSION['customer_info']);
}

// 注文データの保存
function save_order($order_data) {
    if (file_exists('orders.json')) {
        $orders = json_decode(file_get_contents('orders.json'), true);
    } else {
        $orders = [];
    }
    $orders[] = $order_data;
    file_put_contents('orders.json', json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}