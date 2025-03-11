<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();
require_once 'plugins/functions.php';
require_once 'plugins/variants.php';

$base_path = get_base_path();
generate_csrf_token();
$products = load_products();

$product_id = $_GET['product_id'] ?? '';
if (!isset($products[$product_id]) || (isset($products[$product_id]['is_public']) && !$products[$product_id]['is_public'])) {
    $page_title = '商品が見つかりません - SHOPCART';
    include 'header.php';
    ?>
    <div class="d-flex flex-column min-vh-100">
        <main class="flex-grow-1">
            <div class="container py-5 text-center">
                <h1 class="mb-4">商品が見つかりません</h1>
                <p class="mb-4">お探しの商品は現在ご覧いただけません。商品一覧から他の商品をご覧ください。</p>
                <a href="index.php" class="btn btn-primary">商品一覧に戻る</a>
            </div>
        </main>
        <?php include 'footer.php'; ?>
    </div>
    </body>
    </html>
    <?php
    exit;
}

$product = $products[$product_id];
$page_title = htmlspecialchars($product['name']) . ' - SHOPCART';
$page_description = htmlspecialchars(strip_tags($product['seo_description'] ?? $product['description']));
include 'header.php';

$variant_prices = array_column(array_filter($product['variants'], fn($v) => !$v['sold_out']), 'price');
$min_price = !empty($variant_prices) ? min($variant_prices) : 0;
$all_sold_out = empty($product['variants']) || !array_reduce($product['variants'], fn($carry, $v) => $carry || !$v['sold_out'], false);
?>

<div class="d-flex flex-column min-vh-100">
    <main class="flex-grow-1">
        <div class="container py-5">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">商品一覧</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['name'] ?? '商品名不明', ENT_QUOTES, 'UTF-8'); ?></li>
                </ol>
            </nav>


<div class="row g-3">


<!-- Swiper 部分 -->
<div class="col-md-7">
    <div class="swiper productSwiper" id="swiper-product">
        <div class="swiper-wrapper">
            <?php foreach ($product['images'] as $index => $image): ?>
                <?php $image_src = (strpos($image, 'http') === 0) ? $image : $base_path . $image; ?>
                <div class="swiper-slide">
                    <img src="<?php echo htmlspecialchars($image_src, ENT_QUOTES, 'UTF-8'); ?>" 
                         class="img-fluid clickable-image" 
                         alt="<?php echo htmlspecialchars($product['image_descriptions'][$index] ?? $product['name'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            <?php endforeach; ?>
        </div>
        <div class="swiper-pagination mt-3"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-button-next"></div>
    </div>
<div class="swiper productThumbs mt-3" id="swiper-thumbs">
    <div class="swiper-wrapper">
        <?php foreach ($product['images'] as $index => $image): ?>
            <?php 
            $image_src = (strpos($image, 'http') === 0) ? $image : $base_path . $image;
            // 画像のサイズを取得（ローカルファイルの場合）
            $aspect = 'square'; // デフォルト
            if (strpos($image, 'http') !== 0) { // ローカルファイルの場合
                $file_path = $base_path . $image;
                if (file_exists($file_path)) {
                    list($width, $height) = getimagesize($file_path);
                    $aspect = ($width > $height) ? 'landscape' : ($width < $height ? 'portrait' : 'square');
                }
            }
            ?>
            <div class="swiper-slide">
                <img src="<?php echo htmlspecialchars($image_src, ENT_QUOTES, 'UTF-8'); ?>" 
                     class="img-fluid" 
                     data-aspect="<?php echo $aspect; ?>" 
                     alt="<?php echo htmlspecialchars($product['image_descriptions'][$index] ?? $product['name'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        <?php endforeach; ?>
    </div>
</div>
</div>


<div class="col-md-5">
    <h1 class="fw-bold mb-3"><?php echo htmlspecialchars($product['name'] ?? '商品名不明', ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="price fs-3 mb-4"><span id="selected-price"><?php echo $min_price ? number_format($min_price) . '円～' : '価格未定'; ?></span></p>
    <?php
    $sold_out_variants = get_sold_out_variants($product);
    if (!empty($sold_out_variants)): ?>
        <p class="text-danger mb-3">在庫切れ: <?php echo htmlspecialchars(implode(', ', $sold_out_variants), ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
    <form method="post" action="cart.php?action=add" id="productForm" class="mb-4">
        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        <div class="variant-options mb-4">
            <?php echo display_variant_options($product); ?>
        </div>
        <div class="mb-4">
            <label for="quantity" class="form-label">数量</label>
            <input type="number" class="form-control w-25" name="quantity" value="1" min="1" max="100" required>
        </div>
        <button type="submit" class="btn btn-primary btn-lg w-100 <?php echo $all_sold_out ? 'btn-secondary disabled' : ''; ?>" <?php echo $all_sold_out ? 'disabled' : ''; ?>>カートに追加</button>
    </form>
</div>
    <div class="product-description"><?php echo $product['description']; ?></div>

            </div>
        </div>

<div class="modal fade" id="imageModal" tabindex="-1" aria-label="商品画像の拡大表示" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="swiper modalSwiper" id="swiper-modal">
                    <div class="swiper-wrapper">
                        <?php foreach ($product['images'] as $index => $image): ?>
                            <?php 
                            $image_src = (strpos($image, 'http') === 0) ? $image : $base_path . $image;
                            $description = $product['image_descriptions'][$index] ?? '';
                            $max_length = 20;
                            if (mb_strlen($description, 'UTF-8') > $max_length) {
                                $description = mb_substr($description, 0, $max_length, 'UTF-8') . '...';
                            }
                            ?>
                            <div class="swiper-slide">
                                <div class="swiper-zoom-container" data-swiper-zoom="3">
                                    <img src="<?php echo htmlspecialchars($image_src, ENT_QUOTES, 'UTF-8'); ?>" 
                                         class="img-fluid" 
                                         alt="<?php echo htmlspecialchars($product['image_descriptions'][$index] ?? $product['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <?php if ($description): ?>
                                    <p class="image-description text-center"><?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-prev"></div>
                    <div class="swiper-button-next"></div>
                </div>
            </div>
        </div>
    </div>
</div>


    </main>

    <?php include 'footer.php'; ?>
</div>

<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
<script src="<?php echo $base_path; ?>js/common.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // サムネイル用のSwiper
    const productThumbs = new Swiper('.productThumbs', {
        loop: false,
        spaceBetween: 10,
        slidesPerView: 4,
        freeMode: true,
        watchSlidesProgress: true,
    });

    // メインのSwiper
    const productSwiper = new Swiper('.productSwiper', {
        loop: false,
        pagination: { el: '.swiper-pagination', clickable: true },
        navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
        thumbs: { swiper: productThumbs },
    });

    // モーダルのSwiper
    const modalSwiper = new Swiper('.modalSwiper', {
        loop: false,
        zoom: true,
        pagination: { el: '.swiper-pagination', clickable: true },
        navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
        zoom: {
            maxRatio: 3,
            minRatio: 1
        },
        on: {
            init: function () {
                const altDescriptions = <?php echo json_encode($product['image_descriptions'] ?? array_fill(0, count($product['images']), $product['name']), JSON_UNESCAPED_UNICODE); ?>;
                document.querySelectorAll('.modalSwiper .swiper-slide img').forEach((img, index) => {
                    img.setAttribute('alt', altDescriptions[index] || '商品画像');
                });
            },
        },
    });

    // 以下は変更なし
    const form = document.getElementById('productForm');
    const priceDisplay = document.getElementById('selected-price');
    const product = <?php echo json_encode($product, JSON_UNESCAPED_UNICODE); ?>;
    const minPrice = <?php echo $min_price ? $min_price : 0; ?>;

    initializeVariantButtons(form, priceDisplay, productSwiper, product, minPrice, true);

    const selects = form.querySelectorAll('.variant-select');
    selects.forEach(select => {
        if (select.tagName === 'SELECT') {
            select.addEventListener('change', () => {
                updatePriceAndImage(form, priceDisplay, productSwiper, product, minPrice, true);
            });
        }
    });

    updatePriceAndImage(form, priceDisplay, productSwiper, product, minPrice, true);

    form.addEventListener('submit', function(e) {
        if (!validateForm(form, product)) {
            e.preventDefault();
        }
    });

    document.querySelectorAll('.clickable-image').forEach(img => {
        let touchStartX = 0;
        let touchStartY = 0;
        let touchMoved = false;

        img.addEventListener('touchstart', (e) => {
            touchStartX = e.touches[0].clientX;
            touchStartY = e.touches[0].clientY;
            touchMoved = false;
        });

        img.addEventListener('touchmove', (e) => {
            const touchX = e.touches[0].clientX;
            const touchY = e.touches[0].clientY;
            const diffX = Math.abs(touchX - touchStartX);
            const diffY = Math.abs(touchY - touchStartY);
            if (diffX > 10 || diffY > 10) {
                touchMoved = true;
            }
        });

        img.addEventListener('touchend', (e) => {
            if (!touchMoved) {
                const modal = new bootstrap.Modal(document.getElementById('imageModal'));
                const src = img.src;
                const slides = document.querySelectorAll('.modalSwiper .swiper-slide');
                let targetIndex = 0;
                slides.forEach((slide, index) => {
                    if (slide.querySelector('img').src === src) {
                        targetIndex = index;
                    }
                });
                modalSwiper.slideTo(targetIndex);
                modal.show();
            }
        });

        // クリックイベントをダブルクリックと区別
        let clickCount = 0;
        img.addEventListener('click', (e) => {
            clickCount++;
            if (clickCount === 1) {
                setTimeout(() => {
                    if (clickCount === 1) {
                        const modal = new bootstrap.Modal(document.getElementById('imageModal'));
                        const src = img.src;
                        const slides = document.querySelectorAll('.modalSwiper .swiper-slide');
                        let targetIndex = 0;
                        slides.forEach((slide, index) => {
                            if (slide.querySelector('img').src === src) {
                                targetIndex = index;
                            }
                        });
                        modalSwiper.slideTo(targetIndex);
                        modal.show();
                    } else if (clickCount === 2) {
                        e.preventDefault(); // ダブルクリックでズームを優先
                    }
                    clickCount = 0;
                }, 300);
            }
        });
    });
});
</script>