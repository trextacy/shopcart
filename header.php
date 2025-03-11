<?php
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($seo_description ?? $page_description ?? 'SHOPCART - オンラインショッピング', ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="robots" content="index, follow">
    <meta property="og:title" content="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?> - SHOPCART">
    <meta property="og:description" content="<?php echo htmlspecialchars($product['seo_description'] ?? strip_tags($product['description']), ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($product['images'][0] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:url" content="http://localhost/shopcart/product_detail.php?product_id=<?php echo urlencode($product_id); ?>">
    <meta property="og:type" content="product">

    <title><?php echo htmlspecialchars($page_title ?? 'SHOPCART', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="./bscss/custom.css">
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const themeSwitch = document.getElementById('themeSwitch');
        const body = document.body;
        if (localStorage.getItem('theme') === 'dark') {
            body.classList.add('dark-mode');
            themeSwitch.checked = true;
        }
        themeSwitch.addEventListener('change', function () {
            if (this.checked) {
                body.classList.add('dark-mode');
                localStorage.setItem('theme', 'dark');
            } else {
                body.classList.remove('dark-mode');
                localStorage.setItem('theme', 'light');
            }
        });
    });
    </script>
</head>
<body>
    <div class="feather-background">
        <div class="feather feather-1"></div>
        <div class="feather feather-2"></div>
        <div class="feather feather-3"></div>
        <div class="feather feather-4"></div>
        <div class="feather feather-5"></div>
    </div>
<header>
    <nav class="navbar navbar-expand-md navbar-light bg-light fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">SHOPCART</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link active" href="index.php">商品一覧</a></li>
                    <li class="nav-item"><a class="nav-link" href="cart.php">カート</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#categoryModal">カテゴリー</a></li>
                </ul>
                <!-- シンプルにms-3のみで開始 -->
                <div class="search-container ms-3">
                    <i class="bi bi-search search-icon me-2" id="searchToggle"></i>
                    <form method="get" class="search-form" action="index.php">
                        <input type="text" name="search" class="form-control search-input" placeholder="商品を検索してね♪" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    </form>
                </div>
                <div class="form-check form-switch ms-3">
                    <input class="form-check-input" type="checkbox" id="themeSwitch">
                    <label class="form-check-label" for="themeSwitch">ダークモード</label>
                </div>
            </div>
        </div>
    </nav>
</header>
<?php include_once './plugins/search.php'; ?>
<!-- モーダルはそのまま（省略） -->

    <!-- フルサイズカテゴリーメニュー（モーダル） -->
    <div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="categoryModalLabel">カテゴリー</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="container">
                        <div class="row g-4">
                            <?php
                            $categories = array_unique(array_filter(array_map(function($p) { return $p['category'] ?? ''; }, $products)));
                            foreach ($categories as $category):
                                if (empty($category)) continue;
                            ?>
                                <div class="col-md-4">
                                    <h3 class="fw-bold mb-3"><?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <ul class="list-unstyled">
                                        <?php
                                        $category_products = array_filter($products, function($p) use ($category) {
                                            return ($p['category'] ?? '') === $category && ($p['is_public'] ?? true);
                                        });
                                        foreach ($category_products as $id => $prod):
                                        ?>
                                            <li class="mb-2">
                                                <a href="product_detail.php?product_id=<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none"><?php echo htmlspecialchars($prod['name'], ENT_QUOTES, 'UTF-8'); ?></a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endforeach; ?>
                            <!-- その他のリンク -->
                            <div class="col-md-4">
                                <h3 class="fw-bold mb-3">その他</h3>
                                <ul class="list-unstyled">
                                    <li class="mb-2"><a href="index.php" class="text-decoration-none">商品一覧</a></li>
                                    <li class="mb-2"><a href="#" class="text-decoration-none">会社概要</a></li>
                                    <li class="mb-2"><a href="contact.php" class="text-decoration-none">お問い合わせ</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>