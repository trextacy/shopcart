<?php
function send_order_email($customer_info, $cart_items, $products, $total_price, $order_number, $delivery_date) {
    $to = $customer_info['email'] ?? $customer_info['phone'] . '@example.com'; // 仮に電話番号ベース
    $subject = "ご注文ありがとうございます [注文番号: $order_number]";
    $message = "ご注文内容:\n";
    foreach ($cart_items as $item) {
        $product = $products[$item['product_id']] ?? null;
        if ($product && isset($product['sizes'][$item['size']])) {
            $price = $product['sizes'][$item['size']]['price'];
            $subtotal = $price * $item['quantity'];
            $message .= "- {$product['name']} ({$product['sizes'][$item['size']]['name']}): {$item['quantity']}個, " . number_format($subtotal) . "円\n";
        }
    }
    $message .= "合計: " . number_format($total_price) . "円\n";
    $message .= "配送予定日: $delivery_date\n";
    $headers = "From: no-reply@trextacy.com";
    return mail($to, $subject, $message, $headers);
}