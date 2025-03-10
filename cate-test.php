<?php
require_once 'plugins/functions.php';
$products = load_products();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>trextacy.com</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
</head>
<body>
    <div class="container">
        <h1>スタティックページ</h1>
        <div class="row">
            <?php include_card($products, 'orange001'); ?>
            <?php include_card($products, 'orange001'); ?>
            <?php include_card($products, 'orange001'); ?>
            <?php include_card($products, 'orange001'); ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>

<?php
function include_card($products, $product_id) {
    $product = $products[$product_id] ?? null;
    if ($product) {
        $variant_prices = array_column(array_filter($product['variants'], fn($v) => !$v['sold_out']), 'price');
        $min_price = !empty($variant_prices) ? min($variant_prices) : 0;
        echo '<div class="col-md-4 mb-4">';
        echo '<div class="card product-card">';
        echo '<a href="product_detail.php?product_id=' . htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8') . '">';
        echo '<img src="' . htmlspecialchars($product['images'][0] ?? 'https://placehold.jp/300x300.png', ENT_QUOTES, 'UTF-8') . '" class="card-img-top product-image" alt="' . htmlspecialchars($product['name'] ?? '商品名不明', ENT_QUOTES, 'UTF-8') . '">';
        echo '</a>';
        echo '<div class="card-body">';
        echo '<h5 class="card-title product-name"><a href="product_detail.php?product_id=' . htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($product['name'] ?? '商品名不明', ENT_QUOTES, 'UTF-8') . '</a></h5>';
        echo '<p class="card-text product-description">' . htmlspecialchars($product['description'] ?? '', ENT_QUOTES, 'UTF-8') . '</p>';
        echo '<p class="price-display">価格: <span>' . ($min_price ? number_format($min_price) . '円～' : '価格未定') . '</span></p>';
        echo '</div></div></div>';
    }
}
?>