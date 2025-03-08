<?php include 'header.php'; ?>
<div class="d-flex flex-column min-vh-100">
    <main class="flex-grow-1">
        <div class="swiper mySwiper mb-4">
            <div class="swiper-wrapper">
                <div class="swiper-slide">
                    <svg class="d-block w-100" width="100%" height="400" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
                        <defs><linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" style="stop-color:#F8E1E9;stop-opacity:1" /><stop offset="100%" style="stop-color:#A2CFFE;stop-opacity:1" /></linearGradient></defs>
                        <rect width="100%" height="100%" fill="url(#grad1)" />
                    </svg>
                    <div class="swiper-caption text-start">
                        <h1>ようこそ trextacy.com へ</h1>
                        <p>プリキュアみたいに楽しいお買い物♪</p>
                        <a href="#" class="btn btn-primary">今すぐ見てみよう！</a>
                    </div>
                </div>
                <div class="swiper-slide">
                    <svg class="d-block w-100" width="100%" height="400" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
                        <defs><linearGradient id="grad2" x1="0%" y1="100%" x2="100%" y2="0%"><stop offset="0%" style="stop-color:#F8E1E9;stop-opacity:1" /><stop offset="100%" style="stop-color:#A2CFFE;stop-opacity:1" /></linearGradient></defs>
                        <rect width="100%" height="100%" fill="url(#grad2)" />
                    </svg>
                    <div class="swiper-caption text-center">
                        <h1>新商品だよ！</h1>
                        <p>キラキラなアイテムがいっぱい！</p>
                        <a href="#" class="btn btn-primary">チェックするよ～</a>
                    </div>
                </div>
                <div class="swiper-slide">
                    <svg class="d-block w-100" width="100%" height="400" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
                        <defs><linearGradient id="grad3" x1="100%" y1="0%" x2="0%" y2="100%"><stop offset="0%" style="stop-color:#FFD1DC;stop-opacity:1" /><stop offset="100%" style="stop-color:#87CEEB;stop-opacity:1" /></linearGradient></defs>
                        <rect width="100%" height="100%" fill="url(#grad3)" />
                    </svg>
                    <div class="swiper-caption text-end">
                        <h1>特別キャンペーン</h1>
                        <p>今だけのお得なオファーをお見逃しなく。</p>
                        <a href="#" class="btn btn-primary">キャンペーンを見る</a>
                    </div>
                </div>
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
        </div>

        <div class="container">
            <div class="mb-4">
                <h2 class="h4">カテゴリーだよ♪</h2>
                <div class="d-flex flex-wrap gap-2">
                    <a href="index.php" class="btn btn-outline-primary <?php echo empty($selected_category) ? 'active' : ''; ?>">ぜんぶ</a>
                    <?php
                    $categories = array_unique(array_filter(array_map(function($p) { return $p['category'] ?? ''; }, $products)));
                    foreach ($categories as $category):
                        if (empty($category)) continue;
                    ?>
                        <a href="index.php?category=<?php echo urlencode($category); ?>" class="btn btn-outline-primary <?php echo $selected_category === $category ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 gx-3 gy-4" id="product-grid">
                <?php foreach ($initial_products as $product_id => $product): ?>
                    <div class="col">
                        <?php echo render_product_card($product_id, $product, $base_path, $_SESSION['csrf_token']); ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div id="loading" class="text-center my-4" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">読み込み中...</span>
                </div>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>
</div>

<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
<script>
// (JavaScript部分は変更なし、前回のまま)
</script>
<link rel="stylesheet" href="styles/custom.css">
<style>
/* (CSS部分は`styles/custom.css`に移動、前回のまま残さない)
*/
</style>