<?php
require_once 'plugins/functions.php'; // get_base_path()用

// CSVファイルを読み込む
$csv_file = 'Items.csv';
$products = [];

if (file_exists($csv_file)) {
    $file = fopen($csv_file, 'r');
    $line_number = 0;
    while (($row = fgetcsv($file)) !== false) {
        $line_number++;
        // 空行や列数が5未満ならスキップ
        if (empty($row) || count($row) < 5 || trim(implode('', $row)) === '') {
            echo "警告: 行 $line_number は空かデータが足りません（列数: " . count($row) . "）: '" . implode(',', $row) . "'<br>";
            continue;
        }

        // デバッグ用: 全列を表示
        echo "行 $line_number: " . implode(' | ', $row) . "<br>";

        // 列を分解
        $id = $row[0] ?? ''; // 商品番号
        $name = $row[2] ?? ''; // 商品名
        $price = (int)($row[4] ?? 0); // 税込み価格
        $colors = !empty($row[5]) ? explode('<>', $row[5]) : []; // カラー
        $lr = !empty($row[6]) ? explode('<>', $row[6]) : []; // 左右
        $size_a = !empty($row[7]) ? explode('<>', $row[7]) : []; // サイズA
        $size_b = !empty($row[8]) ? explode('<>', $row[8]) : []; // サイズB
        $image_tag = $row[9] ?? ''; // 画像タグ

        // デバッグ用: $image_tagの中身を確認
        echo "行 $line_number のimage_tag: '$image_tag'<br>";

        // 画像URLとALTを抽出（クオートなし対応）
        preg_match('/src=([^\s"\']+)/', $image_tag, $src_matches);
        $image_url = $src_matches[1] ?? 'https://placehold.jp/300x300.png';
        preg_match('/alt=["\'](.*?)["\']/', $image_tag, $alt_matches);
        $image_alt = $alt_matches[1] ?? $name;

        // デバッグ用: 画像URLが取れてるか確認
        if ($image_url === 'https://placehold.jp/300x300.png') {
            echo "警告: 行 $line_number の画像URLが取れてないよ: '$image_tag'<br>";
        } else {
            echo "成功: 行 $line_number の画像URL: '$image_url'<br>";
        }

        // attributesを構築
        $attributes = [];
        if ($colors) $attributes['カラー'] = $colors;
        if ($lr) $attributes['左右'] = $lr;
        if ($size_a) $attributes['サイズ'] = $size_a;
        if ($size_b && !$size_a) $attributes['サイズ'] = $size_b;

        // variantsを構築
        $variants = [];
        $combinations = [];
        $attr_keys = array_keys($attributes);
        $attr_values = array_values($attributes);

        if (count($attr_values) > 0) {
            $combinations = [[]];
            foreach ($attr_values as $values) {
                $new_combinations = [];
                foreach ($combinations as $combo) {
                    foreach ($values as $value) {
                        $new_combinations[] = array_merge($combo, [$value]);
                    }
                }
                $combinations = $new_combinations;
            }
            foreach ($combinations as $combo) {
                $variant_key = implode('-', $combo);
                $variants[$variant_key] = [
                    'price' => $price,
                    'sold_out' => false,
                    'image' => $image_url
                ];
            }
        } else {
            $variants['default'] = [
                'price' => $price,
                'sold_out' => false,
                'image' => $image_url
            ];
        }

        // products.jsonのエントリを作成
        $products[$id] = [
            'name' => $name,
            'description' => $name,
            'lead' => mb_substr($name, 0, 20, 'UTF-8') . '...',
            'category' => '',
            'tags' => [],
            'images' => [$image_url],
            'image_descriptions' => [$image_alt],
            'default_image' => $image_url,
            'is_public' => true,
            'variants' => $variants,
            'attributes' => $attributes
        ];
    }
    fclose($file);

    // JSONに保存
    file_put_contents('csvitem.json', json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "Items.csvをcsvitem.jsonに変換したよ！<br>";
} else {
    echo "Items.csvが見つからないよ！<br>";
}

// 結果をちょっと確認
echo "<pre>";
print_r(array_slice($products, 0, 2));
echo "</pre>";
?>