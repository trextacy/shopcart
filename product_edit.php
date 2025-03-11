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
        <pre>POSTデータ: <?php print_r(array_map(function($item) { return is_string($item) ? urldecode($item) : $item; }, $_SESSION['last_update']['post_data'])); ?></pre>
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

<!-- product_edit.php のフォーム部分を修正 -->
<form method="post" enctype="multipart/form-data" id="productForm" onsubmit="handleFormSubmit(event)">
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
                    <!-- SEO対策用 -->
                    <div class="mb-3">
                        <label for="seo_description" class="form-label">SEO用の短い説明（100～150文字だよ♪）</label>
                        <textarea class="form-control cure-sky-input" id="seo_description" name="seo_description" rows="3" maxlength="150"><?php echo htmlspecialchars($product['seo_description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <small class="text-muted">グーグルさんに教える短い説明だよ。短く、わかりやすく書いてね！</small>
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
                <input type="text" class="form-control cure-sky-input" name="attr_values[]" value="<?php echo htmlspecialchars(implode(',', $attr_data['values']), ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" class="form-check-input" name="variant_display[<?php echo htmlspecialchars($attr_name, ENT_QUOTES, 'UTF-8'); ?>]" id="variant_display_<?php echo htmlspecialchars($attr_name, ENT_QUOTES, 'UTF-8'); ?>" value="button_group" <?php echo ($attr_data['variant_display'] ?? 'select') === 'button_group' ? 'checked' : ''; ?>>
            <label class="form-check-label" for="variant_display_<?php echo htmlspecialchars($attr_name, ENT_QUOTES, 'UTF-8'); ?>">Button Groupにするよ♪</label>
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
    <select class="form-select cure-sky-select" name="variant_image[<?php echo htmlspecialchars($variant_key, ENT_QUOTES, 'UTF-8'); ?>]" data-initial-value="<?php echo htmlspecialchars($variant['image'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <option value="" <?php echo empty($variant['image']) ? 'selected' : ''; ?>>デフォルト画像だよ（画像なし）</option>
        <?php foreach ($product['images'] as $index => $img): ?>
            <?php
            $is_selected = ($variant['image'] ?? '') === $img;
            echo "<!-- Debug: variant_image=" . htmlspecialchars($variant['image'] ?? 'なし') . ", option=" . htmlspecialchars($img) . ", selected=" . ($is_selected ? 'true' : 'false') . " -->";
            ?>
            <option value="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $is_selected ? 'selected' : ''; ?>>
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
        <button type="submit" id="submitButton" class="btn btn-primary cure-sky-btn">更新だよ♪</button>
        <button type="button" id="copyButton" class="btn btn-success cure-sky-btn ms-2">コピーして保存だよ♪</button>
        <a href="product_list.php" class="btn btn-secondary cure-sky-btn ms-2">戻るよ♪</a>
    </div>
</form>
</div>

<script src="tinymce/tinymce.min.js"></script>
<script src="/shopcart/tinymce/tinymce.min.js"></script>
<script>
  tinymce.init({
    selector: '#description', // 商品説明のtextareaのID
    license_key: '1gbw1vps2yoottaix7o0rx1ollhlhqxavqe0zqwz28ein7y6', // ライセンスキー
    plugins: 'image imagetools media link table lists fullscreen code textcolor align visualblocks template charmap paste wordcount autosave',
    toolbar: 'undo redo | bold italic | image media link | table | fullscreen code template',
    menubar: 'file edit insert view format table tools help',
    content_css: '/shopcart/bscss/custom.css',
    height: 600,
    images_upload_url: '/shopcart/upload.php',
    automatic_uploads: true,
    templates: [
      { title: 'ヒーロー画像（左揃え）', content: '<div class="px-4 py-5 text-center"><div class="row flex-lg-row-reverse align-items-center g-5"><div class="col-10 col-sm-8 col-lg-6"><img src="https://placehold.jp/30/dd6699/ffffff/700x400.png?text=Hero+Image" class="d-block mx-lg-auto img-fluid" alt="ヒーロー画像" loading="lazy"></div><div class="col-lg-6"><h1 class="display-5 fw-bold text-body-emphasis lh-1 mb-3">注目の商品</h1><p class="lead">ここに商品の魅力をたっぷり書けるよ。</p><div class="d-grid gap-2 d-md-flex justify-content-md-start"><button type="button" class="btn btn-primary btn-lg px-4 me-md-2">購入</button><button type="button" class="btn btn-outline-secondary btn-lg px-4">詳細</button></div></div></div></div>' },
      { title: 'シンプルカード', content: '<div class="card" style="width: 18rem;"><img src="https://placehold.jp/30/dd6699/ffffff/300x200.png?text=Card+Image" class="card-img-top" alt="カード画像"><div class="card-body"><h5 class="card-title">シンプルカード</h5><p class="card-text">基本的なカードだよ。説明をここに。</p><a href="#" class="btn btn-primary">ボタン</a></div></div>' },
      { title: 'ヘッダーフッター付きカード', content: '<div class="card"><div class="card-header">特集</div><img src="https://placehold.jp/30/dd6699/ffffff/300x200.png?text=Featured+Image" class="card-img-top" alt="特集画像"><div class="card-body"><h5 class="card-title">特別な商品</h5><p class="card-text">限定品だよ！</p></div><div class="card-footer"><a href="#" class="btn btn-success">今すぐチェック</a></div></div>' },
      { title: '横長カード', content: '<div class="card mb-3" style="max-width: 540px;"><div class="row g-0"><div class="col-md-4"><img src="https://placehold.jp/30/dd6699/ffffff/300x200.png?text=Horizontal+Image" class="img-fluid rounded-start" alt="横長画像"></div><div class="col-md-8"><div class="card-body"><h5 class="card-title">横長カード</h5><p class="card-text">画像とテキストが横に並ぶよ。</p><a href="#" class="btn btn-secondary">詳細</a></div></div></div></div>' },
      { title: 'リストグループ（画像付き）', content: '<ul class="list-group"><li class="list-group-item d-flex align-items-center"><img src="https://placehold.jp/30/dd6699/ffffff/50x50.png?text=Item+1" class="me-3" alt="アイテム1"><div><h5 class="mb-1">アイテム1</h5><p class="mb-1">説明だよ。</p></div></li><li class="list-group-item d-flex align-items-center"><img src="https://placehold.jp/30/dd6699/ffffff/50x50.png?text=Item+2" class="me-3" alt="アイテム2"><div><h5 class="mb-1">アイテム2</h5><p class="mb-1">もう一つ。</p></div></li></ul>' }
    ]
  });
</script>
<!-- JavaScript開始 -->
<script>


// === [2] 属性（Attr）管理 ===
// 新しい属性入力欄を追加する機能
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
        <div class="form-check mb-2">
            <input type="checkbox" class="form-check-input" name="variant_display[new_${Date.now()}]" id="variant_display_new_${Date.now()}" value="button_group">
            <label class="form-check-label" for="variant_display_new_${Date.now()}">Button Groupにするよ♪</label>
        </div>
    `;
    container.appendChild(entry);
}

// === [3] バリエーション生成 ===
// 属性からバリエーション（例: S-RED-SSD）を生成
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
                    <option value="">デフォルト画像だよ（画像なし）</option>
                </select>
            </td>
        `;
        container.appendChild(row);
    });
    updateImageOptions(); // 画像選択肢を更新
}

// 一括価格適用（バリエーションの価格をまとめて設定）
function applyBulkPrice() {
    const bulkPrice = document.getElementById('bulk_price').value;
    if (bulkPrice && !isNaN(bulkPrice)) {
        document.querySelectorAll('.variant-price').forEach(input => input.value = bulkPrice);
    }
}

// デカルト積計算（属性の組み合わせを生成）
function cartesian(arrays) {
    return arrays.reduce((acc, curr) => acc.flatMap(x => curr.map(y => x.concat(y))), [[]]);
}

// === [4] 画像管理 ===
// 画像選択肢を更新（バリエーションとデフォルト画像の<select>を管理）
function updateImageOptions() {
    const images = Array.from(document.querySelectorAll('#image-preview .image-item')).map((item, index) => ({
        src: item.querySelector('img').src,
        description: item.querySelector('textarea').value || `画像${index + 1}`
    }));
    document.querySelectorAll('select[name^="variant_image"]').forEach(select => {
        const initialValue = select.dataset.initialValue || '';
        console.log(`[${select.name}] Initial value: "${initialValue}"`);
        select.innerHTML = '<option value="">デフォルト画像だよ（画像なし）</option>';
        let matched = false;
        images.forEach((img, index) => {
            let relativeValue = img.src.replace(window.location.origin, '');
            if (relativeValue.startsWith(getBasePath())) {
                relativeValue = relativeValue.replace(getBasePath(), '');
            }
            relativeValue = decodeURIComponent(relativeValue);
            console.log(`[${select.name}] Option ${index + 1}: "${initialValue}" vs "${relativeValue}"`);
            const option = document.createElement('option');
            option.value = relativeValue;
            option.textContent = img.description;
            if (initialValue === relativeValue) {
                option.selected = true;
                console.log(`[${select.name}] MATCHED and selected: "${relativeValue}"`);
                matched = true;
            }
            select.appendChild(option);
        });
        if (initialValue && !matched) {
            console.warn(`[${select.name}] WARNING: No match found for initial value "${initialValue}"`);
        }
    });

    const defaultSelect = document.getElementById('default_image');
    if (defaultSelect) {
        const currentDefault = defaultSelect.value || images[0]?.src.replace(window.location.origin + getBasePath(), '');
        defaultSelect.innerHTML = images.map(img => {
            const relativeSrc = img.src.replace(window.location.origin + getBasePath(), '');
            return `<option value="${relativeSrc}" ${relativeSrc === currentDefault ? 'selected' : ''}>${img.description}</option>`;
        }).join('');
    }
}

// ベースパスを取得（PHPから動的に設定）
function getBasePath() {
    return '<?php echo get_base_path(); ?>';
}

// 画像プレビュー関連
const imageInput = document.getElementById('images');
const preview = document.getElementById('image-preview');
let nextIndex = <?php echo count($product['images']); ?>;

// 新しい画像をプレビューに追加
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

// ファイル選択時のイベント
imageInput.addEventListener('change', function(e) {
    Array.from(e.target.files).forEach(file => addImageToPreview(file));
});

// === [5] ドラッグ＆ドロップ機能 ===
// 画像の並べ替えを有効化
function enableDragAndDrop() {
    const items = document.querySelectorAll('#image-preview .image-item');
    items.forEach(item => {
        item.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('text/plain', item.dataset.index);
            e.dataTransfer.effectAllowed = 'move';
        });
        item.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        });
        item.addEventListener('drop', (e) => {
            e.preventDefault();
            const fromIndex = e.dataTransfer.getData('text/plain');
            const toIndex = item.dataset.index;
            if (fromIndex !== toIndex) {
                const fromItem = document.querySelector(`#image-preview [data-index="${fromIndex}"]`);
                const toItem = document.querySelector(`#image-preview [data-index="${toIndex}"]`);
                if (fromItem && toItem) {
                    const nextSibling = toItem.nextSibling === fromItem ? toItem : toItem.nextSibling;
                    preview.insertBefore(fromItem, nextSibling);
                    updateIndices();
                    updateImageOptions();
                }
            }
        });
    });
}

// 画像のインデックスを更新
function updateIndices() {
    const items = document.querySelectorAll('#image-preview .image-item');
    items.forEach((item, index) => {
        item.dataset.index = index;
        const textarea = item.querySelector('textarea[name="image_desc[]"]');
        if (textarea) textarea.name = `image_desc[${index}]`;
    });
}

// === [6] フォーム送信処理 ===
// 画像の順序と説明をフォームに反映
function updateImageOrder() {
    const items = document.querySelectorAll('#image-preview .image-item');
    const order = Array.from(items).map(item => item.dataset.index);
    const descriptions = Array.from(items).map(item => item.querySelector('textarea[name^="image_desc"]').value || '');
    const existingImages = Array.from(items).map(item => item.querySelector('input[name="existing_images[]"]')?.value || '');

    document.getElementById('image-order').value = JSON.stringify(order);
    document.getElementById('image-descriptions').value = JSON.stringify(descriptions);

    const form = document.getElementById('productForm');
    form.querySelectorAll('input[name="existing_images[]"]').forEach(input => input.remove());
    existingImages.forEach((img, index) => {
        if (img) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'existing_images[]';
            input.value = img;
            form.appendChild(input);
        }
    });
}

// フォーム送信時のデバッグ
function handleFormSubmit(event) {
    console.log('フォーム送信開始');
    updateImageOrder();
    const formData = new FormData(document.getElementById('productForm'));
    console.log('送信データ:');
    for (let [key, value] of formData.entries()) {
        console.log(key + ': ' + value);
    }
    return true;
}

// ドラッグ＆ドロップで画像を追加
document.body.addEventListener('dragover', (e) => {
    e.preventDefault();
    const dropZone = document.querySelector('#image-preview');
    if (dropZone) dropZone.classList.add('dragover');
});
document.body.addEventListener('dragleave', (e) => {
    e.preventDefault();
    const dropZone = document.querySelector('#image-preview');
    if (dropZone) dropZone.classList.remove('dragover');
});
document.body.addEventListener('drop', (e) => {
    e.preventDefault();
    const dropZone = document.querySelector('#image-preview');
    if (dropZone) dropZone.classList.remove('dragover');
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        const dataTransfer = new DataTransfer();
        Array.from(files).forEach(file => {
            if (file.type.startsWith('image/')) {
                addImageToPreview(file);
                dataTransfer.items.add(file);
            }
        });
        imageInput.files = dataTransfer.files;
    }
});

// === [7] ページ初期化 ===
// ページ読み込み時の設定
document.addEventListener('DOMContentLoaded', () => {
    enableDragAndDrop();
    window.addEventListener('load', () => {
        console.log('Window loaded, running updateImageOptions');
        updateImageOptions();
    });
});

</script>
<!-- JavaScript終了 -->

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

<?php if (!empty($result)): ?>
    <?php if ($result['success']): ?>
        <div class="alert alert-success cure-sky-alert"><?php echo $result['message']; ?></div>
    <?php else: ?>
        <div class="alert alert-danger">
            <?php echo $result['message']; ?>
            <pre>エラーデータ: <?php print_r($result['post_data']); ?></pre>
        </div>
    <?php endif; ?>
<?php endif; ?>