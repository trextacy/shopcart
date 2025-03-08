<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();
require_once 'plugins/functions.php';
require_once 'plugins/variants.php';
require_once 'plugins/checkout_helper.php';

if (!isset($_SESSION['customer_info']) || !isset($_SESSION['cart'])) {
    header('Location: checkout.php');
    exit();
}

$customer_info = $_SESSION['customer_info'];
$cart_items = $_SESSION['cart'];
$products = load_products();

$page_title = '確認画面 - trextacy.com';
$page_description = '注文内容の確認ページです。';
include 'header.php';
?>

<div class="container my-4">
    <h1 class="mt-4 mb-3">注文内容確認</h1>

    <div class="row g-4">
        <!-- お届け先情報 -->
        <div class="col-12 col-md-7">
            <h4 class="mb-3">お届け先情報</h4>
            <?php echo display_customer_info($customer_info); ?>
        </div>

        <!-- カート内容 -->
        <div class="col-12 col-md-5">
            <?php 
            $cart_summary = render_cart_summary($cart_items, $products, $customer_info);
            echo $cart_summary['html'];
            ?>
        </div>
    </div>

    <div class="mt-4 text-center">
        <form action="complete.php" method="post" class="d-inline">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" class="btn btn-success">注文を確定する</button>
        </form>
        <a href="checkout.php" class="btn btn-secondary ms-2">お届け先・注文内容を修正する</a>
    </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>