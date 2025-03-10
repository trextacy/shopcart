<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();
require_once 'plugins/functions.php';
require_once 'plugins/order.php';
require_once 'plugins/variants.php';
require_once 'plugins/checkout_helper.php';

if (!isset($_SESSION['customer_info']) || !isset($_SESSION['cart'])) {
    header('Location: checkout.php');
    exit();
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    die('不正なリクエストです。');
}

$customer_info = $_SESSION['customer_info'];
$cart_items = $_SESSION['cart'];
$products = load_products();

$order_number = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
$order_date = date('Y-m-d H:i:s');
$delivery_date = date('Y-m-d', strtotime('+3 days'));

$total_price = 0;
foreach ($cart_items as $item) {
    $product = $products[$item['product_id']] ?? null;
    if ($product && isset($product['variants'][$item['variant']])) {
        $price = $product['variants'][$item['variant']]['price'] ?? 0;
        $total_price += $price * $item['quantity'];
    }
}

$shipping_and_fees = calculate_shipping_and_fees($total_price, $customer_info);
$shipping_fee = $shipping_and_fees['shipping_fee'];
$cod_fee = $shipping_and_fees['cod_fee'];
$grand_total = $total_price + $shipping_fee + $cod_fee;

$order_data = [
    'order_id' => $order_number,
    'order_date' => $order_date,
    'customer_name' => $customer_info['name'],
    'kana' => $customer_info['kana'],
    'postal_code' => $customer_info['postal_code'],
    'prefecture' => $customer_info['prefecture'],
    'address' => $customer_info['address'],
    'building' => $customer_info['building'] ?? '',
    'phone' => $customer_info['phone'],
    'email' => $customer_info['email'],
    'comments' => $customer_info['comments'] ?? '',
    'payment_method' => $customer_info['payment_method'],
    'total_price' => $total_price,
    'shipping_fee' => $shipping_fee,
    'cod_fee' => $cod_fee,
    'grand_total' => $grand_total,
    'items' => [],
    'delivery_date' => $delivery_date,
];

foreach ($cart_items as $item) {
    $product = $products[$item['product_id']] ?? null;
    if ($product && isset($product['variants'][$item['variant']])) {
        $price = $product['variants'][$item['variant']]['price'] ?? 0;
        $order_data['items'][] = [
            'product_id' => $item['product_id'],
            'product_name' => $product['name'],
            'variant' => $item['variant'],
            'quantity' => $item['quantity'],
            'price' => $price,
        ];
    }
}

save_order($order_data);
clear_cart();

$page_title = '注文完了 - trextacy.com';
$page_description = 'ご注文ありがとうございました。';
include 'header.php';
?>

<div class="container">
    <h1 class="mt-4 text-success">ご注文ありがとうございます！</h1>
    <p>ご注文が確定しました。商品の配送までしばらくお待ちください。</p>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">注文情報</h5>
        </div>
        <div class="card-body">
            <p><strong>注文番号：</strong> <?php echo htmlspecialchars($order_number, ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>注文日時：</strong> <?php echo htmlspecialchars($order_date, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">お届け情報</h5>
        </div>
        <div class="card-body">
            <p><strong>お届け先：</strong> <?php echo htmlspecialchars($customer_info['name'], ENT_QUOTES, 'UTF-8'); ?> 様</p>
            <?php echo display_customer_info($customer_info); ?>
            <p><strong>配送予定日：</strong> <?php echo htmlspecialchars($delivery_date, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </div>

    <?php echo render_cart_summary($cart_items, $products, $customer_info); ?>

    <div class="d-flex justify-content-center">
        <a href="index.php" class="btn btn-secondary">トップページに戻る</a>
    </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>