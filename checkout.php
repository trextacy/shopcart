<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();
require_once 'plugins/functions.php';
require_once 'plugins/variants.php';
require_once 'plugins/checkout_helper.php';

$base_path = get_base_path();

if (!file_exists(__DIR__ . '/plugins/checkout_helper.php')) {
    die('エラー：checkout_helper.phpが見つからないよ！');
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// キャッシュで商品データを取得
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

$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = '何か変だよ。もう一度やってみてね。';
    } else {
        $customer_info = process_checkout_data($_POST);
        if (empty($customer_info['name']) || !preg_match('/^\d{7}$/', $customer_info['postal_code'])) {
            $error_message = '名前か郵便番号（7桁数字）を入れてね。';
        } else {
            $_SESSION['customer_info'] = $customer_info;
            header('Location: confirm.php');
            exit;
        }
    }
} elseif (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

$customer_info = $_SESSION['customer_info'] ?? [];
$cart_items = $_SESSION['cart'];
$cart_summary = render_cart_summary($cart_items, $products, $customer_info);
$cart_summary_html = $cart_summary['html'];
$total_amount = number_format($cart_summary['grand_total'] ?? 0);

$page_title = 'お届け先入力 - trextacy.com';
$page_description = 'お届け先を入れるページだよ。';
include 'header.php';
?>

<style>
body { background-color: #F8E1E9; font-family: 'Noto Sans JP', sans-serif; }
.checkout-container { background-color: #fff; border-radius: 10px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); padding: 20px; margin: 20px auto 40px; }
.checkout-header { color: #333; font-size: 1.75rem; margin-bottom: 20px; border-bottom: 2px solid #A2CFFE; padding-bottom: 10px; }
.btn-primary { background-color: #A2CFFE; border-color: #A2CFFE; color: #fff; }
.btn-primary:hover { background-color: #87BFFF; border-color: #87BFFF; }
.form-label { font-weight: 500; color: #555; }
.cart-summary { background-color: #F8F9FA; padding: 15px; border-radius: 8px; }
@media (min-width: 768px) { .checkout-container { max-width: 900px; } }
.fixed-footer { position: sticky; bottom: 0; width: 100%; background-color: #A2CFFE; color: #fff; padding: 10px; text-align: center; font-weight: bold; z-index: 1000; }
.fixed-footer span { font-size: 1.2rem; }
</style>

<div class="container checkout-container">
    <h1 class="checkout-header">お届け先情報入力</h1>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-12 col-md-5 order-md-2">
            <div class="cart-summary">
                <?php echo $cart_summary_html; ?>
            </div>
        </div>
        <div class="col-12 col-md-7 order-md-1">
            <?php echo render_checkout_form($customer_info, $cart_items, $products); ?>
        </div>
    </div>
</div>

<div class="fixed-footer">
    <span>合計金額 <span id="footer-grand-total"><?php echo htmlspecialchars($total_amount); ?></span>円</span>
</div>

<?php include 'footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const cartSummary = document.querySelector('.cart-summary');
    const fixedFooter = document.querySelector('.fixed-footer');
    if (!cartSummary || !fixedFooter) return;

    function checkVisibility() {
        const rect = cartSummary.getBoundingClientRect();
        if (rect.bottom < window.innerHeight * 0.5) {
            fixedFooter.style.display = 'block';
        } else {
            fixedFooter.style.display = 'none';
        }
    }
    window.addEventListener('scroll', checkVisibility);
    checkVisibility();
});
</script>

</body>
</html>