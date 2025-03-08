<?php
require_once './plugins/functions.php';
require_once './plugins/variants.php';

function add_to_cart($product_id, $quantity, $variant_key, &$cart, $products) {
    $quantity = max(1, (int)$quantity);
    if (isset($products[$product_id]) && is_variant_available($products[$product_id], $variant_key)) {
        foreach ($cart as &$item) {
            if ($item['product_id'] === $product_id && $item['variant'] === $variant_key) {
                $item['quantity'] += $quantity;
                return true;
            }
        }
        $cart[] = ['product_id' => $product_id, 'quantity' => $quantity, 'variant' => $variant_key];
        return true;
    }
    return false;
}

function remove_from_cart($index, &$cart) {
    if (isset($cart[$index])) {
        unset($cart[$index]);
        $cart = array_values($cart);
        return true;
    }
    return false;
}
?>