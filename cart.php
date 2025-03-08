<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();
require_once './plugins/functions.php';
require_once './plugins/variants.php';
require_once './plugins/checkout_helper.php';
require_once './plugins/cart_logic.php';

$base_path = get_base_path();

// キャッシュ処理（エラー対策付き）
$cache_dir = 'cache';
$cache_file = "$cache_dir/products_cache.json";
if (!is_dir($cache_dir)) {
    if (!mkdir($cache_dir, 0755, true)) {
        error_log('Failed to create cache directory: ' . $cache_dir);
        $products = load_products(); // フォールバック
    }
}
if (!isset($products) && is_writable($cache_dir)) {
    if (file_exists($cache_file) && (time() - filemtime($cache_file) < 86400)) { // 24時間キャッシュ
        $products = json_decode(file_get_contents($cache_file), true);
        if ($products === null) {
            error_log('Failed to decode cache file: ' . $cache_file);
            $products = load_products();
        }
    } else {
        $products = load_products();
        if (!file_put_contents($cache_file, json_encode($products))) {
            error_log('Failed to write cache file: ' . $cache_file);
        }
    }
} else {
    $products = load_products();
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 商品追加処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'add') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = '不正なリクエストです。';
    } else {
        $product_id = $_POST['product_id'] ?? null;
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));
        $variant_key = get_variant_key($_POST['variant'] ?? []);
        if ($product_id && isset($products[$product_id]) && $variant_key) {
            if (add_to_cart($product_id, $quantity, $variant_key, $_SESSION['cart'], $products)) {
                $_SESSION['success'] = '商品をカートに追加しました♪';
            } else {
                $_SESSION['error'] = '在庫切れか商品が見つかりません。';
            }
        } else {
            $_SESSION['error'] = '商品情報が正しくありません。';
        }
    }
    header('Location: cart.php');
    exit();
}

// 商品削除処理
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['index'])) {
    $index = (int)$_GET['index'];
    if (remove_from_cart($index, $_SESSION['cart'])) {
        $_SESSION['success'] = 'カートから削除しました。';
    }
    header('Location: cart.php');
    exit();
}

// 数量更新処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'update') {
    $index = (int)($_POST['index'] ?? -1);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    if (isset($_SESSION['cart'][$index])) {
        $item = $_SESSION['cart'][$index];
        if (is_variant_available($products[$item['product_id']], $item['variant'])) {
            $_SESSION['cart'][$index]['quantity'] = $quantity;
            $_SESSION['success'] = '数量を更新しました。';
        } else {
            $_SESSION['error'] = '在庫切れのため更新できません。';
        }
    }
    header('Location: cart.php');
    exit();
}

// 表示処理
$page_title = 'カート - trextacy.com';
$page_description = 'カートの中身を確認してね。';
include 'header.php';
?>

<div class="d-flex flex-column min-vh-100">
    <div class="container cart-container flex-grow-1 my-5" style="background: #F8E1E9; padding: 20px;">
        <h1 class="cart-title">カートの中身</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (empty($_SESSION['cart'])): ?>
            <div class="text-center py-5">
                <p class="text-muted fs-4">カートに何も入ってないよ～</p>
                <a href="index.php" class="btn btn-primary btn-lg shadow-sm">商品を見に行く</a>
            </div>
<?php else: ?>
    <ul class="list-group mb-4">
        <?php foreach ($_SESSION['cart'] as $index => $item): ?>
            <?php
            $product = $products[$item['product_id']] ?? null;
            if ($product && isset($product['variants'][$item['variant']])):
                $variant_image = get_variant_image($product, $item['variant']);
                $image_src = strpos($variant_image, 'http') === 0 ? $variant_image : $base_path . $variant_image;
            ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <img src="<?php echo htmlspecialchars($image_src); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         class="img-thumbnail me-3" style="width: 75px; height: 75px; border-radius: 8px;" loading="lazy">
                    <div>
                        <h6 class="my-0"><?php echo htmlspecialchars($product['name']); ?></h6>
                        <small class="text-muted"><?php echo htmlspecialchars($item['variant']); ?></small>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <form method="post" action="cart.php?action=update" class="d-inline-flex align-items-center me-3">
                        <input type="hidden" name="index" value="<?php echo $index; ?>">
                        <input type="number" name="quantity" min="1" value="<?php echo htmlspecialchars($item['quantity']); ?>" 
                               class="form-control qty-input" style="width: 70px; color: #333;">
                    </form>
                    <a href="cart.php?action=remove&index=<?php echo $index; ?>" 
                       class="btn btn-danger btn-sm remove-item">削除</a>
                </div>
            </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
    <?php 
    $cart_summary = render_cart_summary($_SESSION['cart'], $products, [], false); // 削除ボタン非表示
    echo $cart_summary['html']; 
    ?>
    <div class="text-end mt-3">
        <a href="checkout.php" class="btn btn-primary btn-lg shadow-sm <?php echo empty($_SESSION['cart']) ? 'disabled' : ''; ?>" 
           <?php echo empty($_SESSION['cart']) ? 'disabled' : ''; ?>>購入手続きへ</a>
    </div>
<?php endif; ?>
    </div>
    <?php include 'footer.php'; ?>
</div>

<style>
.btn-primary { background: #A2CFFE; border: none; padding: 15px 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
.btn-danger { padding: 5px 15px; }
.cart-title { font-size: 1.75rem; color: #333; }
.qty-input { margin-right: 10px; }
@media (max-width: 576px) { .btn-lg { font-size: 1rem; padding: 10px 20px; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 数量更新
    document.querySelectorAll('.qty-input').forEach(input => {
        input.addEventListener('change', function() {
            const form = this.closest('form');
            if (this.value < 1) this.value = 1; // 最小値ガード
            form.submit();
        });
    });

    // 削除確認
    document.querySelectorAll('.remove-item').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('本当に削除しますか？')) {
                window.location = this.href;
            }
        });
    });

    // 通知アニメーション
    const successAlert = document.querySelector('.alert-success');
    if (successAlert) {
        setTimeout(() => successAlert.classList.add('fade-out'), 2000);
    }
});
</script>