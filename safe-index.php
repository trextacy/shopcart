<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();
require_once 'plugins/functions.php';
require_once 'plugins/variants.php';

$base_path = get_base_path(); // 統一

generate_csrf_token();
$products = load_products();

$filtered_products = $products;
if (isset($_GET) && !empty($_GET)) {
    $tags = array_keys($_GET);
    $tags = explode('+', $tags[0]);
    $filtered_products = array_filter($products, function($product) use ($tags) {
        $product_tags = $product['tags'] ?? [];
        return count(array_intersect($tags, $product_tags)) === count($tags);
    });
}

$page_title = '商品一覧 - trextacy.com';
$page_description = '商品の一覧ページです。trextacy.com で購入できる商品をご覧いただけます。';
include 'header.php';
?>

<div class="d-flex flex-column min-vh-100">
    <main class="flex-grow-1">
        <div class="container-fluid p-0">
<div id="myCarousel-title" class="carousel slide mb-6"> <!-- IDを変更 -->
<div class="carousel-indicators">
    <button type="button" data-bs-target="#myCarousel-title" data-bs-slide-to="0" class="carousel-indicator active" aria-label="Slide 1" aria-current="true">
        <svg class="indicator-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
        </svg>
    </button>
    <button type="button" data-bs-target="#myCarousel-title" data-bs-slide-to="1" class="carousel-indicator" aria-label="Slide 2">
        <svg class="indicator-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
        </svg>
    </button>
    <button type="button" data-bs-target="#myCarousel-title" data-bs-slide-to="2" class="carousel-indicator" aria-label="Slide 3">
        <svg class="indicator-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
        </svg>
    </button>
</div>    <div class="carousel-inner">
        <div class="carousel-item">
            <svg class="bd-placeholder-img" width="100%" height="400" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" preserveAspectRatio="xMidYMid slice" focusable="false">
                <rect width="100%" height="100%" fill="var(--bs-secondary)"></rect>
            </svg>
            <div class="container">
                <div class="carousel-caption text-start">
                    <h1>ようこそ trextacy.com へ</h1>
                    <p class="opacity-75">最新のPCパーツや周辺機器をチェック！</p>
                    <p><a class="btn btn-lg btn-primary" href="#">今すぐ購入</a></p>
                </div>
            </div>
        </div>
        <div class="carousel-item">
            <svg class="bd-placeholder-img" width="100%" height="400" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" preserveAspectRatio="xMidYMid slice" focusable="false">
                <rect width="100%" height="100%" fill="var(--bs-secondary)"></rect>
            </svg>
            <div class="container">
                <div class="carousel-caption">
                    <h1>新製品情報</h1>
                    <p>最新アイテムをいち早くお届けします。</p>
                    <p><a class="btn btn-lg btn-primary" href="#">詳細を見る</a></p>
                </div>
            </div>
        </div>
        <div class="carousel-item active">
            <svg class="bd-placeholder-img" width="100%" height="400" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" preserveAspectRatio="xMidYMid slice" focusable="false">
                <rect width="100%" height="100%" fill="var(--bs-secondary)"></rect>
            </svg>
            <div class="container">
                <div class="carousel-caption text-end">
                    <h1>特別キャンペーン</h1>
                    <p>今だけのお得なオファーをお見逃しなく。</p>
                    <p><a class="btn btn-lg btn-primary" href="#">キャンペーンを見る</a></p>
                </div>
            </div>
        </div>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#myCarousel-title" data-bs-slide="prev"> <!-- data-bs-targetを変更 -->
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#myCarousel-title" data-bs-slide="next"> <!-- data-bs-targetを変更 -->
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>
            <div class="container">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">商品一覧</li>
                    </ol>
                </nav>

                <h1 class="mt-4 mb-3">商品一覧</h1>

                <div class="row row-cols-1 row-cols-md-3 g-4 h-100">
                    <?php foreach ($filtered_products as $product_id => $product): ?>
                        <?php
                        $variant_prices = array_column(array_filter($product['variants'], fn($v) => !$v['sold_out']), 'price');
                        $min_price = !empty($variant_prices) ? min($variant_prices) : 0;
                        $base_path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/';
                        ?>
                        <div class="col-md-4 col-12 m-md-0 gt-4 p-md-2 p-0">
                            <div class="card product-card h-100">
                                <div class="card-header">
                                    <h5 class="card-title product-name fw-bold py-2 px-3 m-0">
                                        <a href="product_detail.php?product_id=<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($product['name'] ?? '商品名不明', ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </h5>
                                </div>
                                <div class="position-relative">
                                    <a href="product_detail.php?product_id=<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>">
                                        <div id="carousel-<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>" class="carousel slide">
                                            <div class="carousel-inner square-image-container">
                                                <?php foreach ($product['images'] as $index => $image): ?>
                                                    <?php $image_src = (strpos($image, 'http') === 0) ? $image : $base_path . $image; ?>
                                                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>" data-image-src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>">
                                                        <img src="<?php echo htmlspecialchars($image_src, ENT_QUOTES, 'UTF-8'); ?>" 
                                                             class="d-block w-100 card-img-top product-image square-image" 
                                                             alt="<?php echo htmlspecialchars($product['image_descriptions'][$index] ?? '商品画像 ' . ($index + 1), ENT_QUOTES, 'UTF-8'); ?>">
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <button class="carousel-control-prev" type="button" data-bs-target="#carousel-<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>" data-bs-slide="prev">
                                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                <span class="visually-hidden">Previous</span>
                                            </button>
                                            <button class="carousel-control-next" type="button" data-bs-target="#carousel-<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>" data-bs-slide="next">
                                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                <span class="visually-hidden">Next</span>
                                            </button>
                                        </div>
                                    </a>
                                </div>
                                <div class="card-body">
                                    <div class="card-text product-description"><?php echo $product['description'] ?? ''; ?></div>
                                    <p class="price-display">価格: <span id="variant-price-<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo $min_price ? number_format($min_price) . '円～' : '価格未定'; ?>
                                    </span></p>
                                    <form method="post" action="cart.php?action=add" onsubmit="return validateForm(this)">
                                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <div class="row g-2 variant-options">
                                            <?php echo display_variant_options($product); ?>
                                        </div>
                                        <div class="mb-3 mt-2">
                                            <label for="quantity" class="form-label">数量</label>
                                            <input type="number" class="form-control" name="quantity" value="1" min="1" max="100" required>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <button type="submit" class="btn btn-primary">カートに追加</button>
                                            <a href="product_detail.php?product_id=<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">詳細を見る</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>
</div>

<script>
function updatePriceAndImage(form) {
    const selects = form.querySelectorAll('.variant-select');
    const productId = form.querySelector('input[name="product_id"]').value;
    const priceDisplay = document.getElementById('variant-price-' + productId);
    const carousel = document.getElementById('carousel-' + productId);
    const product = <?php echo json_encode($products, JSON_UNESCAPED_UNICODE); ?>[productId];
    const variants = product.variants || {};
    const attributes = product.attributes || {};
    const defaultImage = product.images[0] || 'https://placehold.jp/300x300.png';
    const minPrice = <?php echo json_encode(array_map(function($product) {
        $variant_prices = array_column(array_filter($product['variants'], fn($v) => !$v['sold_out']), 'price');
        return !empty($variant_prices) ? min($variant_prices) : 0;
    }, $products), JSON_UNESCAPED_UNICODE); ?>[productId];

    const selected = {};
    selects.forEach(select => {
        if (select.value) selected[select.getAttribute('data-attr')] = select.value;
    });
    const variantKey = Object.values(selected).join('-');
    const variant = variants[variantKey];
    const price = variant && !variant.sold_out ? variant.price : null;

    let image = variant && variant.image ? variant.image : null;
    if (!image) {
        for (const [attr, value] of Object.entries(selected)) {
            if (attributes[attr]?.images?.[value]) {
                image = attributes[attr].images[value];
                break;
            }
        }
    }
    image = image || defaultImage;

    priceDisplay.textContent = price ? `${price.toLocaleString()}円` : `${minPrice.toLocaleString()}円～`;

    const items = carousel.querySelectorAll('.carousel-item');
    let targetIndex = 0;
    items.forEach((item, index) => {
        if (item.getAttribute('data-image-src') === image) {
            targetIndex = index;
        }
    });
    const carouselInstance = bootstrap.Carousel.getInstance(carousel) || new bootstrap.Carousel(carousel, { interval: false, wrap: false });
    carouselInstance.to(targetIndex);
}

function validateForm(form) {
    const selects = form.querySelectorAll('.variant-select');
    let allSelected = true;
    selects.forEach(select => {
        if (!select.value) allSelected = false;
    });
    if (!allSelected) {
        alert('すべてのオプションを選択してください。');
        return false;
    }
    const productId = form.querySelector('input[name="product_id"]').value;
    const variants = <?php echo json_encode($products, JSON_UNESCAPED_UNICODE); ?>[productId].variants;
    const variantKey = getVariantKey(form);
    if (!variants[variantKey] || variants[variantKey].sold_out) {
        alert('選択したバリアントは在庫切れです。');
        return false;
    }
    return true;
}

function getVariantKey(form) {
    const selects = form.querySelectorAll('.variant-select');
    const selected = {};
    selects.forEach(select => {
        if (select.value) selected[select.getAttribute('data-attr')] = select.value;
    });
    return Object.values(selected).join('-');
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form').forEach(form => {
        form.querySelectorAll('.variant-select').forEach(select => {
            select.addEventListener('change', () => updatePriceAndImage(form));
        });
        updatePriceAndImage(form);
    });

    const topCarousel = document.getElementById('myCarousel-title'); /* IDを変更 */
    if (topCarousel) {
        new bootstrap.Carousel(topCarousel, {
            interval: false, /* 自動再生をオフ */
            wrap: false      /* ループをオフ */
        });
    }
});
</script>

<style>
html, body {
    height: 100%;
    margin: 0;
}
.carousel-inner img {
    height: 300px;
    object-fit: cover;
}
.product-description ul, .product-description ol {
    margin-left: 20px;
}
.product-description a {
    color: #007bff;
    text-decoration: underline;
}
.product-description a:hover {
    color: #0056b3;
}
</style>