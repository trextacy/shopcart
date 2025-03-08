<?php
function handle_image_upload($files) {
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            error_log("Failed to create upload directory: $upload_dir");
            return []; // ディレクトリ作成に失敗した場合、空配列を返す
        }
    }

    $uploaded_images = [];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

    // $files が正しい構造か確認
    if (!isset($files['name']) || !is_array($files['name'])) {
        error_log("Invalid file upload data structure");
        return [];
    }

    foreach ($files['name'] as $key => $name) {
        if ($files['error'][$key] === UPLOAD_ERR_OK) {
            $tmp_name = $files['tmp_name'][$key];
            $type = $files['type'][$key];
            if (in_array($type, $allowed_types)) {
                $filename = uniqid() . '_' . sanitize_filename(basename($name));
                $destination = $upload_dir . $filename;
                if (move_uploaded_file($tmp_name, $destination)) {
                    $uploaded_images[] = $destination;
                } else {
                    error_log("Failed to move uploaded file to: $destination");
                }
            } else {
                error_log("Unsupported file type: $type for file: $name");
            }
        } elseif ($files['error'][$key] !== UPLOAD_ERR_NO_FILE) {
            error_log("Upload error code {$files['error'][$key]} for file: $name");
        }
    }
    return $uploaded_images;
}

// ファイル名を安全にする補助関数
function sanitize_filename($filename) {
    $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $filename);
    return $filename ?: 'unnamed';
}