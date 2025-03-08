<!-- templates/product_admin_template.php -->
<?php include 'header.php'; ?>
<div class="container">
    <h1 class="mt-4">商品登録</h1>
    <?php if ($result['success']): ?>
        <p class="text-success"><?php echo $result['message']; ?></p>
    <?php elseif (!empty($result['message'])): ?>
        <p class="text-danger"><?php echo $result['message']; ?></p>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" id="productForm" onsubmit="return false;">
        <div class="mb-3">
            <label for="product_id" class="form-label">商品ID（半角英数字）</label>
            <input type="text" class="form-control" id="product_id" name="product_id" placeholder="例: product01" required>
        </div>
        <div class="mb-3">
            <label for="name" class="form-label">商品名</label>
            <input type="text" class="form-control" id="name" name="name" placeholder="例: オリジナルTシャツ" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">商品説明</label>
            <textarea class="form-control" id="description" name="description" placeholder="例: 着心地の良いコットン100%のTシャツ" required></textarea>
        </div>
        <div class="mb-3">
            <label for="lead" class="form-label">リード文（10～15文字の短文）</label>
            <input type="text" class="form-control" id="lead" name="lead" maxlength="15" placeholder="例: おいしいみかんだよ">
        </div>
        <div class="mb-3">
            <label for="category" class="form-label">カテゴリー</label>
            <input type="text" class="form-control" id="category" name="category" placeholder="例: フルーツ、PCパーツ">
            <small class="text-muted">商品のカテゴリーを入力してください（任意）。</small>
        </div>
        <!-- (属性、バリアント、画像のフォームは省略、前回のコードを使ってね) -->
        <button type="button" id="submitButton" class="btn btn-primary">商品を登録</button>
        <a href="product_list.php" class="btn btn-info ms-2">商品リストを見る</a>
    </form>

    <h2 class="mt-5">カスタムCSS編集</h2>
    <form method="post" id="cssForm">
        <div class="mb-3">
            <label for="custom_css" class="form-label">カスタムCSS</label>
            <textarea class="form-control" id="custom_css" name="custom_css" rows="10"><?php echo htmlspecialchars($custom_css, ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>
        <button type="submit" class="btn btn-success">CSSを保存</button>
    </form>

    <a href="logout.php" class="btn btn-secondary ms-2 mt-3">ログアウト</a>
</div>

<script>
let attrIndex = 0;
// (JavaScriptは前回のまま、略)
</script>
<link rel="stylesheet" href="styles/custom.css">
<?php include 'footer.php'; ?>