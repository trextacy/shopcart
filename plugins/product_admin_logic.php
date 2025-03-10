<?php

function handle_product_admin_submission() {
    error_log('POST Data: ' . print_r($_POST, true));

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return ['success' => false, 'message' => 'お手紙の送り方が違うよ'];
    }

    $product_id = $_POST['product_id'] ?? '';
    if (!preg_match('/^[a-zA-Z0-9]+$/', $product_id)) {
        return ['success' => false, 'message' => 'おもちゃの名前はアルファベットと数字だけだよ'];
    }

    if (empty($_POST['name'])) {
        error_log('Error: 商品名が空です');
        return ['success' => false, 'message' => 'おもちゃの名前を書いてね'];
    }

    $default_image = $_POST['default_image'] ?? '';
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $lead = $_POST['lead'] ?? '';
    $category = $_POST['category'] ?? '';
    $is_public = isset($_POST['is_public']) && $_POST['is_public'] === '1';
    $attributes = [];
    $variants = [];
    $images = [];
    $tags = array_filter(explode(',', $_POST['tags'] ?? ''));
    $image_descriptions = json_decode($_POST['image_descriptions'] ?? '[]', true) ?: [];
    $image_order = json_decode($_POST['image_order'] ?? '[]', true) ?: [];

    $products = json_decode(file_get_contents('products.json'), true) ?? [];
    $existing_product = $products[$product_id] ?? [];
    $existing_images = $existing_product['images'] ?? [];
    $existing_image_descriptions = $existing_product['image_descriptions'] ?? [];
    $existing_registered_date = $existing_product['registered_date'] ?? date('Y-m-d');

    // 属性
    $attr_names = $_POST['attr_name'] ?? [];
    $attr_values = $_POST['attr_values'] ?? [];
    $attr_variant_displays = $_POST['variant_display'] ?? [];
    foreach ($attr_names as $index => $attr_name) {
        if (!empty($attr_name) && !empty($attr_values[$index])) {
            $values = array_filter(explode(',', trim($attr_values[$index])));
            if (!empty($values)) {
                $attributes[$attr_name] = [
                    'values' => $values,
                    'variant_display' => isset($attr_variant_displays[$attr_name]) && $attr_variant_displays[$attr_name] === 'button_group' ? 'button_group' : 'select'
                ];
            }
        }
    }

    // バリアント
    $variants = []; // ここを空にして、前の値を引き継がない！
    $variant_keys = $_POST['variant_key'] ?? [];
    $variant_prices = $_POST['variant_price'] ?? [];
    $variant_sold_outs = $_POST['variant_sold_out'] ?? [];
    $variant_images = $_POST['variant_image'] ?? [];
foreach ($variant_keys as $index => $key) {
    if (!empty($key) && isset($variant_prices[$index]) && is_numeric($variant_prices[$index])) {
        $variants[$key] = [
            'price' => (int)$variant_prices[$index],
            'sold_out' => isset($variant_sold_outs[$key]) && $variant_sold_outs[$key] === '1',
            'image' => isset($variant_images[$key]) ? ($variant_images[$key] === '' ? null : urldecode(str_replace('http://localhost' . get_base_path(), '', $variant_images[$key]))) : null
        ];
    }
}


    // 画像
    $submitted_existing_images = $_POST['existing_images'] ?? [];
    if (!empty(array_filter($_FILES['images']['name'] ?? []))) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $new_images = [];
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $file_name = uniqid() . '_' . basename($_FILES['images']['name'][$key]);
                if (move_uploaded_file($tmp_name, $upload_dir . $file_name)) {
                    $new_images[] = $upload_dir . $file_name;
                }
            }
        }
        $images = array_merge($submitted_existing_images, $new_images);
        $image_descriptions = array_merge(
            array_slice($image_descriptions, 0, count($submitted_existing_images)),
            array_fill(0, count($new_images), '')
        );
    } else {
        $images = $submitted_existing_images;
        $image_descriptions = $image_descriptions ?: $existing_image_descriptions;
    }

    // 画像並び替え
    if (!empty($image_order)) {
        $sorted_images = [];
        $sorted_descriptions = [];
        foreach ($image_order as $index) {
            $index = (int)$index;
            if (isset($images[$index])) {
                $sorted_images[] = $images[$index];
                $sorted_descriptions[] = $image_descriptions[$index] ?? '';
            }
        }
        $images = $sorted_images;
        $image_descriptions = $sorted_descriptions;
    }

    // デフォルト画像
    if (empty($images)) {
        $images = ['https://placehold.jp/300x300.png'];
        $image_descriptions = ['デフォルト画像'];
        $default_image = $images[0];
    }
    if (empty($default_image) || !in_array($default_image, $images)) {
        $default_image = $images[0];
    }

    // 商品データ更新
    $products[$product_id] = [
        'default_image' => $default_image,
        'name' => $name,
        'description' => $description,
        'lead' => $lead,
        'category' => $category,
        'is_public' => $is_public,
        'images' => $images,
        'image_descriptions' => $image_descriptions,
        'attributes' => $attributes,
        'variants' => $variants,
        'tags' => $tags,
        'registered_date' => $existing_registered_date
    ];
    if (empty($existing_product)) {
        $products[$product_id]['registered_date'] = date('Y-m-d');
    }

    // 保存
    $json_data = json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json_data === false) {
        error_log('Error: JSONエンコードに失敗: ' . json_last_error_msg());
        return ['success' => false, 'message' => 'データが変えられなかったよ'];
    }
    if (!file_put_contents('products.json', $json_data)) {
        error_log('Error: products.jsonへの書き込み失敗: ' . error_get_last()['message']);
        return ['success' => false, 'message' => 'おもちゃ箱に保存できなかったよ'];
    }

    return ['success' => true, 'message' => 'おもちゃ箱を更新したよ！'];
}

function delete_product($product_id) {
    $products = json_decode(file_get_contents('products.json'), true) ?? [];
    if (isset($products[$product_id])) {
        unset($products[$product_id]);
        file_put_contents('products.json', json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return ['success' => true, 'message' => '商品を削除しました。'];
    }
    return ['success' => false, 'message' => '商品が見つかりません。'];
}

function toggle_variant_sold_out($product_id, $variant_key) {
    $products = json_decode(file_get_contents('products.json'), true) ?? [];
    if (isset($products[$product_id]['variants'][$variant_key])) {
        $current = $products[$product_id]['variants'][$variant_key]['sold_out'];
        $products[$product_id]['variants'][$variant_key]['sold_out'] = !$current;
        file_put_contents('products.json', json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return ['success' => true, 'message' => '在庫状態を更新しました。'];
    }
    return ['success' => false, 'message' => 'バリアントが見つかりません。'];
}

function get_existing_products() {
    return json_decode(file_get_contents('products.json'), true) ?? [];
}

function toggle_product_public($product_id) {
    $products = json_decode(file_get_contents('products.json'), true) ?? [];
    if (isset($products[$product_id])) {
        $current = $products[$product_id]['is_public'] ?? true;
        $products[$product_id]['is_public'] = !$current;
        file_put_contents('products.json', json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return ['success' => true, 'message' => '公開状態を更新しました。', 'is_public' => !$current];
    }
    return ['success' => false, 'message' => '商品が見つかりません。'];
}

?>