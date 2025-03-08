<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();
require_once 'plugins/functions.php';
require_once 'plugins/variants.php';

generate_csrf_token();
$products = load_products();

// タグフィルタリング
$filtered_products = $products;
$selected_tags = [];
if (isset($_GET) && !empty($_GET)) {
    $tags = array_keys($_GET);
    $selected_tags = explode('+', $tags[0]);
    $filtered_products = array_filter($products, function($product) use ($selected_tags) {
        $product_tags = $product['tags'] ?? [];
        return count(array_intersect($selected_tags, $product_tags)) === count($selected_tags);
    });
}

// タグごとに最新2商品を取得（キー保持）
$tags = ['PCパーツ', '新製品', '周辺機器'];
$products_by_tag = [];
foreach ($tags as $tag) {
    $tagged_products = array_filter($products, fn($p) => in_array($tag, $p['tags'] ?? []));
    usort($tagged_products, fn($a, $b) => strcmp($b['registered_date'], $a['registered_date']));
    $tagged_products = array_slice($tagged_products, 0, 2);
    $products_by_tag[$tag] = [];
    foreach ($tagged_products as $product) {
        foreach ($products as $key => $p) {
            if ($p === $product) {
                $products_by_tag[$tag][$key] = $p; // キー（例: "orange001"）を保持
                break;
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo '<pre>';
    echo "POST Received:\n";
    print_r($_POST);
    echo '</pre>';
    exit;
}

$page_title = '商品一覧 - trextacy.com';
$page_description = '商品の一覧ページです。trextacy.com で購入できる商品をご覧いただけます。';
include 'header.php';
?>

<div class="container-fluid p-0">
    <!-- カルーセル -->
    <div id="myCarousel" class="carousel slide mb-6" data-bs-ride="carousel" data-bs-theme="light">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#myCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#myCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#myCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
        </div>
        <div class="carousel-inner">
            <div class="carousel-item active">
                <svg class="bd-placeholder-img" width="100%" height="400" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" preserveAspectRatio="xMidYMid slice" focusable="false">
                    <rect width="100%" height="100%" fill="var(--bs-secondary-color)"/>
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
                    <rect width="100%" height="100%" fill="var(--bs-secondary-color)"/>
                </svg>
                <div class="container">
                    <div class="carousel-caption">
                        <h1>新製品情報</h1>
                        <p>最新アイテムをいち早くお届けします。</p>
                        <p><a class="btn btn-lg btn-primary" href="#">詳細を見る</a></p>
                    </div>
                </div>
            </div>
            <div class="carousel-item">
                <svg class="bd-placeholder-img" width="100%" height="400" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" preserveAspectRatio="xMidYMid slice" focusable="false">
                    <rect width="100%" height="100%" fill="var(--bs-secondary-color)"/>
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
        <button class="carousel-control-prev" type="button" data-bs-target="#myCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#myCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>

    <!-- マーケティングコンテンツ -->
    <div class="container marketing">
        <div class="row">
            <div class="col-lg-4 text-center">
                <svg class="bd-placeholder-img rounded-circle" width="140" height="140" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder" preserveAspectRatio="xMidYMid slice" focusable="false">
                    <title>Placeholder</title>
                    <rect width="100%" height="100%" fill="var(--bs-secondary-color)"/>
                </svg>
                <h2 class="fw-normal">PCパーツ</h2>
                <p>最新のCPUやGPUを豊富に取り揃えています。</p>
                <p><a class="btn btn-secondary" href="product.php?PCパーツ">詳細を見る »</a></p>
            </div>
            <div class="col-lg-4 text-center">
                <svg class="bd-placeholder-img rounded-circle" width="140" height="140" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder" preserveAspectRatio="xMidYMid slice" focusable="false">
                    <title>Placeholder</title>
                    <rect width="100%" height="100%" fill="var(--bs-secondary-color)"/>
                </svg>
                <h2 class="fw-normal">新製品</h2>
                <p>話題の新アイテムをいち早くチェック。</p>
                <p><a class="btn btn-secondary" href="product.php?新製品">詳細を見る »</a></p>
            </div>
            <div class="col-lg-4 text-center">
                <svg class="bd-placeholder-img rounded-circle" width="140" height="140" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder" preserveAspectRatio="xMidYMid slice" focusable="false">
                    <title>Placeholder</title>
                    <rect width="100%" height="100%" fill="var(--bs-secondary-color)"/>
                </svg>
                <h2 class="fw-normal">周辺機器</h2>
                <p>便利なアクセサリーもお任せください。</p>
                <p><a class="btn btn-secondary" href="product.php?周辺機器">詳細を見る »</a></p>
            </div>
        </div>

        <hr class="featurette-divider">

        <div class="row featurette">
            <div class="col-md-7">
                <h2 class="featurette-heading fw-normal lh-1">最新技術をあなたに <span class="text-body-secondary">驚くべき性能</span></h2>
                <p class="lead">次世代のPCパーツで、あなたの体験をアップグレード。</p>
            </div>
            <div class="col-md-5">
                <svg class="bd-placeholder-img bd-placeholder-img-lg featurette-image img-fluid mx-auto" width="500" height="500" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder: 500x500" preserveAspectRatio="xMidYMid slice" focusable="false">
                    <title>Placeholder</title>
                    <rect width="100%" height="100%" fill="var(--bs-secondary-bg)"/>
                    <text x="50%" y="50%" fill="var(--bs-secondary-color)" dy=".3em">500x500</text>
                </svg>
            </div>
        </div>

        <hr class="featurette-divider">

        <div class="row featurette">
            <div class="col-md-7 order-md-2">
                <h2 class="featurette-heading fw-normal lh-1">使いやすさを追求 <span class="text-body-secondary">快適な操作</span></h2>
                <p class="lead">周辺機器で毎日の作業をよりスムーズに。</p>
            </div>
            <div class="col-md-5 order-md-1">
                <svg class="bd-placeholder-img bd-placeholder-img-lg featurette-image img-fluid mx-auto" width="500" height="500" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder: 500x500" preserveAspectRatio="xMidYMid slice" focusable="false">
                    <title>Placeholder</title>
                    <rect width="100%" height="100%" fill="var(--bs-secondary-bg)"/>
                    <text x="50%" y="50%" fill="var(--bs-secondary-color)" dy=".3em">500x500</text>
                </svg>
            </div>
        </div>

        <hr class="featurette-divider">
    </div>
</div>

<!-- 商品一覧 -->
<div class="container">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.html">ホーム</a></li>
            <li class="breadcrumb-item active" aria-current="page">商品一覧</li>
        </ol>
    </nav>

    <h1 class="mt-4 mb-3">商品一覧</h1>

    <!-- タグごとの最新商品 -->
    <?php if (empty($selected_tags)): ?>
        <?php foreach ($products_by_tag as $tag => $tagged_products): ?>
            <div class="mb-5">
                <h2><?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?></h2>
                <div class="row">
                    <?php foreach ($tagged_products as $product_id => $product): ?>
                        <?php
                        $variant_prices = array_column(array_filter($product['variants'], fn($v) => !$v['sold_out']), 'price');
                        $min_price = !empty($variant_prices) ? min($variant_prices) : 0;
                        ?>
                        <div class="col-md-6 mb-4">
                            <div class="card product-card">
                                <a href="product_detail.php?product_id=<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>">
                                    <img src="<?php echo htmlspecialchars($product['images'][0] ?? 'https://placehold.jp/300x300.png', ENT_QUOTES, 'UTF-8'); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name'] ?? '商品名不明', ENT_QUOTES, 'UTF-8'); ?>" 
                                         class="card-img-top product-image">
                                </a>
                                <div class="card-body">
                                    <h5 class="card-title product-name">
                                        <a href="product_detail.php?product_id=<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($product['name'] ?? '商品名不明', ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </h5>
                                    <p class="card-text product-description">
                                        <?php echo htmlspecialchars($product['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                    </p>
                                    <p class="price-display">価格: <span id="variant-price-<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo $min_price ? number_format($min_price) . '円～' : '価格未定'; ?>
                                    </span></p>
                                    <form method="post" action="cart.php?action=add" onsubmit="return validateForm(this)">
                                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo display_variant_options($product); ?>
                                        <div class="mb-3">
                                            <label for="quantity" class="form-label">数量</label>
                                            <input type="number" class="form-control" name="quantity" value="1" min="1" max="100" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">カートに追加</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a href="product.php?<?php echo urlencode($tag); ?>" class="btn btn-outline-primary">もっと見る</a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- フィルタリングされた全商品 -->
    <?php if (!empty($selected_tags)): ?>
        <h2 class="mt-5">すべての <?php echo htmlspecialchars(implode(' + ', $selected_tags), ENT_QUOTES, 'UTF-8'); ?> 商品</h2>
        <div class="row">
            <?php foreach ($filtered_products as $product_id => $product): ?>
                <?php
                $variant_prices = array_column(array_filter($product['variants'], fn($v) => !$v['sold_out']), 'price');
                $min_price = !empty($variant_prices) ? min($variant_prices) : 0;
                ?>
                <div class="col-md-4 mb-4">
                    <div class="card product-card">
                        <a href="product_detail.php?product_id=<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>">
                            <img src="<?php echo htmlspecialchars($product['images'][0] ?? 'https://placehold.jp/300x300.png', ENT_QUOTES, 'UTF-8'); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name'] ?? '商品名不明', ENT_QUOTES, 'UTF-8'); ?>" 
                                 class="card-img-top product-image">
                        </a>
                        <div class="card-body">
                            <h5 class="card-title product-name">
                                <a href="product_detail.php?product_id=<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($product['name'] ?? '商品名不明', ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </h5>
                            <p class="card-text product-description">
                                <?php echo htmlspecialchars($product['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                            <p class="price-display">価格: <span id="variant-price-<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo $min_price ? number_format($min_price) . '円～' : '価格未定'; ?>
                            </span></p>
                            <form method="post" action="cart.php?action=add" onsubmit="return validateForm(this)">
                                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo display_variant_options($product); ?>
                                <div class="mb-3">
                                    <label for="quantity" class="form-label">数量</label>
                                    <input type="number" class="form-control" name="quantity" value="1" min="1" max="100" required>
                                </div>
                                <button type="submit" class="btn btn-primary">カートに追加</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function updatePrice(form) {
    const selects = form.querySelectorAll('.variant-select');
    const productId = form.querySelector('input[name="product_id"]').value;
    const priceDisplay = document.getElementById('variant-price-' + productId);
    const variants = <?php echo json_encode($products, JSON_UNESCAPED_UNICODE); ?>[productId].variants;
    const minPrice = variants ? Math.min(...Object.values(variants).filter(v => !v.sold_out).map(v => v.price)) : 0;

    const selected = {};
    selects.forEach(select => {
        if (select.value) selected[select.getAttribute('data-attr')] = select.value;
    });
    const variantKey = Object.values(selected).join('-');
    const price = variants[variantKey] && !variants[variantKey].sold_out ? variants[variantKey].price : null;
    priceDisplay.textContent = price ? `${price.toLocaleString()}円` : `${minPrice.toLocaleString()}円～`;
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
            select.addEventListener('change', () => updatePrice(form));
        });
        updatePrice(form);
    });
});
</script>

<?php include 'footer.php'; ?>