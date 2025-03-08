<?php
function render_product_card($product_id, $product, $base_path, $csrf_token) {
    $variant_prices = array_column(array_filter($product['variants'], fn($v) => !$v['sold_out']), 'price');
    $min_price = !empty($variant_prices) ? min($variant_prices) : 0;

    ob_start();
    ?>
    <div class="card product-card border-0 shadow-sm" data-product-id="<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="position-relative">
            <a href="product_detail.php?product_id=<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>">
                <div id="carousel-<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>" class="carousel slide">
                    <div class="carousel-inner">
                        <?php foreach ($product['images'] as $index => $image): ?>
                            <?php $image_src = (strpos($image, 'http') === 0) ? $image : $base_path . $image; ?>
                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>" data-image-src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>">
                                <img src="<?php echo htmlspecialchars($image_src, ENT_QUOTES, 'UTF-8'); ?>" 
                                     class="d-block w-100 card-img-top" 
                                     alt="<?php echo htmlspecialchars($product['image_descriptions'][$index] ?? '商品画像 ' . ($index + 1), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($product['images']) > 1): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#carousel-<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#carousel-<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    <?php endif; ?>
                </div>
            </a>
        </div>
        <div class="card-body text-center d-flex flex-column">
            <h5 class="card-title mb-2">
                <a href="product_detail.php?product_id=<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>" class="text-dark text-decoration-none">
                    <?php echo htmlspecialchars($product['name'] ?? '商品名不明', ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </h5>
            <div class="card-text text-muted mb-2 product-description text-truncate"><?php echo htmlspecialchars($product['lead'] ?? mb_substr(strip_tags($product['description'] ?? ''), 0, 20, 'UTF-8') . '...', ENT_QUOTES, 'UTF-8'); ?></div>
            <p class="price fw-bold mb-2" data-min-price="<?php echo $min_price; ?>"><?php echo $min_price ? number_format($min_price) . '円～' : '価格未定'; ?></p>
            <form method="post" action="cart.php?action=add" onsubmit="return validateForm(this)" class="mt-auto">
                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="variant-options mb-2">
                    <?php echo display_variant_options($product); ?>
                </div>
                <div class="d-flex justify-content-center gap-2 text-nowrap">
                    <button type="submit" class="btn btn-primary btn-sm">カートに追加だよ♪</button>
                    <a href="product_detail.php?product_id=<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary btn-sm">見てみる！</a>
                </div>
            </form>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>