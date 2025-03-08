<?php
function display_variant_options($product) {
    $attributes = $product['attributes'] ?? [];
    $variants = $product['variants'] ?? [];

    $available_variants = array_filter($variants, fn($v) => !$v['sold_out']);
    if (empty($available_variants) || empty($attributes)) {
        return '<p class="text-danger">在庫なし</p>';
    }

    $output = '<div class="mb-3 variant-options">';
    foreach ($attributes as $attr_name => $values) {
        if (empty($values)) continue;

        $available_values = [];
        foreach ($values as $value) {
            foreach ($available_variants as $key => $variant) {
                $parts = explode('-', $key);
                if (in_array($value, $parts)) {
                    $available_values[$value] = true;
                    break;
                }
            }
        }

        if (empty($available_values)) {
            $output .= '<p class="text-danger">' . htmlspecialchars($attr_name, ENT_QUOTES, 'UTF-8') . ': 在庫なし</p>';
            continue;
        }

        $output .= "<select class='form-select variant-select' name='variant[{$attr_name}]' data-attr='{$attr_name}' required>";
        $output .= "<option value=''>{$attr_name}を選択</option>";

        foreach ($values as $value) {
            if (isset($available_values[$value])) {
                $output .= "<option value='" . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . "'>" . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . "</option>";
            }
        }
        $output .= "</select>";
    }
    $output .= '</div>';

    return $output;
}

function get_variant_key($selected_attributes) {
    return is_array($selected_attributes) ? implode('-', array_values($selected_attributes)) : '';
}

function is_variant_available($product, $variant_key) {
    $variants = $product['variants'] ?? [];
    return isset($variants[$variant_key]) && !$variants[$variant_key]['sold_out'];
}

function get_sold_out_variants($product) {
    $variants = $product['variants'] ?? [];
    $sold_out = [];
    foreach ($variants as $key => $variant) {
        if ($variant['sold_out']) {
            $sold_out[] = $key;
        }
    }
    return $sold_out;
}

function get_variant_image($product, $variant_key) {
    $variants = $product['variants'] ?? [];
    $default_image = $product['default_image'] ?? ($product['images'][0] ?? 'https://placehold.jp/300x300.png');

    if (isset($variants[$variant_key]['image']) && !empty($variants[$variant_key]['image'])) {
        return $variants[$variant_key]['image'];
    }

    return $default_image;
}
?>