<?php
header('Content-Type: application/json');
error_log('upload_image.php が呼ばれました');

$upload_dir = 'uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
    error_log("ディレクトリを作成: $upload_dir");
}

if (isset($_FILES['file'])) {
    $file = $_FILES['file'];
    error_log('アップロードされたファイル: ' . print_r($file, true));
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    error_log("MIMEタイプ: $mime");
    
    if (strpos($mime, 'image/') === 0) {
        $filename = uniqid() . '_' . basename($file['name']);
        $target_path = $upload_dir . $filename;
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            $base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
            $location = $base_path . '/' . $target_path;
            error_log("アップロード成功: $location");
            echo json_encode(['location' => $location]);
            exit;
        } else {
            error_log("ファイル移動失敗: $target_path");
            http_response_code(500);
            echo json_encode(['error' => 'アップロードに失敗したよ']);
            exit;
        }
    } else {
        error_log("画像じゃないよ: $mime");
        http_response_code(500);
        echo json_encode(['error' => '画像じゃないよ']);
        exit;
    }
} else {
    error_log('ファイルが見つからないよ');
    http_response_code(400);
    echo json_encode(['error' => 'ファイルがないよ']);
    exit;
}
?>