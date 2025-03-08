<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();
require_once 'plugins/functions.php';
require_once 'plugins/product_admin_logic.php';
$config = require_once 'config/admin-config.php';

$products = load_products();

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

$result = ['success' => false, 'message' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = handle_product_admin_submission();
}

$page_title = '商品登録 - trextacy.com';
include 'header_admin.php';
?>

<div class="container mt-4">
    <h1 class="cure-prism-title mt-4">商品登録だよ♪</h1>
    <?php if ($result['success']): ?>
        <p class="text-success cure-prism-success"><?php echo $result['message']; ?></p>
    <?php elseif (!empty($result['message'])): ?>
        <p class="text-danger cure-prism-error"><?php echo $result['message']; ?></p>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" id="productForm" onsubmit="return false;" class="cure-prism-form">
        <div class="mb-3">
            <label for="product_id" class="form-label">商品ID（半角英数字）だよ</label>
            <input type="text" class="form-control cure-prism-input" id="product_id" name="product_id" placeholder="例: product01" required>
        </div>

        <div class="mb-3">
            <label for="name" class="form-label">商品名だよ</label>
            <input type="text" class="form-control cure-prism-input" id="name" name="name" placeholder="例: オリジナルTシャツ" required>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">商品説明だよ</label>
            <textarea class="form-control cure-prism-input" id="description" name="description" placeholder="例: 着心地の良いコットン100%のTシャツ" required></textarea>
        </div>

        <div class="mb-3">
            <label for="lead" class="form-label">リード文（10～15文字の短文）だよ</label>
            <input type="text" class="form-control cure-prism-input" id="lead" name="lead" maxlength="15" placeholder="例: おいしいみかんだよ">
        </div>

        <div class="mb-3">
            <label for="category" class="form-label">カテゴリーだよ</label>
            <input type="text" class="form-control cure-prism-input" id="category" name="category" placeholder="例: フルーツ、PCパーツ">
            <small class="text-muted">商品のカテゴリーを入力してね（任意だよ）。</small>
        </div>

        <div class="mb-3">
            <label class="form-label">属性（例: サイズ、カラーなど）と画像だよ</label>
            <div id="attr-container" class="cure-prism-container">
                <div class="attr-entry mb-3 cure-prism-entry">
                    <div class="d-flex mb-2">
                        <input type="text" class="form-control cure-prism-input me-2" name="attr_name[]" placeholder="属性名 (例: サイズ)" required>
                        <input type="text" class="form-control cure-prism-input me-2" name="attr_values[]" placeholder="値 (例: S, M, LL)" required oninput="updateAttrValueImages(this, 0)">
                        <button type="button" class="btn btn-danger cure-prism-btn-danger" onclick="this.parentElement.parentElement.remove()">削除だよ</button>
                    </div>
                    <div class="attr-values-images" data-attr-index="0"></div>
                </div>
            </div>
            <button type="button" class="btn btn-outline-secondary cure-prism-btn" onclick="addAttrEntry()">属性を追加だよ♪</button>
        </div>

        <div class="mb-3">
            <label class="form-label">バリアント（組み合わせごとの価格と在庫だよ♪）</label>
            <div class="mb-2 d-flex align-items-end">
                <div class="flex-grow-1 me-2">
                    <label for="bulk_price" class="form-label">全て同じ価格にする（任意だよ）</label>
                    <input type="number" class="form-control cure-prism-input" id="bulk_price" name="bulk_price" placeholder="例: 3500" min="0">
                </div>
                <button type="button" class="btn btn-outline-secondary cure-prism-btn" onclick="applyBulkPrice()">一括適用だよ♪</button>
            </div>
            <div id="variant-container" class="cure-prism-container"></div>
            <button type="button" class="btn btn-outline-secondary cure-prism-btn mt-2" onclick="generateVariants()">バリアントを生成だよ♪</button>
        </div>

        <div class="mb-3">
            <label for="images" class="form-label">商品画像（ドラッグ＆ドロップで複数OKだよ♪）</label>
            <input type="file" class="form-control cure-prism-input" id="images" name="images[]" multiple accept="image/*">
            <small class="text-muted">画像説明を入力すると、属性やバリアントの選択肢に反映されるよ。</small>
            <div id="image-preview" class="mt-2 d-flex flex-wrap gap-3"></div>
            <input type="hidden" id="image-order" name="image_order">
            <input type="hidden" id="image-descriptions" name="image_descriptions">
        </div>

        <div class="mb-3">
            <label for="tags" class="form-label">タグ（カンマ区切りだよ）</label>
            <input type="text" class="form-control cure-prism-input" id="tags" name="tags" placeholder="例: Tシャツ, カジュアル">
        </div>

        <div class="mt-4">
            <button type="button" id="submitButton" class="btn btn-primary cure-prism-btn-primary">商品を登録だよ♪</button>
            <a href="product_list.php" class="btn btn-info cure-prism-btn ms-2">商品リストを見るよ♪</a>
            <a href="logout.php" class="btn btn-secondary cure-prism-btn ms-2">ログアウトだよ♪</a>
        </div>
    </form>
</div>

<script>
let attrIndex = 0;

function addAttrEntry() {
    attrIndex++;
    const container = document.getElementById('attr-container');
    const entry = document.createElement('div');
    entry.className = 'attr-entry mb-3 cure-prism-entry';
    entry.innerHTML = `
        <div class="d-flex mb-2">
            <input type="text" class="form-control cure-prism-input me-2" name="attr_name[]" placeholder="属性名 (例: カラー)" required>
            <input type="text" class="form-control cure-prism-input me-2" name="attr_values[]" placeholder="値 (例: 赤, 青, 白)" required oninput="updateAttrValueImages(this, ${attrIndex})">
            <button type="button" class="btn btn-danger cure-prism-btn-danger" onclick="this.parentElement.parentElement.remove()">削除だよ</button>
        </div>
        <div class="attr-values-images" data-attr-index="${attrIndex}"></div>
    `;
    container.appendChild(entry);
}

function updateAttrValueImages(input, attrIndex) {
    const values = input.value.split(',').map(v => v.trim()).filter(v => v);
    const container = document.querySelector(`.attr-values-images[data-attr-index="${attrIndex}"]`);
    container.innerHTML = '';
    values.forEach((value, index) => {
        const div = document.createElement('div');
        div.className = 'd-flex align-items-center mb-2';
        div.innerHTML = `
            <span class="me-2" style="min-width: 80px;">${value}</span>
            <select class="form-select attr-image-select cure-prism-select" name="attr_value_images[${attrIndex}][]" data-value="${value}" onchange="updatePreview(this)">
                <option value="">デフォルト画像だよ</option>
            </select>
            <img class="preview-img ms-2" src="" style="width: 50px; height: 50px; object-fit: cover; display: none;" alt="プレビュー">
        `;
        container.appendChild(div);
    });
    updateImageOptions();
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
        const row = document.createElement('div');
        row.className = 'variant-entry mb-2 d-flex align-items-center cure-prism-entry';
        row.innerHTML = `
            <input type="hidden" name="variant_key[]" value="${key}">
            <span class="me-2" style="min-width: 100px;">${key}</span>
            <input type="number" class="form-control cure-prism-input me-2 variant-price" name="variant_price[]" placeholder="価格" min="0" required>
            <label class="form-check-label me-2">売り切れだよ</label>
            <input type="checkbox" class="form-check-input" name="variant_sold_out[]" value="1">
            <select class="form-select cure-prism-select me-2 variant-image" name="variant_image[]" onchange="updatePreview(this)">
                <option value="">デフォルト画像だよ</option>
            </select>
            <img class="preview-img ms-2" src="" style="width: 50px; height: 50px; object-fit: cover; display: none;" alt="プレビュー">
        `;
        container.appendChild(row);
    });
    updateImageOptions();
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
        tempKey: `temp_${index}_${Date.now()}`,
        src: item.querySelector('img').src,
        description: item.querySelector('textarea').value || `画像${index + 1}`
    }));
    document.querySelectorAll('.variant-image, .attr-image-select').forEach(select => {
        const currentValue = select.value;
        select.innerHTML = '<option value="">デフォルト画像だよ</option>';
        images.forEach(img => {
            const option = document.createElement('option');
            option.value = img.tempKey;
            option.textContent = img.description;
            option.dataset.src = img.src;
            if (currentValue === img.tempKey) option.selected = true;
            select.appendChild(option);
        });
        updatePreview(select);
    });
}

function updatePreview(select) {
    const previewImg = select.nextElementSibling;
    if (select.value && select.selectedOptions[0].dataset.src) {
        previewImg.src = select.selectedOptions[0].dataset.src;
        previewImg.style.display = 'block';
    } else {
        previewImg.style.display = 'none';
    }
}

const imageInput = document.getElementById('images');
const preview = document.getElementById('image-preview');
imageInput.addEventListener('change', function(e) {
    preview.innerHTML = '';
    const files = Array.from(e.target.files);
    files.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const div = document.createElement('div');
            div.className = 'image-item me-2 mb-2 position-relative cure-prism-image';
            div.draggable = true;
            div.dataset.index = index;
            div.innerHTML = `
                <img src="${e.target.result}" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                <textarea class="form-control cure-prism-input mt-1" placeholder="画像説明 (例: 正面だよ)" rows="2" oninput="updateImageOptions()"></textarea>
                <button type="button" class="btn btn-danger cure-prism-btn-danger position-absolute top-0 end-0" onclick="this.parentElement.remove(); updateImageOptions()">削除だよ</button>
            `;
            preview.appendChild(div);
            updateImageOptions();
            document.querySelectorAll('input[name="attr_values[]"]').forEach((input, index) => {
                updateAttrValueImages(input, index);
            });
        };
        reader.readAsDataURL(file);
    });
    setTimeout(() => enableDragAndDrop(), 100);
});

function enableDragAndDrop() {
    const items = preview.querySelectorAll('.image-item');
    items.forEach(item => {
        item.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('text/plain', item.dataset.index);
        });
        item.addEventListener('dragover', (e) => e.preventDefault());
        item.addEventListener('drop', (e) => {
            e.preventDefault();
            const fromIndex = e.dataTransfer.getData('text/plain');
            const toIndex = item.dataset.index;
            if (fromIndex !== toIndex) {
                const fromItem = preview.children[fromIndex];
                const toItem = preview.children[toIndex];
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
    items.forEach(item => {
        const fileIndex = item.dataset.index;
        const description = item.querySelector('textarea').value;
        order.push(fileIndex);
        descriptions.push(description);
    });
    document.getElementById('image-order').value = JSON.stringify(order);
    document.getElementById('image-descriptions').value = JSON.stringify(descriptions);
}

document.getElementById('submitButton').addEventListener('click', function() {
    const form = document.getElementById('productForm');
    if (form.checkValidity()) {
        form.removeAttribute('onsubmit');
        updateImageOrder();
        form.submit();
    } else {
        form.reportValidity();
    }
});

document.getElementById('productForm').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') e.preventDefault();
});

document.querySelectorAll('input[name="attr_values[]"]').forEach((input, index) => {
    updateAttrValueImages(input, index);
});
</script>

<style>
body {
    font-family: 'Noto Sans JP', sans-serif;
    background: linear-gradient(135deg, #FFD1DC, #FFFFFF);
    color: #333;
    min-height: 100vh;
}

.cure-prism-title {
    color: #FFD1DC;
    font-weight: 700;
    text-shadow: 0 2px 5px rgba(255, 209, 220, 0.5);
}

.cure-prism-form {
    background: rgba(255, 255, 255, 0.9);
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 5px 15px rgba(255, 209, 220, 0.5);
}

.cure-prism-input, .cure-prism-select {
    border: 2px solid #FFD1DC;
    border-radius: 10px;
    font-weight: 500;
    transition: all 0.3s;
}

.cure-prism-input:focus, .cure-prism-select:focus {
    border-color: #FFABC2;
    box-shadow: 0 0 5px rgba(255, 171, 194, 0.7);
}

.cure-prism-btn {
    background: #FFD1DC;
    border: none;
    border-radius: 20px;
    color: white;
    font-weight: 500;
    padding: 8px 20px;
    box-shadow: 0 3px 10px rgba(255, 209, 220, 0.5);
    transition: all 0.3s;
}

.cure-prism-btn:hover {
    background: #FFABC2;
    transform: translateY(-2px);
}

.cure-prism-btn-primary {
    background: #FFD1DC;
    border-radius: 20px;
    padding: 8px 20px;
    box-shadow: 0 3px 10px rgba(255, 209, 220, 0.5);
    transition: all 0.3s;
}

.cure-prism-btn-primary:hover {
    background: #FFABC2;
    transform: translateY(-2px);
}

.cure-prism-btn-danger {
    background: #FFABC2;
    border-radius: 10px;
    padding: 4px 12px;
    font-size: 12px;
    box-shadow: 0 2px 5px rgba(255, 171, 194, 0.5);
}

.cure-prism-btn-danger:hover {
    background: #FF879D;
}

.cure-prism-container {
    background: rgba(255, 255, 255, 0.8);
    border-radius: 15px;
    padding: 10px;
}

.cure-prism-entry {
    background: rgba(255, 255, 255, 0.9);
    border: 2px dashed #FFD1DC;
    border-radius: 15px;
    padding: 10px;
    margin-bottom: 10px;
}

.cure-prism-image {
    width: 150px;
    border-radius: 15px;
    overflow: hidden;
    transition: all 0.3s;
}

.cure-prism-image:hover {
    transform: scale(1.05);
    box-shadow: 0 5px 15px rgba(255, 209, 220, 0.7);
}

.preview-img {
    border: 2px solid #FFD1DC;
    border-radius: 10px;
}

.cure-prism-success {
    background: rgba(255, 209, 220, 0.2);
    border: 2px solid #FFD1DC;
    color: #FFD1DC;
    border-radius: 10px;
}

.cure-prism-error {
    background: rgba(255, 171, 194, 0.2);
    border: 2px solid #FFABC2;
    color: #FFABC2;
    border-radius: 10px;
}
</style>

<?php include 'footer.php'; ?>