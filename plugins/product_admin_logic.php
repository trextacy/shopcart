<?php
function handle_product_admin_submission() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return ['success' => false];
    }

    $product_id = $_POST['product_id'] ?? '';
    $default_image = $_POST['default_image'] ?? '';
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $lead = $_POST['lead'] ?? '';
    $category = $_POST['category'] ?? '';
    $is_public = isset($_POST['is_public']) && $_POST['is_public'] === '1';
    $attributes = [];
    $variants = []; // 初期化しないで後でマージ
    $images = [];
    $tags = array_filter(explode(',', $_POST['tags'] ?? ''));
    $image_descriptions = json_decode($_POST['image_descriptions'] ?? '[]', true) ?: [];
    $image_order = json_decode($_POST['image_order'] ?? '[]', true) ?: [];

    $products = json_decode(file_get_contents('products.json'), true) ?? [];
    $existing_product = $products[$product_id] ?? [];
    $existing_images = $existing_product['images'] ?? [];
    $existing_image_descriptions = $existing_product['image_descriptions'] ?? [];
    $existing_registered_date = $existing_product['registered_date'] ?? date('Y-m-d');

    if (isset($_POST['copy']) && $_POST['copy'] === '1') {
        $new_product_id = 'copy_' . $product_id . '_' . uniqid();
        $products[$new_product_id] = $products[$product_id];
        $products[$new_product_id]['is_public'] = false;
        $products[$new_product_id]['registered_date'] = date('Y-m-d');
        file_put_contents('products.json', json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return ['success' => true, 'message' => "商品をコピーしました。新しいID: $new_product_id"];
    }

    // 属性をシンプルに
    $attr_names = $_POST['attr_name'] ?? [];
    $attr_values = $_POST['attr_values'] ?? [];
    foreach ($attr_names as $index => $attr_name) {
        if (!empty($attr_name) && !empty($attr_values[$index])) {
            $values = array_filter(explode(',', trim($attr_values[$index])));
            if (!empty($values)) {
                $attributes[$attr_name] = $values;
            }
        }
    }

    // 既存のバリアントをベースにする
    $variants = $existing_product['variants'] ?? [];
    $variant_keys = $_POST['variant_key'] ?? [];
    $variant_prices = $_POST['variant_price'] ?? [];
    $variant_sold_outs = $_POST['variant_sold_out'] ?? [];
    $variant_images = $_POST['variant_image'] ?? [];
    foreach ($variant_keys as $index => $key) {
        if (!empty($key) && isset($variant_prices[$index]) && is_numeric($variant_prices[$index])) {
            $variants[$key] = [
                'price' => (int)$variant_prices[$index],
                'sold_out' => isset($variant_sold_outs[$key]) && $variant_sold_outs[$key] === '1',
                'image' => !empty($variant_images[$key]) ? $variant_images[$key] : ($variants[$key]['image'] ?? null)
            ];
        }
    }

    $submitted_existing_images = $_POST['existing_images'] ?? [];
    $submitted_new_images = $_POST['new_images'] ?? [];
    $new_image_descriptions = json_decode($_POST['image_descriptions'] ?? '[]', true) ?: [];
    if (!empty(array_filter($_FILES['images']['name'] ?? []))) {
        require_once 'plugins/file_upload.php';
        $new_images = handle_image_upload($_FILES['images']);
        $images = array_merge($submitted_existing_images, $new_images);
        $image_descriptions = array_merge(
            $existing_image_descriptions,
            array_slice($new_image_descriptions, count($submitted_existing_images), count($new_images))
        );
    } else {
        $images = $submitted_existing_images;
        $image_descriptions = $new_image_descriptions ?: $existing_image_descriptions;
    }

    if (!empty($image_order)) {
        $sorted_images = [];
        $sorted_descriptions = [];
        foreach ($image_order as $i => $index) {
            if (isset($images[$index])) {
                $sorted_images[] = $images[$index];
                $sorted_descriptions[] = $image_descriptions[$index] ?? '';
            }
        }
        $images = $sorted_images;
        $image_descriptions = $sorted_descriptions;
    }

    if (empty($images)) {
        $images = ['https://placehold.jp/300x300.png'];
        $image_descriptions = ['デフォルト画像'];
        $default_image = $images[0];
    }

    if (empty($default_image) || !in_array($default_image, $images)) {
        $default_image = $images[0];
    }

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

    file_put_contents('products.json', json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return ['success' => true, 'message' => '商品を更新しました。'];
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