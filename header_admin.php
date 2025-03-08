<?php
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($page_description ?? 'trextacy.com - 管理画面', ENT_QUOTES, 'UTF-8'); ?>">
    <title><?php echo htmlspecialchars($page_title ?? 'trextacy.com - 管理画面', ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-md navbar-light bg-light fixed-top">
            <div class="container-fluid">
                <a class="navbar-brand" href="product_list.php">trextacy.com 管理</a>
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="logout.php">ログアウト</a>
                </div>
            </div>
        </nav>
    </header>
    <main class="container-fluid p-0">