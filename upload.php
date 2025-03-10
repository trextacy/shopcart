<?php
$uploadDir = __DIR__ . '/uploads/';
if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

$file = $_FILES['file'];
$filename = $file['name']; // 日本語名OK
$destination = $uploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $destination)) {
    echo json_encode([
        'location' => "/shopcart/uploads/$filename"
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Upload failed']);
}
?>