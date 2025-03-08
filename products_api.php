<?php
// [Grok Instruction]
// Do not modify $_SESSION['authenticated'] logic - affects all admin pages.
// Do not change $products structure - impacts frontend display.
// Added CSRF token verification for security.

session_start();
require_once 'plugins/functions.php';
$config = require_once 'config/admin-config.php';

$base_path = get_base_path(); // 今後画像パスが必要なら使用

// 認証チェック（管理者アクセスの場合）
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => '認証が必要です']);
    exit;
}

// CSRFトークン検証
$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verify_csrf_token($csrf_token)) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'CSRFトークンが無効です']);
    exit;
}

// 商品データを取得
$products = load_products();

// JSON形式で出力
header('Content-Type: application/json');
echo json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>