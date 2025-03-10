<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();
require_once 'plugins/functions.php';
require_once 'plugins/variants.php';
require_once 'plugins/checkout_helper.php';

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['customer_info'] = process_checkout_data($_POST);
        header('Location: confirm.php');
        exit;
    } else {
        $error_message = '不正なリクエストです。もう一度入力してください。';
    }
}

$customer_info = $_SESSION['customer_info'] ?? [];
$cart_items = $_SESSION['cart'];
$products = load_products();

$page_title = 'お届け先入力 - trextacy.com';
$page_description = 'お届け先情報の入力ページです。';
include 'header.php';
?>

<div class="container">
    <h1 class="mt-4 mb-3">お届け先情報入力</h1>
    <?php if ($error_message): ?>
        <p class="text-danger"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <div class="row">
        <div class="col-12 col-md-6 order-md-2 mb-4">
            <?php echo render_cart_summary($cart_items, $products, $customer_info); ?>
        </div>
        <div class="col-12 col-md-6 order-md-1">
            <?php echo render_checkout_form($customer_info); ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>