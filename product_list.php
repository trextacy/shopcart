<?php
session_start();
require_once 'plugins/functions.php';
require_once 'plugins/product_admin_logic.php';
$config = require_once 'config/admin-config.php';

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if (password_verify($_POST['password'], $config['admin_password'])) {
            $_SESSION['authenticated'] = true;
        } else {
            $error = 'パスワードが正しくありません。';
        }
    }
    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        ?>
        <!DOCTYPE html>
        <html lang="ja">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>認証 - trextacy.com</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
        </head>
        <body>
            <div class="container mt-5">
                <h2>認証が必要だよ♪</h2>
                <?php if (isset($error)) echo "<p class='text-danger'>$error</p>"; ?>
                <form method="post">
                    <div class="mb-3">
                        <label for="password" class="form-label">パスワードだよ</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">認証だよ♪</button>
                </form>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        </body>
        </html>
        <?php
        exit;
    }
}

$products = get_existing_products();
$result = ['success' => false, 'message' => ''];

if (isset($_POST['delete_product'])) {
    $product_id = $_POST['product_id'] ?? '';
    $result = delete_product($product_id);
}

if (isset($_POST['action']) && $_POST['action'] === 'toggle_sold_out') {
    $product_id = $_POST['product_id'] ?? '';
    $variant_key = $_POST['variant_key'] ?? '';
    $result = toggle_variant_sold_out($product_id, $variant_key);
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'toggle_public') {
    $product_id = $_POST['product_id'] ?? '';
    $result = toggle_product_public($product_id);
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

$page_title = '商品リスト - trextacy.com';
include 'header_admin.php';
?>

<div class="container py-5">
    <h1 class="cure-majesty-title mb-4">登録商品リストだよ♪</h1>
    <div id="message-area" class="mb-4">
        <?php if ($result['success']): ?>
            <div class="alert cure-majesty-success"><?php echo $result['message']; ?></div>
        <?php elseif ($result['message']): ?>
            <div class="alert cure-majesty-error"><?php echo $result['message']; ?></div>
        <?php endif; ?>
    </div>

    <?php if (empty($products)): ?>
        <div class="alert cure-majesty-info">登録された商品はないよ...</div>
    <?php else: ?>
        <div class="card cure-majesty-card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 cure-majesty-table">
                        <thead>
                            <tr>
                                <th scope="col" class="ps-3" style="min-width: 150px;">商品名だよ</th>
                                <th scope="col" style="min-width: 100px;">リード文だよ</th>
                                <th scope="col" style="min-width: 200px;">説明だよ</th>
                                <th scope="col" style="min-width: 120px;">カテゴリーだよ</th>
                                <th scope="col" style="min-width: 150px;">属性だよ</th>
                                <th scope="col" style="min-width: 120px;">タグだよ</th>
                                <th scope="col" style="min-width: 150px;">バリアントだよ</th>
                                <th scope="col" style="min-width: 180px;">操作だよ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product_id => $product): ?>
                                <tr class="cure-majesty-row">
                                    <td class="ps-3 align-middle"><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="align-middle"><?php echo htmlspecialchars($product['lead'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="align-middle"><?php echo htmlspecialchars(mb_strimwidth($product['description'], 0, 50, '...', 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="align-middle"><?php echo htmlspecialchars($product['category'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="align-middle">
                                        <?php
                                        foreach ($product['attributes'] as $attr_name => $attr_data) {
                                            $values = $attr_data['values'] ?? [];
                                            echo htmlspecialchars($attr_name, ENT_QUOTES, 'UTF-8') . ': ' . implode(', ', array_map('htmlspecialchars', $values)) . '<br>';
                                        }
                                        ?>
                                    </td>
                                    <td class="align-middle"><?php echo htmlspecialchars(implode(', ', $product['tags'] ?? []), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="align-middle">
                                        <button class="btn btn-link p-0 text-decoration-none cure-majesty-link" type="button" data-bs-toggle="collapse" data-bs-target="#variants-<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>" aria-expanded="false" aria-controls="variants-<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo count($product['variants']); ?>件 <i class="bi bi-chevron-down"></i>
                                        </button>
                                        <div class="collapse" id="variants-<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>">
                                            <ul class="list-unstyled mt-2">
                                                <?php foreach ($product['variants'] as $variant_key => $variant): ?>
                                                    <li class="mb-1 d-flex align-items-center">
                                                        <span class="me-2"><?php echo htmlspecialchars($variant_key, ENT_QUOTES, 'UTF-8'); ?> - <?php echo number_format($variant['price']); ?>円</span>
                                                        <input type="checkbox" 
                                                               class="form-check-input toggle-sold-out" 
                                                               data-product-id="<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>" 
                                                               data-variant-key="<?php echo htmlspecialchars($variant_key, ENT_QUOTES, 'UTF-8'); ?>" 
                                                               <?php echo $variant['sold_out'] ? 'checked' : ''; ?>>
                                                        <label class="form-check-label ms-1"><?php echo $variant['sold_out'] ? '売り切れだよ' : '在庫ありだよ'; ?></label>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </td>
                                    <td class="align-middle">
                                        <a href="product_edit.php?product_id=<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm mb-2 w-100 cure-majesty-btn-edit">編集だよ♪</a>
                                        <form method="post" onsubmit="return confirm('本当に削除する？');">
                                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>">
                                            <button type="submit" name="delete_product" class="btn btn-sm w-100 cure-majesty-btn-danger">削除だよ</button>
                                        </form>
                                        <div class="form-check form-switch mt-2">
                                            <input type="checkbox" 
                                                   class="form-check-input toggle-public" 
                                                   data-product-id="<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>" 
                                                   <?php echo ($product['is_public'] ?? true) ? 'checked' : ''; ?>>
                                            <label class="form-check-label"><?php echo ($product['is_public'] ?? true) ? '公開だよ' : '非公開だよ'; ?></label>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="mt-4">
        <a href="product_admin.php" class="btn cure-majesty-btn">商品を追加だよ♪</a>
        <a href="logout.php" class="btn cure-majesty-btn-outline ms-2">ログアウトだよ♪</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script>
document.querySelectorAll('.toggle-sold-out').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const productId = this.dataset.productId;
        const variantKey = this.dataset.variantKey;
        const isChecked = this.checked;
        const label = this.nextElementSibling;

        fetch('product_list.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=toggle_sold_out&product_id=${encodeURIComponent(productId)}&variant_key=${encodeURIComponent(variantKey)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                label.textContent = isChecked ? '売り切れだよ' : '在庫ありだよ';
                document.getElementById('message-area').innerHTML = '<div class="alert cure-majesty-success">' + data.message + '</div>';
            } else {
                alert('更新に失敗しました: ' + data.message);
                this.checked = !isChecked;
            }
        })
        .catch(error => {
            alert('エラーが発生しました: ' + error);
            this.checked = !isChecked;
        });
    });
});

document.querySelectorAll('.toggle-public').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const productId = this.dataset.productId;
        const isChecked = this.checked;
        const label = this.nextElementSibling;

        fetch('product_list.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=toggle_public&product_id=${encodeURIComponent(productId)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                label.textContent = isChecked ? '公開だよ' : '非公開だよ';
                document.getElementById('message-area').innerHTML = '<div class="alert cure-majesty-success">' + data.message + '</div>';
            } else {
                alert('更新に失敗しました: ' + data.message);
                this.checked = !isChecked;
            }
        })
        .catch(error => {
            alert('エラーが発生しました: ' + error);
            this.checked = !isChecked;
        });
    });
});
</script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
body {
    font-family: 'Noto Sans JP', sans-serif;
    background: linear-gradient(135deg, #D8BFD8, #FFFFFF);
    color: #333;
    min-height: 100vh;
}

.container {
    max-width: 1400px;
}

.cure-majesty-title {
    color: #D8BFD8;
    font-weight: 700;
    text-shadow: 0 2px 5px rgba(216, 191, 216, 0.5);
}

.cure-majesty-card {
    border: none;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(216, 191, 216, 0.5);
    background: #fff;
}

.cure-majesty-table th {
    background: #D8BFD8;
    color: white;
    font-weight: 500;
    padding: 12px;
    border-bottom: 2px solid #FFD700;
}

.cure-majesty-table td {
    padding: 12px;
    vertical-align: middle;
}

.cure-majesty-row:hover {
    background: rgba(216, 191, 216, 0.1);
    transition: background 0.3s;
}

.cure-majesty-btn {
    background: #D8BFD8;
    border: none;
    border-radius: 20px;
    color: white;
    font-weight: 500;
    padding: 10px 20px;
    box-shadow: 0 3px 10px rgba(216, 191, 216, 0.5);
    transition: all 0.3s;
}

.cure-majesty-btn:hover {
    background: #C68EC6;
    transform: translateY(-2px);
}

.cure-majesty-btn-edit {
    background: #FFD700;
    border: none;
    border-radius: 15px;
    color: #333;
    padding: 6px 12px;
    box-shadow: 0 2px 5px rgba(216, 191, 216, 0.5);
}

.cure-majesty-btn-edit:hover {
    background: #FFC107;
    color: #333;
}

.cure-majesty-btn-danger {
    background: #C68EC6;
    border: none;
    border-radius: 10px;
    padding: 6px 12px;
    box-shadow: 0 2px 5px rgba(216, 191, 216, 0.5);
}

.cure-majesty-btn-danger:hover {
    background: #B57EB5;
}

.cure-majesty-btn-outline {
    background: transparent;
    border: 2px solid #D8BFD8;
    border-radius: 20px;
    color: #D8BFD8;
    padding: 8px 20px;
    transition: all 0.3s;
}

.cure-majesty-btn-outline:hover {
    background: #D8BFD8;
    color: white;
}

.cure-majesty-link {
    color: #D8BFD8;
}

.cure-majesty-link:hover {
    color: #C68EC6;
}

.cure-majesty-success {
    background: rgba(216, 191, 216, 0.2);
    border: 2px solid #D8BFD8;
    color: #D8BFD8;
    border-radius: 10px;
}

.cure-majesty-error {
    background: rgba(198, 142, 198, 0.2);
    border: 2px solid #C68EC6;
    color: #C68EC6;
    border-radius: 10px;
}

.cure-majesty-info {
    background: rgba(216, 191, 216, 0.2);
    border: 2px solid #D8BFD8;
    color: #D8BFD8;
    border-radius: 10px;
}
</style>

<?php include 'footer.php'; ?>
</body>
</html>