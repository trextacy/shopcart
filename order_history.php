<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();
require_once 'plugins/functions.php';
require_once 'plugins/variants.php';

$config = require_once 'config/admin-config.php';

// 管理者認証
if (!isset($_SESSION['history_authenticated']) || $_SESSION['history_authenticated'] !== true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if (password_verify($_POST['password'], $config['admin_password'])) {
            $_SESSION['history_authenticated'] = true;
        } else {
            $error = 'パスワードが正しくありません。';
        }
    }
    if (!isset($_SESSION['history_authenticated']) || $_SESSION['history_authenticated'] !== true) {
        ?>
        <!DOCTYPE html>
        <html lang="ja">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>認証 - 注文履歴</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
        </head>
        <body>
            <div class="container mt-5">
                <h2>注文履歴閲覧のための認証</h2>
                <?php if (isset($error)) echo "<p class='text-danger'>$error</p>"; ?>
                <form method="post">
                    <div class="mb-3">
                        <label for="password" class="form-label">管理者パスワード</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">認証</button>
                </form>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        </body>
        </html>
        <?php
        exit;
    }
}

if (file_exists('orders.json')) {
    $orders = json_decode(file_get_contents('orders.json'), true);
    if ($orders === null) {
        echo "<p class='text-danger'>注文データの読み込みに失敗しました。JSON形式を確認してください。</p>";
        $orders = [];
    }
} else {
    $orders = [];
}

$products = load_products();
$base_path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/';

$page_title = '注文履歴 - trextacy.com';
$page_description = '過去の注文履歴をご覧いただけます。';
include 'header.php';
?>

<div class="container">
    <h1 class="mt-4">注文履歴</h1>

    <?php if (empty($orders)): ?>
        <p>まだ注文履歴はありません。</p>
    <?php else: ?>
        <div class="row">
            <?php foreach ($orders as $order): ?>
                <div class="col-12 col-md-6 mb-3 border">
                    <div class="list-group-item">
                        <h5 class="mb-2">注文番号: <?php echo htmlspecialchars($order['order_id'] ?? '不明', ENT_QUOTES, 'UTF-8'); ?></h5>
<table class="table table-striped">
    <tbody>
        <tr>
            <th scope="row">注文日時</th>
            <td><?php echo htmlspecialchars($order['order_date'] ?? '不明', ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <th scope="row">お届け先</th>
            <td><?php echo htmlspecialchars($order['customer_name'] ?? '不明', ENT_QUOTES, 'UTF-8'); ?> 様</td>
        </tr>
        <tr>
            <th scope="row">支払い方法</th>
            <td><?php 
                if (isset($order['payment_method'])) {
                    echo htmlspecialchars($config['payment_methods'][$order['payment_method']] ?? '不明', ENT_QUOTES, 'UTF-8');
                } else {
                    echo '不明';
                }
                ?></td>
        </tr>
        <tr>
            <th scope="row">配送日時</th>
            <td><?php 
                $delivery_times = [
                    'morning' => '午前中',
                    '14-16' => '14:00-16:00',
                    '16-18' => '16:00-18:00',
                    '18-20' => '18:00-20:00'
                ];
                echo htmlspecialchars($delivery_times[$order['delivery_time'] ?? ''] ?? '未指定', ENT_QUOTES, 'UTF-8'); 
                ?></td>
        </tr>
        <tr>
            <th scope="row">カタログ冊子</th>
            <td><?php echo isset($order['catalog_request']) && $order['catalog_request'] ? '希望する' : '希望しない'; ?></td>
        </tr>
        <tr>
            <th scope="row">合計金額</th>
            <td><?php echo number_format($order['total_price'] ?? 0); ?>円（税込）</td>
        </tr>
        <tr>
            <th scope="row">配送予定日</th>
            <td><?php echo htmlspecialchars($order['delivery_date'] ?? '不明', ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
    </tbody>
</table>
                        
                        <h6>注文内容</h6>
                        <ul class="list-group">
                            <?php 
                            if (isset($order['items']) && is_array($order['items'])):
                                foreach ($order['items'] as $item): 
                                    $product_id = $item['product_id'] ?? '';
                                    $product = $products[$product_id] ?? null;
                                    // バリアントを柔軟に取得
                                    $variant = $item['variant'] ?? ($item['size'] ?? '');
                                    if (empty($variant) && isset($item['attributes']) && is_array($item['attributes'])) {
                                        $variant = implode('-', array_values($item['attributes']));
                                    }
                                    $variant = $variant ?: '不明';
                                    $variant_image = $product ? get_variant_image($product, $variant) : 'https://placehold.jp/60x60.png';
                                    $image_src = (strpos($variant_image, 'http') === 0) ? $variant_image : $base_path . $variant_image;
                            ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo htmlspecialchars($image_src, ENT_QUOTES, 'UTF-8'); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['product_name'] ?? '不明', ENT_QUOTES, 'UTF-8'); ?>" 
                                                 class="img-thumbnail me-2" style="width: 60px; height: 60px;">
                                            <div>
                                                <strong><?php echo htmlspecialchars($item['product_name'] ?? '不明', ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                                <small>バリアント: <?php echo htmlspecialchars($variant, ENT_QUOTES, 'UTF-8'); ?> | 数量: <?php echo $item['quantity'] ?? 0; ?></small>
                                            </div>
                                        </div>
                                        <span class="text-muted"><?php echo number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 0)); ?>円</span>
                                    </li>
                                <?php endforeach;
                            else:
                                echo '<li class="list-group-item">注文内容がありません。</li>';
                            endif;
                            ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-center mt-4">
        <a href="index.php" class="btn btn-secondary">トップページに戻る</a>
    </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>