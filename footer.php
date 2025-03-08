<?php
$categories = array_unique(array_map(fn($p) => $p['category'], load_products()));
?>
<footer class="bg-dark text-white py-5">
    <div class="container">
        <div class="row g-4">
            <!-- カテゴリー -->
            <div class="col-md-3 col-sm-6">
                <h5 class="mb-3">カテゴリー</h5>
                <ul class="list-unstyled">
                    <?php foreach ($categories as $cat): ?>
                        <li><a href="index.php?category=<?php echo urlencode($cat); ?>" class="text-white text-decoration-none hover-effect"><?php echo htmlspecialchars($cat); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <!-- サポート -->
            <div class="col-md-3 col-sm-6">
                <h5 class="mb-3">サポート</h5>
                <ul class="list-unstyled">
                    <li><a href="contact.php" class="text-white text-decoration-none hover-effect">お問い合わせ</a></li>
                    <li><a href="#" class="text-white text-decoration-none hover-effect">配送について</a></li>
                    <li><a href="#" class="text-white text-decoration-none hover-effect">返品ポリシー</a></li>
                </ul>
            </div>
            <!-- ソーシャル -->
            <div class="col-md-3 col-sm-6">
                <h5 class="mb-3">フォローしてね</h5>
                <a href="#" class="text-white me-3"><i class="bi bi-twitter fs-4"></i></a>
                <a href="#" class="text-white me-3"><i class="bi bi-instagram fs-4"></i></a>
                <a href="#" class="text-white"><i class="bi bi-facebook fs-4"></i></a>
            </div>
            <!-- ニュースレター -->
            <div class="col-md-3 col-sm-6">
                <h5 class="mb-3">ニュースレター</h5>
                <form>
                    <input type="email" class="form-control mb-2" placeholder="メールアドレス">
                    <button type="submit" class="btn btn-primary w-100">登録する</button>
                </form>
            </div>
        </div>
        <div class="text-center mt-4">
            <p class="mb-0">&copy; 2025 trextacy.com All Rights Reserved.</p>
        </div>
    </div>
</footer>