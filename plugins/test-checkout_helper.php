<?php
function render_checkout_form($customer_info = []) {
    $payment_methods = ['bank_transfer' => '郵便振込', 'cash_on_delivery' => '代引き']; // '銀行振込'を'郵便振込'に修正
    ob_start();
    ?>
    <form method="post" action="checkout.php" id="checkoutForm">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="mb-3">
            <label for="name" class="form-label">氏名</label>
            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($customer_info['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="mb-3">
            <label for="kana" class="form-label">フリガナ</label>
            <input type="text" class="form-control" id="kana" name="kana" value="<?php echo htmlspecialchars($customer_info['kana'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="mb-3">
            <label for="postal_code" class="form-label">郵便番号（ハイフンなし）</label>
            <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($customer_info['postal_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="7" pattern="\d{7}" required>
        </div>
        <div class="mb-3">
            <label for="prefecture" class="form-label">都道府県</label>
            <input type="text" class="form-control" id="prefecture" name="prefecture" value="<?php echo htmlspecialchars($customer_info['prefecture'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="mb-3">
            <label for="address" class="form-label">住所</label>
            <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($customer_info['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="mb-3">
            <label for="building" class="form-label">建物名・部屋番号（任意）</label>
            <input type="text" class="form-control" id="building" name="building" value="<?php echo htmlspecialchars($customer_info['building'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="mb-3">
            <label for="phone" class="form-label">電話番号</label>
            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($customer_info['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" pattern="\d{10,11}" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Emailアドレス</label>
            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($customer_info['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="mb-3">
            <label for="comments" class="form-label">通信欄（400文字以内、任意）</label>
            <textarea class="form-control" id="comments" name="comments" rows="4" maxlength="400"><?php echo htmlspecialchars($customer_info['comments'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>
        <div class="mb-3">
            <label for="payment_method" class="form-label">支払い方法</label>
            <select class="form-select" id="payment_method" name="payment_method" required>
                <option value="">選択してください</option>
                <?php foreach ($payment_methods as $value => $label): ?>
                    <option value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($customer_info['payment_method'] ?? '') === $value ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">注文内容を確認する</button>
    </form>

    <script>
    document.getElementById('postal_code').addEventListener('blur', function() {
        const postalCode = this.value.replace(/[^\d]/g, '');
        if (postalCode.length === 7) {
            fetch(`https://zipcloud.ibsnet.co.jp/api/search?zipcode=${postalCode}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 200 && data.results) {
                        const result = data.results[0];
                        document.getElementById('prefecture').value = result.prefcode ? result.address1 : '';
                        document.getElementById('address').value = result.address2 + result.address3;
                    } else {
                        alert('住所が見つかりませんでした。');
                    }
                })
                .catch(error => console.error('Error:', error));
        }
    });
    </script>
    <?php
    return ob_get_clean();
}

function process_checkout_data($post_data) {
    $customer_info = [
        'name' => $post_data['name'] ?? '',
        'kana' => $post_data['kana'] ?? '',
        'postal_code' => $post_data['postal_code'] ?? '',
        'prefecture' => $post_data['prefecture'] ?? '',
        'address' => $post_data['address'] ?? '',
        'building' => $post_data['building'] ?? '',
        'phone' => $post_data['phone'] ?? '',
        'email' => $post_data['email'] ?? '',
        'comments' => $post_data['comments'] ?? '',
        'payment_method' => $post_data['payment_method'] ?? ''
    ];
    return $customer_info;
}

function display_customer_info($customer_info) {
    $payment_methods = ['bank_transfer' => '郵便振込', 'cash_on_delivery' => '代引き'];
    ob_start();
    ?>
    <ul class="list-group mb-4">
        <li class="list-group-item">氏名: <?php echo htmlspecialchars($customer_info['name'], ENT_QUOTES, 'UTF-8'); ?></li>
        <li class="list-group-item">フリガナ: <?php echo htmlspecialchars($customer_info['kana'], ENT_QUOTES, 'UTF-8'); ?></li>
        <li class="list-group-item">郵便番号: <?php echo htmlspecialchars($customer_info['postal_code'], ENT_QUOTES, 'UTF-8'); ?></li>
        <li class="list-group-item">都道府県: <?php echo htmlspecialchars($customer_info['prefecture'], ENT_QUOTES, 'UTF-8'); ?></li>
        <li class="list-group-item">住所: <?php echo htmlspecialchars($customer_info['address'], ENT_QUOTES, 'UTF-8'); ?></li>
        <li class="list-group-item">建物名: <?php echo htmlspecialchars($customer_info['building'] ?? '', ENT_QUOTES, 'UTF-8'); ?></li>
        <li class="list-group-item">電話番号: <?php echo htmlspecialchars($customer_info['phone'], ENT_QUOTES, 'UTF-8'); ?></li>
        <li class="list-group-item">Email: <?php echo htmlspecialchars($customer_info['email'], ENT_QUOTES, 'UTF-8'); ?></li>
        <li class="list-group-item">通信欄: <?php echo htmlspecialchars($customer_info['comments'] ?? '', ENT_QUOTES, 'UTF-8'); ?></li>
        <li class="list-group-item">支払い方法: <?php echo htmlspecialchars($payment_methods[$customer_info['payment_method']] ?? '不明', ENT_QUOTES, 'UTF-8'); ?></li>
    </ul>
    <?php
    return ob_get_clean();
}

function render_cart_summary($cart_items, $products, $customer_info = []) {
    $total_price = 0;
    foreach ($cart_items as $item) {
        $product = $products[$item['product_id']] ?? null;
        if ($product && isset($product['variants'][$item['variant']])) {
            $price = $product['variants'][$item['variant']]['price'] ?? 0;
            $total_price += $price * $item['quantity'];
        }
    }

    $shipping_and_fees = calculate_shipping_and_fees($total_price, $customer_info);
    $shipping_fee = $shipping_and_fees['shipping_fee'];
    $cod_fee = $shipping_and_fees['cod_fee'];
    $grand_total = $total_price + $shipping_fee + $cod_fee;

    ob_start();
    ?>
    <h4 class="mb-3">カート内容</h4>
    <ul class="list-group mb-4">
        <?php foreach ($cart_items as $item):
            $product = $products[$item['product_id']] ?? null;
            if ($product && isset($product['variants'][$item['variant']])):
                $price = $product['variants'][$item['variant']]['price'] ?? 0;
                $subtotal = $price * $item['quantity'];
        ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <img src="<?php echo htmlspecialchars($product['images'][0] ?? 'https://placehold.jp/60x60.png', ENT_QUOTES, 'UTF-8'); ?>" 
                             alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>" 
                             class="img-thumbnail me-2" style="width: 60px; height: 60px;">
                        <div>
                            <h6 class="my-0"><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h6>
                            <small class="text-muted">
                                バリアント: <?php echo htmlspecialchars($item['variant'], ENT_QUOTES, 'UTF-8'); ?><br>
                                数量: <?php echo $item['quantity']; ?>
                            </small>
                        </div>
                    </div>
                    <span class="text-muted"><?php echo number_format($subtotal); ?>円</span>
                </li>
        <?php endif; endforeach; ?>
        <li class="list-group-item d-flex justify-content-between">
            <span>商品合計</span>
            <span><?php echo number_format($total_price); ?>円</span>
        </li>
        <li class="list-group-item d-flex justify-content-between">
            <span>送料</span>
            <span><?php echo number_format($shipping_fee); ?>円</span>
        </li>
        <?php if ($cod_fee > 0): ?>
            <li class="list-group-item d-flex justify-content-between">
                <span>代引き手数料</span>
                <span><?php echo number_format($cod_fee); ?>円</span>
            </li>
        <?php endif; ?>
        <li class="list-group-item d-flex justify-content-between">
            <strong>総合計</strong>
            <strong><?php echo number_format($grand_total); ?>円</strong>
        </li>
    </ul>
    <?php
    return ob_get_clean();
}

function calculate_shipping_and_fees($total_price, $customer_info = []) {
    $shipping_fee = 0;
    $cod_fee = 0;
    $payment_method = $customer_info['payment_method'] ?? '';
    $prefecture = $customer_info['prefecture'] ?? '';

    if ($payment_method === 'cash_on_delivery') {
        $shipping_fee = 770; // 代引き: 全国一律770円
        $cod_fee = 500; // 代引き手数料: 一律500円
        if ($total_price >= 11000) {
            $shipping_fee = 0; // 11,000円以上で送料無料
            $cod_fee = 0; // 11,000円以上で手数料無料
        }
    } elseif ($payment_method === 'bank_transfer') {
        $remote_areas = ['北海道', '沖縄', '福岡', '佐賀', '長崎', '熊本', '大分', '宮崎', '鹿児島']; // 九州と北海道・沖縄
        $shipping_fee = in_array($prefecture, $remote_areas) ? 1100 : 770; // 本州770円、北海道・沖縄・九州1100円
        if ($total_price >= 11000) {
            $shipping_fee = 0; // 11,000円以上で送料無料
        }
    }

    return ['shipping_fee' => $shipping_fee, 'cod_fee' => $cod_fee];
}
?>