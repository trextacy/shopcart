<?php
session_start();
require_once 'plugins/functions.php';
require_once 'plugins/product_admin_logic.php';
$config = require_once 'config/admin-config.php';

$base_path = get_base_path();

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
                <h2>認証が必要です</h2>
                <?php if (isset($error)) echo "<p class='text-danger'>$error</p>"; ?>
                <form method="post">
                    <div class="mb-3">
                        <label for="password" class="form-label">パスワード</label>
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

// products.jsonを読み込む
$products = json_decode(file_get_contents('products.json'), true) ?? [];
$product_id = $_GET['product_id'] ?? '';
if (!isset($products[$product_id])) {
    die('商品が見つかりません。');
}

$product = $products[$product_id];
$product['attributes'] = $product['attributes'] ?? [];
$product['variants'] = $product['variants'] ?? [];
$result = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = handle_product_admin_submission();
    if ($result['success']) {
        // デバッグ情報をセッションに保存してリダイレクト
        $_SESSION['last_update'] = [
            'post_data' => $_POST,
            'updated_product' => json_decode(file_get_contents('products.json'), true)[$product_id]
        ];
        header("Location: product_edit.php?product_id=" . urlencode($product_id));
        exit;
    } else {
        $result['post_data'] = $_POST; // エラー時にPOSTデータを保持
    }
}

$page_title = '商品編集 - trextacy.com';
include 'header_admin.php';
?>

<div class="container mt-4">
    <h1 class="mb-4 cure-sky-title">商品編集: <?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
    
    <!-- デバッグ情報の表示 -->
    <?php if (isset($_SESSION['last_update'])): ?>
        <div class="alert alert-info">
            <h4>前回の更新データ</h4>
            <pre>POSTデータ: <?php print_r($_SESSION['last_update']['post_data']); ?></pre>
            <pre>更新後のデータ: <?php print_r($_SESSION['last_update']['updated_product']); ?></pre>
            <?php unset($_SESSION['last_update']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($result['success'])): ?>
        <div class="alert alert-success cure-sky-alert"><?php echo $result['message']; ?></div>
    <?php elseif (isset($result['message'])): ?>
        <div class="alert alert-danger">
            <?php echo $result['message']; ?>
            <pre>POSTデータ: <?php print_r($result['post_data']); ?></pre>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" id="productForm">
        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="edit" value="1">

        <!-- タブナビゲーション -->
        <ul class="nav nav-tabs cure-sky-tabs" id="editTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="basic-tab" data-bs-toggle="tab" href="#basic" role="tab">基本情報だよ♪</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="images-attributes-tab" data-bs-toggle="tab" href="#images-attributes" role="tab">画像と属性だよ♪</a>
            </li>
        </ul>

        <!-- タブコンテンツ -->
        <div class="tab-content mt-3" id="editTabContent">
            <!-- 基本情報タブ -->
            <div class="tab-pane fade show active" id="basic" role="tabpanel">
                <div class="card cure-sky-card p-3">
                    <div class="mb-3">
                        <label for="product_id_display" class="form-label">商品ID</label>
                        <input type="text" class="form-control cure-sky-input" id="product_id_display" value="<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">商品名</label>
                        <input type="text" class="form-control cure-sky-input" id="name" name="name" value="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">商品説明</label>
                        <textarea class="form-control cure-sky-input" id="description" name="description" rows="5"><?php echo htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="lead" class="form-label">リード文（10～15文字の短文）</label>
                        <input type="text" class="form-control cure-sky-input" id="lead" name="lead" value="<?php echo htmlspecialchars($product['lead'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="15" placeholder="例: おいしいみかんだよ">
                    </div>
                    <div class="mb-3">
                        <label for="category" class="form-label">カテゴリー</label>
                        <input type="text" class="form-control cure-sky-input" id="category" name="category" value="<?php echo htmlspecialchars($product['category'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="例: フルーツ、PCパーツ">
                        <small class="text-muted">商品のカテゴリーを入力してください（任意）。</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">公開設定</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1" <?php echo ($product['is_public'] ?? true) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_public"><?php echo ($product['is_public'] ?? true) ? '公開だよ！' : '非公開だよ'; ?></label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="tags" class="form-label">タグ（カンマ区切り）</label>
                        <input type="text" class="form-control cure-sky-input" id="tags" name="tags" value="<?php echo htmlspecialchars(implode(',', $product['tags'] ?? []), ENT_QUOTES, 'UTF-8'); ?>" placeholder="例: Tシャツ, カジュアル">
                    </div>
                </div>
            </div>

            <!-- 画像と属性タブ -->
            <div class="tab-pane fade" id="images-attributes" role="tabpanel">
                <div class="card cure-sky-card p-3">
                    <!-- デフォルト画像 -->
                    <div class="mb-3">
                        <label for="default_image" class="form-label">デフォルト画像だよ♪</label>
                        <select class="form-select cure-sky-select" id="default_image" name="default_image">
                            <?php foreach ($product['images'] as $index => $img): ?>
                                <option value="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($product['default_image'] ?? $product['images'][0]) === $img ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($product['image_descriptions'][$index] ?? "画像{$index}", ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">商品の基本画像を選んでね！</small>
                    </div>

                    <!-- 属性 -->
                    <div class="mb-3">
                        <label class="form-label">属性（例: カラー、サイズなど）</label>
                        <div id="attr-container">
                            <?php foreach ($product['attributes'] as $attr_name => $attr_data): ?>
                                <div class="attr-entry mb-3 border rounded p-2 position-relative cure-sky-entry">
                                    <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 cure-sky-btn-danger" onclick="this.parentElement.remove()">削除</button>
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">属性名</label>
                                            <input type="text" class="form-control cure-sky-input" name="attr_name[]" value="<?php echo htmlspecialchars($attr_name, ENT_QUOTES, 'UTF-8'); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">値（カンマ区切り）</label>
                                            <input type="text" class="form-control cure-sky-input" name="attr_values[]" value="<?php echo htmlspecialchars(implode(',', $attr_data), ENT_QUOTES, 'UTF-8'); ?>" required>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-outline-primary mt-2 cure-sky-btn" onclick="addAttrEntry()">属性を追加だよ♪</button>
                    </div>

                    <!-- バリアント -->
                    <div class="mb-3">
                        <label class="form-label">バリアント（価格と在庫と画像だよ♪）</label>
                        <div class="mb-3 d-flex align-items-end">
                            <div class="flex-grow-1 me-2">
                                <label for="bulk_price" class="form-label">全て同じ価格にする（任意）</label>
                                <input type="number" class="form-control cure-sky-input" id="bulk_price" name="bulk_price" placeholder="例: 3500" min="0">
                            </div>
                            <button type="button" class="btn btn-outline-primary cure-sky-btn" onclick="applyBulkPrice()">一括適用だよ♪</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered cure-sky-table" id="variant-table">
                                <thead>
                                    <tr>
                                        <th>バリアントキー</th>
                                        <th>価格</th>
                                        <th>在庫</th>
                                        <th>画像</th>
                                    </tr>
                                </thead>
                                <tbody id="variant-container">
                                    <?php foreach ($product['variants'] as $variant_key => $variant): ?>
                                        <tr class="variant-entry">
                                            <td>
                                                <input type="hidden" name="variant_key[]" value="<?php echo htmlspecialchars($variant_key, ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars($variant_key, ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control variant-price cure-sky-input" name="variant_price[]" value="<?php echo $variant['price']; ?>" min="0" required>
                                            </td>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="variant_sold_out[<?php echo htmlspecialchars($variant_key, ENT_QUOTES, 'UTF-8'); ?>]" value="1" <?php echo $variant['sold_out'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label">売り切れだよ</label>
                                                </div>
                                            </td>
                                            <td>
                                                <select class="form-select cure-sky-select" name="variant_image[<?php echo htmlspecialchars($variant_key, ENT_QUOTES, 'UTF-8'); ?>]">
                                                    <option value="">デフォルト画像だよ</option>
                                                    <?php foreach ($product['images'] as $index => $img): ?>
                                                        <option value="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($variant['image'] ?? '') === $img ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($product['image_descriptions'][$index] ?? "画像{$index}", ENT_QUOTES, 'UTF-8'); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-outline-primary mt-2 cure-sky-btn" onclick="generateVariants()">バリアントを生成だよ♪</button>
                    </div>

                    <!-- 画像 -->
                    <div class="mb-3">
                        <label class="form-label">現在の画像だよ♪</label>
                        <div id="image-preview" class="d-flex flex-wrap gap-3 mb-3">
                            <?php foreach ($product['images'] as $index => $image): ?>
                                <div class="image-item card p-2 cure-sky-image" draggable="true" data-index="<?php echo $index; ?>">
                                    <?php $image_src = (strpos($image, 'http') === 0) ? $image : $base_path . $image; ?>
                                    <img src="<?php echo htmlspecialchars($image_src, ENT_QUOTES, 'UTF-8'); ?>" 
                                         class="card-img-top" 
                                         style="width: 100px; height: 100px; object-fit: cover;"
                                         alt="<?php echo htmlspecialchars($product['image_descriptions'][$index] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="card-body p-1">
                                        <textarea class="form-control cure-sky-input" name="image_desc[]" placeholder="画像説明だよ" rows="2" oninput="updateImageOptions()"><?php echo htmlspecialchars($product['image_descriptions'][$index] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                        <button type="button" class="btn btn-danger btn-sm mt-1 w-100 cure-sky-btn-danger" onclick="this.parentElement.parentElement.remove(); updateImageOptions()">削除だよ</button>
                                        <input type="hidden" name="existing_images[]" value="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <label for="images" class="form-label">新しい画像だよ（ドラッグ＆ドロップOK♪）</label>
                        <input type="file" class="form-control cure-sky-input" id="images" name="images[]" multiple accept="image/*">
                        <small class="text-muted">ドラッグで並び替え、削除ボタンで消してね。更新で確定だよ♪</small>
                        <input type="hidden" id="image-order" name="image_order">
                        <input type="hidden" id="image-descriptions" name="image_descriptions">
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <button type="button" id="submitButton" class="btn btn-primary cure-sky-btn">更新だよ♪</button>
            <button type="button" id="copyButton" class="btn btn-success cure-sky-btn ms-2">コピーして保存だよ♪</button>
            <a href="product_list.php" class="btn btn-secondary cure-sky-btn ms-2">戻るよ♪</a>
        </div>
    </form>
</div>

<script src="https://cdn.tiny.cloud/1/1gbw1vps2yoottaix7o0rx1ollhlhqxavqe0zqwz28ein7y6/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: '#description',
    plugins: 'lists link image',
    toolbar: 'undo redo | bold italic | bullist numlist | link image',
    menubar: false,
    height: 300,
    content_style: 'body { font-family: "Noto Sans JP", sans-serif; font-size: 14px; }'
});

function addAttrEntry() {
    const container = document.getElementById('attr-container');
    const entry = document.createElement('div');
    entry.className = 'attr-entry mb-3 border rounded p-2 position-relative cure-sky-entry';
    entry.innerHTML = `
        <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 cure-sky-btn-danger" onclick="this.parentElement.remove()">削除</button>
        <div class="row">
            <div class="col-md-6 mb-2">
                <label class="form-label">属性名だよ</label>
                <input type="text" class="form-control cure-sky-input" name="attr_name[]" placeholder="例: カラー" required>
            </div>
            <div class="col-md-6 mb-2">
                <label class="form-label">値（カンマ区切りだよ）</label>
                <input type="text" class="form-control cure-sky-input" name="attr_values[]" placeholder="例: 赤, 青, 白" required>
            </div>
        </div>
    `;
    container.appendChild(entry);
}

function generateVariants() {
    const attrs = Array.from(document.querySelectorAll('.attr-entry')).map(entry => {
        const name = entry.querySelector('input[name="attr_name[]"]').value;
        const values = entry.querySelector('input[name="attr_values[]"]').value.split(',').map(v => v.trim());
        return { name, values };
    });
    if (attrs.length === 0) return;

    const combinations = cartesian(attrs.map(a => a.values));
    const container = document.getElementById('variant-container');
    container.innerHTML = '';

    combinations.forEach(combo => {
        const key = combo.join('-');
        const row = document.createElement('tr');
        row.className = 'variant-entry';
        row.innerHTML = `
            <td>
                <input type="hidden" name="variant_key[]" value="${key}">
                ${key}
            </td>
            <td><input type="number" class="form-control variant-price cure-sky-input" name="variant_price[]" placeholder="価格" min="0" required></td>
            <td>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="variant_sold_out[${key}]" value="1">
                    <label class="form-check-label">売り切れだよ</label>
                </div>
            </td>
            <td>
                <select class="form-select cure-sky-select" name="variant_image[${key}]">
                    <option value="">デフォルト画像だよ</option>
                    ${Array.from(document.querySelectorAll('#image-preview .image-item')).map((item, index) => {
                        const imgSrc = item.querySelector('img').src;
                        const desc = item.querySelector('textarea').value || `画像${index}`;
                        return `<option value="${imgSrc}">${desc}</option>`;
                    }).join('')}
                </select>
            </td>
        `;
        container.appendChild(row);
    });
}

function applyBulkPrice() {
    const bulkPrice = document.getElementById('bulk_price').value;
    if (bulkPrice && !isNaN(bulkPrice)) {
        document.querySelectorAll('.variant-price').forEach(input => input.value = bulkPrice);
    }
}

function cartesian(arrays) {
    return arrays.reduce((acc, curr) => acc.flatMap(x => curr.map(y => x.concat(y))), [[]]);
}

function updateImageOptions() {
    const images = Array.from(document.querySelectorAll('#image-preview .image-item')).map((item, index) => ({
        src: item.querySelector('img').src,
        description: item.querySelector('textarea').value || `画像${index + 1}`
    }));
    document.querySelectorAll('select[name^="variant_image"]').forEach(select => {
        const currentValue = select.value;
        select.innerHTML = '<option value="">デフォルト画像だよ</option>';
        images.forEach(img => {
            const option = document.createElement('option');
            option.value = img.src;
            option.textContent = img.description;
            if (currentValue === img.src) option.selected = true;
            select.appendChild(option);
        });
    });
    const defaultSelect = document.getElementById('default_image');
    defaultSelect.innerHTML = images.map(img => `<option value="${img.src}" ${img.src === images[0].src ? 'selected' : ''}>${img.description}</option>`).join('');
}

const imageInput = document.getElementById('images');
const preview = document.getElementById('image-preview');
let nextIndex = <?php echo count($product['images']); ?>;

function addImageToPreview(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        const div = document.createElement('div');
        div.className = 'image-item card p-2 cure-sky-image';
        div.draggable = true;
        div.dataset.index = nextIndex++;
        div.innerHTML = `
            <img src="${e.target.result}" class="card-img-top" style="width: 100px; height: 100px; object-fit: cover;">
            <div class="card-body p-1">
                <textarea class="form-control cure-sky-input" name="image_desc[]" placeholder="画像説明だよ" rows="2" oninput="updateImageOptions()"></textarea>
                <button type="button" class="btn btn-danger btn-sm mt-1 w-100 cure-sky-btn-danger" onclick="this.parentElement.parentElement.remove(); updateImageOptions()">削除だよ</button>
                <input type="hidden" name="new_images[]" value="${file.name}">
            </div>
        `;
        preview.appendChild(div);
        enableDragAndDrop();
        updateImageOptions();
    };
    reader.readAsDataURL(file);
}

imageInput.addEventListener('change', function(e) {
    Array.from(e.target.files).forEach(file => addImageToPreview(file));
});

function enableDragAndDrop() {
    const items = preview.querySelectorAll('.image-item');
    items.forEach(item => {
        item.addEventListener('dragstart', e => e.dataTransfer.setData('text/plain', item.dataset.index));
        item.addEventListener('dragover', e => e.preventDefault());
        item.addEventListener('drop', e => {
            e.preventDefault();
            const fromIndex = e.dataTransfer.getData('text/plain');
            const toIndex = item.dataset.index;
            if (fromIndex !== toIndex) {
                const fromItem = preview.querySelector(`[data-index="${fromIndex}"]`);
                const toItem = preview.querySelector(`[data-index="${toIndex}"]`);
                preview.insertBefore(fromItem, toItem);
                updateIndices();
                updateImageOptions();
            }
        });
    });
}

function updateIndices() {
    const items = preview.querySelectorAll('.image-item');
    items.forEach((item, index) => item.dataset.index = index);
}

function updateImageOrder() {
    const items = preview.querySelectorAll('.image-item');
    const order = [];
    const descriptions = [];
    items.forEach((item, index) => {
        order.push(index);
        descriptions.push(item.querySelector('textarea').value);
    });
    document.getElementById('image-order').value = JSON.stringify(order);
    document.getElementById('image-descriptions').value = JSON.stringify(descriptions);
}

document.getElementById('submitButton').addEventListener('click', () => {
    updateImageOrder();
    document.getElementById('productForm').submit();
});

document.getElementById('copyButton').addEventListener('click', () => {
    const form = document.getElementById('productForm');
    const copyInput = document.createElement('input');
    copyInput.type = 'hidden';
    copyInput.name = 'copy';
    copyInput.value = '1';
    form.appendChild(copyInput);
    updateImageOrder();
    form.submit();
});

enableDragAndDrop();
updateImageOptions();
</script>

<style>
body {
    font-family: 'Noto Sans JP', sans-serif;
    background: linear-gradient(135deg, #F8E1E9, #A2CFFE);
    color: #333;
    min-height: 100vh;
}

.cure-sky-title {
    color: #A2CFFE;
    font-weight: 700;
    text-shadow: 0 2px 5px rgba(162, 207, 254, 0.5);
}

.cure-sky-tabs .nav-link {
    font-weight: 500;
    color: #A2CFFE;
    border-radius: 15px 15px 0 0;
    transition: all 0.3s;
}

.cure-sky-tabs .nav-link.active {
    background: #A2CFFE;
    color: white;
    box-shadow: 0 3px 10px rgba(162, 207, 254, 0.5);
}

.cure-sky-card {
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(162, 207, 254, 0.5);
    transition: transform 0.3s;
}

.cure-sky-card:hover {
    transform: translateY(-5px);
}

.cure-sky-input, .cure-sky-select {
    border: 2px solid #A2CFFE;
    border-radius: 10px;
    font-weight: 500;
    transition: all 0.3s;
}

.cure-sky-input:focus, .cure-sky-select:focus {
    border-color: #87BFFF;
    box-shadow: 0 0 5px rgba(135, 191, 255, 0.7);
}

.cure-sky-btn {
    background: #A2CFFE;
    border: none;
    border-radius: 20px;
    color: white;
    font-weight: 500;
    padding: 8px 20px;
    box-shadow: 0 3px 10px rgba(162, 207, 254, 0.5);
    transition: all 0.3s;
}

.cure-sky-btn:hover {
    background: #87BFFF;
    transform: translateY(-2px);
}

.cure-sky-btn-danger {
    background: #ff6f91;
    border-radius: 10px;
    padding: 4px 12px;
    font-size: 12px;
    box-shadow: 0 2px 5px rgba(255, 111, 145, 0.5);
}

.cure-sky-btn-danger:hover {
    background: #ff4d73;
}

.cure-sky-entry {
    background: rgba(255, 255, 255, 0.8);
    border: 2px dashed #A2CFFE;
    border-radius: 15px;
}

.cure-sky-table {
    background: #fff;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(162, 207, 254, 0.5);
}

.cure-sky-table th {
    background: #A2CFFE;
    color: white;
    font-weight: 500;
}

.cure-sky-image {
    width: 150px;
    border-radius: 15px;
    overflow: hidden;
    transition: all 0.3s;
}

.cure-sky-image:hover {
    transform: scale(1.05);
    box-shadow: 0 5px 15px rgba(162, 207, 254, 0.7);
}

.cure-sky-alert {
    background: rgba(162, 207, 254, 0.2);
    border: 2px solid #A2CFFE;
    color: #A2CFFE;
    border-radius: 10px;
}
</style>

<?php include 'footer.php'; ?>