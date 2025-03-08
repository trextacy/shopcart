<?php
$base_path = get_base_path(); // 統一

function display_product_images($product) {
    $images = $product['images'] ?? [$product['image_url'] ?? 'https://placehold.jp/500x500.png'];
    $output = '<div class="row">';
    $output .= '<div class="col-md-12"><img id="product-image" src="' . htmlspecialchars($images[0], ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') . '" class="img-fluid"></div>';
    if (count($images) > 1) {
        $output .= '<div class="mt-2">';
        foreach ($images as $image) {
            $output .= '<img src="' . htmlspecialchars($image, ENT_QUOTES, 'UTF-8') . '" alt="サムネイル" class="img-thumbnail me-2" style="width: 80px; cursor: pointer;" onclick="document.getElementById(\'product-image\').src = this.src;">';
        }
        $output .= '</div>';
    }
    $output .= '</div>';
    return $output;
}