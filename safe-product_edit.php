<?php
session_start();
require_once 'plugins/functions.php';
require_once 'plugins/product_admin.php';

$products = get_existing_products();
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
        header("Location: product_edit.php?product_id=" . urlencode($product_id));
        exit;
    }
}

$page_title = '商品編集 - trextacy.com';
$base_path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/';
include 'header.php';
?>

<div class="container">
    <h1 class="mt-4">商品編集: <?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <?php if (!empty($result['success'])): ?>
        <p class="text-success"><?php echo $result['message']; ?></p>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" id="productForm">
        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="edit" value="1">

        <div class="mb-3">
            <label for="name" class="form-label">商品名</label>
            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">商品説明</label>
            <textarea class="form-control" id="description" name="description" required><?php echo htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">属性（例: サイズ、カラーなど）</label>
            <div id="attr-container">
                <?php foreach ($product['attributes'] as $attr_name => $values): ?>
                    <div class="attr-entry mb-2 d-flex">
                        <input type="text" class="form-control me-2" name="attr_name[]" value="<?php echo htmlspecialchars($attr_name, ENT_QUOTES, 'UTF-8'); ?>" required>
                        <input type="text" class="form-control" name="attr_values[]" value="<?php echo htmlspecialchars(implode(', ', $values), ENT_QUOTES, 'UTF-8'); ?>" required>
                        <button type="button" class="btn btn-danger ms-2" onclick="this.parentElement.remove()">削除</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-secondary" onclick="addAttrEntry()">属性を追加</button>
        </div>

        <div class="mb-3">
            <label class="form-label">バリアント（組み合わせごとの価格と在庫）</label>
            <div class="mb-2">
                <label for="bulk_price" class="form-label">全て同じ価格にする（任意）</label>
                <input type="number" class="form-control" id="bulk_price" name="bulk_price" placeholder="例: 3500" min="0">
                <button type="button" class="btn btn-secondary mt-2" onclick="applyBulkPrice()">一括適用</button>
            </div>
            <div id="variant-container">
                <?php foreach ($product['variants'] as $variant_key => $variant): ?>
                    <div class="variant-entry mb-2 d-flex align-items-center">
                        <input type="hidden" name="variant_key[]" value="<?php echo htmlspecialchars($variant_key, ENT_QUOTES, 'UTF-8'); ?>">
                        <span class="me-2" style="min-width: 100px;"><?php echo htmlspecialchars($variant_key, ENT_QUOTES, 'UTF-8'); ?></span>
                        <input type="number" class="form-control me-2 variant-price" name="variant_price[]" value="<?php echo $variant['price']; ?>" min="0" required>
                        <label class="form-check-label me-2">売り切れ</label>
                        <input type="checkbox" class="form-check-input" name="variant_sold_out[]" value="1" <?php echo $variant['sold_out'] ? 'checked' : ''; ?>>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-secondary mt-2" onclick="generateVariants()">バリアントを生成</button>
        </div>

        <div class="mb-3">
            <label class="form-label">現在の画像</label>
            <div id="image-preview" class="mt-2 d-flex flex-wrap">
                <?php foreach ($product['images'] as $index => $image): ?>
                    <div class="image-item me-2 mb-2 position-relative" draggable="true" data-index="<?php echo $index; ?>">
                        <?php $image_src = (strpos($image, 'http') === 0) ? $image : $base_path . $image; ?>
                        <img src="<?php echo htmlspecialchars($image_src, ENT_QUOTES, 'UTF-8'); ?>" 
                             class="img-thumbnail" 
                             style="width: 100px; height: 100px; object-fit: cover;"
                             alt="<?php echo htmlspecialchars($product['image_descriptions'][$index] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                             title="<?php echo htmlspecialchars($product['image_descriptions'][$index] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <textarea class="form-control mt-1" name="image_desc[]" placeholder="画像説明 (例: 正面)" rows="2"><?php echo htmlspecialchars($product['image_descriptions'][$index] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0" onclick="this.parentElement.remove()">×</button>
                        <input type="hidden" name="existing_images[]" value="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                <?php endforeach; ?>
            </div>
            <label for="images" class="form-label mt-2">新しい画像（ドラッグ＆ドロップまたは選択で追加）</label>
            <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
            <small class="text-muted">画像をドラッグで並び替え、クリックで削除可能。更新ボタンで確定。</small>
            <input type="hidden" id="image-order" name="image_order">
            <input type="hidden" id="image-descriptions" name="image_descriptions">
        </div>

        <div class="mb-3">
            <label for="tags" class="form-label">タグ（カンマ区切りで入力）</label>
            <input type="text" class="form-control" id="tags" name="tags" value="<?php echo htmlspecialchars(implode(',', $product['tags'] ?? []), ENT_QUOTES, 'UTF-8'); ?>" placeholder="例: Tシャツ, カジュアル">
        </div>

        <button type="button" id="submitButton" class="btn btn-primary">更新</button>
        <a href="product_list.php" class="btn btn-secondary ms-2">戻る</a>
    </form>
</div>

<script>
function addAttrEntry() {
    const container = document.getElementById('attr-container');
    const entry = document.createElement('div');
    entry.className = 'attr-entry mb-2 d-flex';
    entry.innerHTML = `
        <input type="text" class="form-control me-2" name="attr_name[]" placeholder="属性名 (例: カラー)" required>
        <input type="text" class="form-control" name="attr_values[]" placeholder="値 (例: 赤, 青, 白)" required>
        <button type="button" class="btn btn-danger ms-2" onclick="this.parentElement.remove()">削除</button>
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
        const row = document.createElement('div');
        row.className = 'variant-entry mb-2 d-flex align-items-center';
        row.innerHTML = `
            <input type="hidden" name="variant_key[]" value="${key}">
            <span class="me-2" style="min-width: 100px;">${key}</span>
            <input type="number" class="form-control me-2 variant-price" name="variant_price[]" placeholder="価格" min="0" required>
            <label class="form-check-label me-2">売り切れ</label>
            <input type="checkbox" class="form-check-input" name="variant_sold_out[]" value="1">
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

const imageInput = document.getElementById('images');
const preview = document.getElementById('image-preview');
let nextIndex = <?php echo count($product['images']); ?>;
let allFiles = []; // 累積ファイルリスト

// ドラッグ＆ドロップとファイル選択でプレビューに追加
document.body.addEventListener('dragover', (e) => e.preventDefault());
document.body.addEventListener('drop', (e) => {
    e.preventDefault();
    const files = Array.from(e.dataTransfer.files);
    if (files.length > 0) {
        addImages(files);
    }
});

imageInput.addEventListener('change', function(e) {
    const files = Array.from(e.target.files);
    if (files.length > 0) {
        addImages(files);
    }
});

function addImages(files) {
    const dataTransfer = new DataTransfer();
    allFiles = allFiles.concat(files); // 既存ファイルに追加
    allFiles.forEach(file => dataTransfer.items.add(file));
    imageInput.files = dataTransfer.files;

    files.forEach(file => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const div = document.createElement('div');
            div.className = 'image-item me-2 mb-2 position-relative';
            div.draggable = true;
            div.dataset.index = nextIndex++;
            div.innerHTML = `
                <img src="${e.target.result}" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                <textarea class="form-control mt-1" name="image_desc[]" placeholder="画像説明 (例: 正面)" rows="2"></textarea>
                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0" onclick="removeImage(this.parentElement, '${file.name}')">×</button>
            `;
            preview.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
    setTimeout(enableDragAndDrop, 100);
}

function removeImage(item, fileName) {
    item.remove();
    allFiles = allFiles.filter(file => file.name !== fileName);
    const dataTransfer = new DataTransfer();
    allFiles.forEach(file => dataTransfer.items.add(file));
    imageInput.files = dataTransfer.files;
    updateIndices();
}

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
    const existingImages = [];
    items.forEach(item => {
        const description = item.querySelector('textarea[name="image_desc[]"]').value;
        const existingImageInput = item.querySelector('input[name="existing_images[]"]');
        if (existingImageInput) {
            existingImages.push(existingImageInput.value);
        } else {
            order.push(parseInt(item.dataset.index) - <?php echo count($product['images']); ?>);
        }
        descriptions.push(description);
    });
    document.getElementById('image-order').value = JSON.stringify(order);
    document.getElementById('image-descriptions').value = JSON.stringify(descriptions);

    const existingInputs = document.querySelectorAll('input[name="existing_images[]"]');
    existingInputs.forEach(input => input.remove());
    existingImages.forEach(img => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'existing_images[]';
        input.value = img;
        preview.appendChild(input);
    });
}

document.getElementById('submitButton').addEventListener('click', function() {
    const form = document.getElementById('productForm');
    if (form.checkValidity()) {
        updateImageOrder();
        form.submit();
    } else {
        form.reportValidity();
    }
});

document.getElementById('productForm').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') e.preventDefault();
});

enableDragAndDrop();
</script>

<style>
.image-item { cursor: move; }
.image-item:hover { opacity: 0.8; }
</style>

<?php include 'footer.php'; ?>