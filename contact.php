<?php
session_start();
require_once 'plugins/functions.php';
require_once 'plugins/variants.php'; // variants.php も必要（header.php のカテゴリーメニューで使う）

$base_path = get_base_path();
generate_csrf_token();
$products = load_products(); // 商品データを読み込む

$result = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $result = ['success' => false, 'message' => 'セキュリティエラーです。'];
    } else {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $message = $_POST['message'] ?? '';
        if (empty($name) || empty($email) || empty($message)) {
            $result = ['success' => false, 'message' => 'すべての項目を入力してください。'];
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $result = ['success' => false, 'message' => '有効なメールアドレスを入力してください。'];
        } else {
            // ここでメール送信やデータ保存を実装（仮に成功とする）
            $result = ['success' => true, 'message' => 'お問い合わせを送信しました。'];
        }
    }
}

$page_title = 'お問い合わせ - trextacy.com';
$page_description = 'お問い合わせフォームです。お気軽にご質問ください。';
include 'header.php';
?>

<div class="d-flex flex-column min-vh-100">
    <main class="flex-grow-1">
        <div class="container py-5">
            <h1 class="fw-bold mb-4">お問い合わせ</h1>
            <?php if (!empty($result)): ?>
                <div class="alert <?php echo $result['success'] ? 'alert-success' : 'alert-danger'; ?>">
                    <?php echo $result['message']; ?>
                </div>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="mb-3">
                    <label for="name" class="form-label">お名前</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">メールアドレス</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="message" class="form-label">メッセージ</label>
                    <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">送信</button>
            </form>
        </div>
    </main>
    <?php include 'footer.php'; ?>
</div>

<style>
body {
    font-family: 'Noto Sans JP', sans-serif;
    background: #f5f5f5;
}
.btn-primary {
    background: #007bff;
    border: none;
    padding: 10px 20px;
}
</style>