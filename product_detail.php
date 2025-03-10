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
    $page_title = '商品が見つかりません - trextacy.com';
    include 'header.php';
    ?>
    <div class="d-flex flex-column min-vh-100">
        <main class="flex-grow-1">
            <div class="container py-5 text-center">
                <h1 class="mb-4">商品が見つかりません</h1>
                <p class="text-muted mb-4">お探しの商品は現在ご覧いただけません。商品一覧から他の商品をご覧ください。</p>
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
$page_title = htmlspecialchars($product['name']) . ' - trextacy.com';
$page_description = htmlspecialchars(strip_tags($product['description']));
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

            <div class="row g-5">
                <div class="col-md-6">
                    <div class="swiper productSwiper">
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
                </div>

<div class="col-md-6">
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
    <div class="product-description text-muted"><?php echo $product['description']; ?></div>
</div>
            </div>
        </div>

        <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="imageModalLabel"><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="swiper modalSwiper">
                            <div class="swiper-wrapper">
                                <?php foreach ($product['images'] as $index => $image): ?>
                                    <?php $image_src = (strpos($image, 'http') === 0) ? $image : $base_path . $image; ?>
                                    <div class="swiper-slide">
                                        <img src="<?php echo htmlspecialchars($image_src, ENT_QUOTES, 'UTF-8'); ?>" 
                                             class="img-fluid" 
                                             alt="<?php echo htmlspecialchars($product['image_descriptions'][$index] ?? $product['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <p class="text-center mt-2 text-muted"><?php echo htmlspecialchars($product['image_descriptions'][$index] ?? '画像 ' . ($index + 1), ENT_QUOTES, 'UTF-8'); ?></p>
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

// variants.phpの魔法をここでも使えるようにするよ
// 元の定義を削除してた部分を戻す
function get_variant_key(selected) {
    return Object.values(selected).join('-');
}

function get_variant_image(product, variantKey) {
    const variants = product.variants || {};
    const default_image = product.default_image || (product.images[0] || 'https://placehold.jp/300x300.png');
    if (variants[variantKey] && variants[variantKey].image && variants[variantKey].image !== '') {
        return variants[variantKey].image;
    }
    return default_image;
}

function is_variant_available(product, variantKey) {
    const variants = product.variants || {};
    return variants[variantKey] && !variants[variantKey].sold_out;
}

document.addEventListener('DOMContentLoaded', () => {
    const productSwiper = new Swiper('.productSwiper', {
        loop: false,
        pagination: { el: '.swiper-pagination', clickable: true },
        navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
    });

    const form = document.getElementById('productForm');
    const selects = form.querySelectorAll('.variant-select');
    const priceDisplay = document.getElementById('selected-price');
    const product = <?php echo json_encode($product, JSON_UNESCAPED_UNICODE); ?>;
    const minPrice = <?php echo $min_price ? $min_price : 0; ?>;

    function updatePriceAndImage() {
        const selected = {};
        selects.forEach(select => {
            if (select.value) selected[select.getAttribute('data-attr')] = select.value;
        });
        const variantKey = get_variant_key(selected);
        const variant = product.variants[variantKey];
        const price = variant && !variant.sold_out ? variant.price : null;
        const image = get_variant_image(product, variantKey);

        priceDisplay.textContent = price ? `${price.toLocaleString()}円` : `${minPrice.toLocaleString()}円～`;

        const slides = document.querySelectorAll('.productSwiper .swiper-slide');
        let targetIndex = 0;
        slides.forEach((slide, index) => {
            const imgSrc = slide.querySelector('img').src;
            if (imgSrc.includes(image)) {
                targetIndex = index;
            }
        });
        productSwiper.slideTo(targetIndex);
    }

    selects.forEach(select => select.addEventListener('change', updatePriceAndImage));
    updatePriceAndImage();

    form.addEventListener('submit', function(e) {
        let allSelected = true;
        selects.forEach(select => {
            if (!select.value) allSelected = false;
        });
        if (!allSelected) {
            e.preventDefault();
            alert('全部選んでね！');
            return;
        }
        const selected = {};
        selects.forEach(select => {
            if (select.value) selected[select.getAttribute('data-attr')] = select.value;
        });
        const variantKey = get_variant_key(selected);
        if (!is_variant_available(product, variantKey)) {
            e.preventDefault();
            alert('ごめんね、在庫がないよ…');
        }
    });

    const modalSwiper = new Swiper('.modalSwiper', {
        loop: false,
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
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

        img.addEventListener('click', (e) => {
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
        });
    });
});
</script>
<style>
.price {
    font-size: 1.5rem;
    color: #333;
}
.product-description ul { margin-left: 20px; }
.product-description a { color: #007bff; text-decoration: underline; }
.product-description a:hover { color: #0056b3; }
.productSwiper .swiper-slide img {
    max-height: 500px;
    object-fit: contain;
    cursor: pointer;
}
.modalSwiper .swiper-slide img {
    max-height: 80vh;
    object-fit: contain;
}

/* 修正したCSS */
.swiper {
    width: 100%;
    max-width: 600px;
    height: 600px;
}
.productSwiper .swiper-slide {
    display: flex;
    justify-content: center;
    align-items: center;
}
.productSwiper .swiper-slide img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}
.modalSwiper {
    width: 100%;
    max-width: 800px; /* モーダルは少し大きめに */
    height: 80vh; /* 高さを画面に合わせる */
}
.modalSwiper .swiper-slide {
    display: flex;
    flex-direction: column; /* 縦に並べる */
    justify-content: center;
    align-items: center;
    text-align: center;
}
.modalSwiper .swiper-slide p {
    margin-top: 10px; /* 画像との間隔 */
    font-size: 1rem;
    color: #666;
}
.btn-secondary.disabled {
    background-color: #ccc;
    border-color: #ccc;
    cursor: not-allowed;
}
</style>