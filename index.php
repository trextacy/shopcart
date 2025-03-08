<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();
require_once 'plugins/functions.php';
require_once 'plugins/variants.php';

$base_path = get_base_path();
generate_csrf_token();

// キャッシュで商品読み込み
$cache_file = 'cache/products_cache.json';
if (file_exists($cache_file) && (time() - filemtime($cache_file) < 86400)) {
    $products = json_decode(file_get_contents($cache_file), true);
    if ($products === null) {
        error_log('Failed to decode cache: ' . $cache_file);
        $products = load_products();
    }
} else {
    $products = load_products();
    if (is_writable('cache') && !file_put_contents($cache_file, json_encode($products))) {
        error_log('Failed to write cache: ' . $cache_file);
    }
}

// フィルタリング
$filtered_products = array_filter($products, fn($p) => isset($p['is_public']) ? $p['is_public'] : true);
$selected_category = $_GET['category'] ?? '';
if ($selected_category) {
    $filtered_products = array_filter($filtered_products, fn($p) => ($p['category'] ?? '') === $selected_category);
}
$tags = isset($_GET['tags']) && !empty($_GET['tags']) ? explode('+', $_GET['tags']) : [];
if ($tags) {
    $filtered_products = array_filter($filtered_products, fn($p) => count(array_intersect($tags, $p['tags'] ?? [])) === count($tags));
}
$search_query = trim($_GET['search'] ?? '');
if ($search_query) {
    $filtered_products = array_filter($filtered_products, fn($p) => stripos($p['name'] ?? '', $search_query) !== false || stripos($p['description'] ?? '', $search_query) !== false);
}

// ソート（人気順を仮にランダムで）
$sort = $_GET['sort'] ?? 'default';
if ($sort === 'popular') {
    $filtered_products = array_keys($filtered_products);
    shuffle($filtered_products);
    $filtered_products = array_intersect_key($products, array_flip($filtered_products));
}

// ページング
$per_page = 12; // 3列×4行でキレイに
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;
$initial_products = array_slice($filtered_products, $offset, $per_page, true);
$total_products = count($filtered_products);
$total_pages = ceil($total_products / $per_page);

$page_title = '商品一覧 - trextacy.com';
$page_description = 'trextacy.comの可愛い商品をチェックしてね♪';
include 'header.php';
?>

<style>
/* 既存のインラインスタイル（必要最小限に残す、アニメーション用） */
.fly-to-cart { animation: fly 1s ease-out; }
@keyframes fly {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(0.5) translate(50vw, -50vh); opacity: 0.5; }
    100% { transform: scale(0) translate(100vw, -100vh); opacity: 0; }
}
</style>

<div class="d-flex flex-column min-vh-100">
<main class="flex-grow-1">
    <!-- Swiperカルーセル -->
    <div class="container">
        <div class="swiper mySwiper">
            <div class="swiper-wrapper">
                <div class="swiper-slide">
                    <img src="https://placehold.jp//1200x400?text=新商品登場" class="w-100" alt="新商品">
                    <div class="swiper-caption text-start px-4">
                        <h1>新商品登場！</h1>
                        <p>キラキラアイテムがいっぱい♪</p>
                        <a href="#product-grid" class="btn btn-primary">今すぐチェック</a>
                    </div>
                </div>
                <div class="swiper-slide">
                    <img src="https://placehold.jp//1200x400?text=キャンペーン" class="w-100" alt="キャンペーン">
                    <div class="swiper-caption text-center">
                        <h1>特別キャンペーン</h1>
                        <p>今だけのお得なオファー！</p>
                        <a href="#product-grid" class="btn btn-primary">見てみる</a>
                    </div>
                </div>
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
        </div>
    </div>

    <div class="container">
        <!-- フィルター -->
        <div class="mb-4">
            <h2 class="h4">カテゴリーとソート</h2>
            <div class="d-flex flex-wrap gap-2 mb-3">
                <a href="index.php" class="btn btn-outline-primary <?php echo !$selected_category ? 'active' : ''; ?>">ぜんぶ</a>
                <?php foreach (array_unique(array_filter(array_column($products, 'category'))) as $category): ?>
                    <a href="index.php?category=<?php echo urlencode($category); ?>" class="btn btn-outline-primary <?php echo $selected_category === $category ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($category); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <select name="sort" class="form-select w-auto d-inline-block" onchange="location.href='index.php?sort='+this.value;">
                <option value="default" <?php echo $sort === 'default' ? 'selected' : ''; ?>>標準</option>
                <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>人気順</option>
            </select>
        </div>

        <!-- 商品グリッド -->
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4" id="product-grid">
            <?php foreach ($initial_products as $product_id => $product): ?>
                <?php
                $variant_prices = array_column(array_filter($product['variants'] ?? [], fn($v) => isset($v['price']) && is_numeric($v['price']) && !$v['sold_out']), 'price');
                $min_price = !empty($variant_prices) ? min($variant_prices) : ($product['price'] ?? 0);
                $all_sold_out = empty($variant_prices) && empty($product['price']);
                ?>
                <div class="col">
                    <div class="card product-card border-0 h-100">
                        <a href="product_detail.php?product_id=<?php echo htmlspecialchars($product_id); ?>">
                            <div id="carousel-<?php echo htmlspecialchars($product_id); ?>" class="carousel slide">
                                <div class="carousel-inner">
                                    <?php foreach ($product['images'] ?? [] as $i => $img): ?>
                                        <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>" data-image-src="<?php echo htmlspecialchars($img); ?>">
                                            <img src="<?php echo htmlspecialchars(strpos($img, 'http') === 0 ? $img : $base_path . $img); ?>" 
                                                 class="d-block w-100 card-img-top" alt="商品画像" loading="lazy">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php if (count($product['images'] ?? []) > 1): ?>
                                    <button class="carousel-control-prev" type="button" data-bs-target="#carousel-<?php echo htmlspecialchars($product_id); ?>" data-bs-slide="prev">
                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    </button>
                                    <button class="carousel-control-next" type="button" data-bs-target="#carousel-<?php echo htmlspecialchars($product_id); ?>" data-bs-slide="next">
                                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="card-body text-center d-flex flex-column">
                            <h5 class="card-title mb-2">
                                <a href="product_detail.php?product_id=<?php echo htmlspecialchars($product_id); ?>" class="text-dark text-decoration-none">
                                    <?php echo htmlspecialchars($product['name'] ?? '商品名不明'); ?>
                                </a>
                            </h5>
                            <div class="card-text text-muted mb-2 text-truncate"><?php echo htmlspecialchars($product['lead'] ?? mb_substr(strip_tags($product['description'] ?? ''), 0, 20) . '...'); ?></div>
                            <p class="price fw-bold mb-2 <?php echo $all_sold_out ? 'sold-out' : ''; ?>" data-min-price="<?php echo $min_price; ?>">
                                <?php echo $all_sold_out ? '売り切れ' : number_format($min_price) . '円～'; ?>
                            </p>
                            <form method="post" action="cart.php?action=add" onsubmit="return validateForm(this)" class="mt-auto" data-product='<?php echo json_encode($product, JSON_UNESCAPED_UNICODE); ?>'>
                                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id); ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <div class="variant-options mb-2"><?php echo display_variant_options($product); ?></div>
                                <div class="d-flex justify-content-center gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm <?php echo $all_sold_out ? 'disabled' : ''; ?>" <?php echo $all_sold_out ? 'disabled' : ''; ?>>カートに追加</button>
                                    <a href="product_detail.php?product_id=<?php echo htmlspecialchars($product_id); ?>" class="btn btn-outline-secondary btn-sm">詳細</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ページング -->
        <?php if ($total_pages > 1): ?>
            <nav class="text-center my-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item"><a class="page-link" href="index.php?page=<?php echo $page - 1; ?>&category=<?php echo urlencode($selected_category); ?>&tags=<?php echo urlencode($_GET['tags'] ?? ''); ?>&search=<?php echo urlencode($search_query); ?>">前</a></li>
                    <?php endif; ?>
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="index.php?page=<?php echo $i; ?>&category=<?php echo urlencode($selected_category); ?>&tags=<?php echo urlencode($_GET['tags'] ?? ''); ?>&search=<?php echo urlencode($search_query); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item"><a class="page-link" href="index.php?page=<?php echo $page + 1; ?>&category=<?php echo urlencode($selected_category); ?>&tags=<?php echo urlencode($_GET['tags'] ?? ''); ?>&search=<?php echo urlencode($search_query); ?>">次</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</main>
<?php include 'footer.php'; ?>
</div>

<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
<script>
const swiper = new Swiper('.mySwiper', {
    loop: true,
    pagination: { el: '.swiper-pagination', clickable: true },
    navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
});

function get_variant_image(product, variantKey) {
    const variants = product.variants || {};
    return variants[variantKey]?.image || product.images?.[0] || 'https://placehold.jp/300x300.png';
}

function updatePriceAndImage(form) {
    const selects = form.querySelectorAll('.variant-select');
    const product = JSON.parse(form.getAttribute('data-product'));
    const priceDisplay = form.closest('.product-card').querySelector('.price');
    const carousel = form.closest('.product-card').querySelector('.carousel');
    const selected = {};
    selects.forEach(s => { if (s.value) selected[s.getAttribute('data-attr')] = s.value; });
    const variantKey = Object.values(selected).join('-');
    const variant = product.variants?.[variantKey];
    const price = variant && !variant.sold_out ? variant.price : null;
    const minPrice = parseInt(priceDisplay.getAttribute('data-min-price')) || 0;
    priceDisplay.textContent = price ? `${price.toLocaleString()}円` : `${minPrice.toLocaleString()}円～`;
    let targetIndex = 0; // 関数スコープで定義
    if (carousel) {
        const items = carousel.querySelectorAll('.carousel-item');
        items.forEach((item, i) => { 
            if (item.getAttribute('data-image-src') === get_variant_image(product, variantKey)) {
                targetIndex = i;
            }
        });
        bootstrap.Carousel.getOrCreateInstance(carousel, { interval: false }).to(targetIndex);
    }
    console.log(`Updated form: VariantKey=${variantKey}, Price=${price || minPrice}, CarouselIndex=${targetIndex}`);
}

function validateForm(form) {
    const selects = form.querySelectorAll('.variant-select');
    if (Array.from(selects).some(s => !s.value)) {
        alert('すべてのオプションを選んでね！');
        return false;
    }
    const product = JSON.parse(form.getAttribute('data-product'));
    const variantKey = getVariantKey(form);
    if (!product.variants?.[variantKey] || product.variants[variantKey].sold_out) {
        alert('ごめんね、在庫がないよ…');
        return false;
    }
    form.querySelector('.btn-primary').classList.add('fly-to-cart');
    setTimeout(() => form.querySelector('.btn-primary').classList.remove('fly-to-cart'), 1000);
    return true;
}

function getVariantKey(form) {
    const selects = form.querySelectorAll('.variant-select');
    const selected = {};
    selects.forEach(s => { if (s.value) selected[s.getAttribute('data-attr')] = s.value; });
    return Object.values(selected).join('-');
}

// ページ読み込み時に各フォームを初期化し、イベントリスナーを設定
document.addEventListener('DOMContentLoaded', () => {
    console.log('Initializing variant options');
    const forms = document.querySelectorAll('.product-card form');
    forms.forEach(form => {
        const selects = form.querySelectorAll('.variant-select');
        // 初期表示を更新
        updatePriceAndImage(form);
        // 各<select>にchangeイベントリスナーを追加
        selects.forEach(select => {
            select.addEventListener('change', () => {
                console.log(`Select changed: ${select.getAttribute('data-attr')} = ${select.value}`);
                updatePriceAndImage(form);
            });
        });
    });
});
</script>