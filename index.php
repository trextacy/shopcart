<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();
require_once 'plugins/functions.php';
require_once 'plugins/variants.php';

$base_path = get_base_path();
generate_csrf_token();

// キャッシュを無効化して直接読み込み
$products = load_products();

// 以下は既存のフィルタリングとページング
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
<div class="swiper mySwiper" id="swiper-home">
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

    <div class="container">



        <!-- フィルター -->
        <div class="mb-4  border shadow p-3">
              <div class="d-flex justify-content-between align-items-center mb-2>
                        <h2 class="h4">カテゴリーからも選べます</h2>
                        <select name="sort" class="form-select w-auto d-inline-block" onchange="location.href='index.php?sort='+this.value;">
                            <option value="default" <?php echo $sort === 'default' ? 'selected' : ''; ?>>標準</option>
                            <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>人気順</option>
                        </select>

              </div>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <a href="index.php" class="btn btn-outline-primary <?php echo !$selected_category ? 'active' : ''; ?>">全商品</a>
                            <?php foreach (array_unique(array_filter(array_column($products, 'category'))) as $category): ?>
                                <a href="index.php?category=<?php echo urlencode($category); ?>" class="btn btn-outline-primary <?php echo $selected_category === $category ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars($category); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
        </div>






<!-- 商品グリッド -->
<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4" id="product-grid">
    <?php foreach ($initial_products as $product_id => $product): ?>
        <?php
        $variant_prices = array_column(array_filter($product['variants'] ?? [], fn($v) => isset($v['price']) && is_numeric($v['price']) && !$v['sold_out']), 'price');
        $min_price = !empty($variant_prices) ? min($variant_prices) : ($product['price'] ?? 0);
        $all_sold_out = empty($variant_prices) && empty($product['price']);
        $sold_out_variants = get_sold_out_variants($product); // 在庫切れのバリエーションを取得
        ?>


        <div class="col">
            <div class="card product-card border-0 h-100 position-relative">
                <!-- 画像エリア：4:3に -->
                <div class="position-relative">
                    <a href="product_detail.php?product_id=<?php echo htmlspecialchars($product_id); ?>">
                        <div id="carousel-<?php echo htmlspecialchars($product_id); ?>" class="carousel slide">
                            <div class="carousel-inner" style="aspect-ratio: 4 / 3;">
                                <?php foreach ($product['images'] ?? [] as $i => $img): ?>
                                    <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>" data-image-src="<?php echo htmlspecialchars($img); ?>">
                                        <img src="<?php echo htmlspecialchars(strpos($img, 'http') === 0 ? $img : $base_path . $img); ?>" 
                                             class="d-block w-100 card-img-top object-fit-cover" alt="商品画像" loading="lazy">
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
                    
                    <!-- 商品名：画像の上に白い帯で -->
                    <h5 class="card-title position-absolute top-0 start-0 w-100 p-2 mb-0 bg-white bg-opacity-75 text-dark fw-medium fs-5">
                        <a href="product_detail.php?product_id=<?php echo htmlspecialchars($product_id); ?>" class="text-decoration-none text-dark">
                            <?php echo htmlspecialchars($product['name'] ?? '商品名不明'); ?>
                        </a>
                    </h5>
                    
                    <!-- 価格：画像の右下に白い窓で黒文字 -->
                    <p class="price position-absolute bottom-0 end-0 m-2 p-1 bg-white text-dark fw-bold rounded <?php echo $all_sold_out ? 'sold-out' : ''; ?>" data-min-price="<?php echo $min_price; ?>">
                        <?php echo $all_sold_out ? '売り切れ' : number_format($min_price) . '円～'; ?>
                    </p>
                </div>

                <!-- カード本体 -->
                <div class="card-body text-center d-flex flex-column p-3">
                    <!-- 説明：画像の下に -->
                    <div class="card-text text-muted mb-2 text-truncate">
                        <?php echo htmlspecialchars($product['lead'] ?? mb_substr(strip_tags($product['description'] ?? ''), 0, 20) . '...'); ?>
                    </div>
                    
                    <!-- 在庫切れのバリエーションを表示 -->
                    <?php if (!empty($sold_out_variants)): ?>
                        <p class="text-danger small mb-2">在庫切れ: <?php echo htmlspecialchars(implode(', ', $sold_out_variants)); ?></p>
                    <?php endif; ?>
                    
                    <!-- フォーム：プルダウンとボタンを左右に -->
                    <form method="post" action="cart.php?action=add" onsubmit="return validateForm(this)" class="mt-auto" data-product='<?php echo json_encode($product, JSON_UNESCAPED_UNICODE); ?>'>
                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        
                        <div class="row">
                            <!-- プルダウン：左側（col-6） -->
                            <div class="col-6 align-self-center">
                                <div class="variant-options"><?php echo display_variant_options($product); ?></div>
                            </div>
                            
                            <!-- ボタン：右側（col-6）に固定幅 -->
                            <div class="col-6 d-flex justify-content-end align-self-center">
                                <div class="button-group d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm flex-fill <?php echo $all_sold_out ? 'disabled' : ''; ?>" <?php echo $all_sold_out ? 'disabled' : ''; ?>>
                                        カートに追加
                                    </button>
                                    <a href="product_detail.php?product_id=<?php echo htmlspecialchars($product_id); ?>" class="btn btn-outline-secondary btn-sm flex-fill">
                                        詳細
                                    </a>
                                </div>
                            </div>
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
<script src="<?php echo $base_path; ?>js/common.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const swiper = new Swiper('.mySwiper', {
        loop: true,
        pagination: { el: '.swiper-pagination', clickable: true },
        navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
    });

    const forms = document.querySelectorAll('.product-card form');
    forms.forEach(form => {
        const priceDisplay = form.closest('.product-card').querySelector('.price');
        const carousel = form.closest('.product-card')?.querySelector('.carousel');
        const product = JSON.parse(form.getAttribute('data-product'));
        const minPrice = parseInt(priceDisplay.getAttribute('data-min-price')) || 0;

        initializeVariantButtons(form, priceDisplay, carousel, product, minPrice);

        const selects = form.querySelectorAll('.variant-select');
        selects.forEach(select => {
            if (select.tagName === 'SELECT') {
                select.addEventListener('change', () => updatePriceAndImage(form, priceDisplay, carousel, product, minPrice));
            }
        });

        updatePriceAndImage(form, priceDisplay, carousel, product, minPrice);

        form.addEventListener('submit', function(e) {
            if (!validateForm(form, product)) {
                e.preventDefault();
                return;
            }
            form.querySelector('.btn-primary').classList.add('fly-to-cart');
            setTimeout(() => form.querySelector('.btn-primary').classList.remove('fly-to-cart'), 1000);
        });
    });
});
</script>

