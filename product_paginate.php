<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once 'plugins/functions.php';
require_once 'plugins/variants.php';

$products = load_products();

// 非公開商品を除外
$filtered_products = array_filter($products, function($product) {
    return isset($product['is_public']) ? $product['is_public'] : true;
});

// カテゴリーソート
$category = $_GET['category'] ?? '';
if (!empty($category)) {
    $filtered_products = array_filter($filtered_products, function($product) use ($category) {
        return ($product['category'] ?? '') === $category;
    });
}

// タグフィルタリング
$tags = isset($_GET['tags']) && !empty($_GET['tags']) ? explode('+', $_GET['tags']) : [];
if (!empty($tags)) {
    $filtered_products = array_filter($filtered_products, function($product) use ($tags) {
        $product_tags = $product['tags'] ?? [];
        return count(array_intersect($tags, $product_tags)) === count($tags);
    });
}

$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = max(1, (int)($_GET['per_page'] ?? 10));
$offset = ($page - 1) * $per_page;

$paginated_products = array_slice($filtered_products, $offset, $per_page, true);

$response = [];
foreach ($paginated_products as $id => $product) {
    $variant_prices = array_column(array_filter($product['variants'], fn($v) => !$v['sold_out']), 'price');
    $min_price = !empty($variant_prices) ? min($variant_prices) : 0;
    ob_start();
    echo display_variant_options($product);
    $variant_options = ob_get_clean();

    $response[] = [
        'id' => $id,
        'name' => $product['name'] ?? '商品名不明',
        'description' => $product['description'] ?? '',
        'images' => $product['images'] ?? [],
        'image_descriptions' => $product['image_descriptions'] ?? [],
        'min_price' => $min_price,
        'variant_options' => $variant_options,
        'variants' => $product['variants'] ?? [],
        'attributes' => $product['attributes'] ?? []
    ];
}

echo json_encode([
    'products' => $response,
    'debug' => ['total' => count($filtered_products), 'page' => $page, 'offset' => $offset, 'returned' => count($response)]
], JSON_UNESCAPED_UNICODE);
?>