<?php
require_once 'plugins/variants.php';
$config = require_once dirname(__DIR__) . '/config/admin-config.php';

function render_checkout_form($customer_info = [], $cart_items = [], $products = []) {
    global $config;
    $payment_methods = $config['payment_methods'];
    $total_price = 0;
    foreach ($cart_items as $item) {
        $product = $products[$item['product_id']] ?? null;
        if ($product && isset($product['variants'][$item['variant']])) {
            $price = $product['variants'][$item['variant']]['price'] ?? 0;
            $total_price += $price * $item['quantity'];
        }
    }
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
            <label for="postal_code" class="form-label">郵便番号（ハイフンなし、7桁数字のみ）</label>
            <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($customer_info['postal_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                   maxlength="7" pattern="\d{7}" required>
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
            <label for="phone" class="form-label">電話番号（数字10-11桁のみ）</label>
            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($customer_info['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                   pattern="\d{10,11}" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Emailアドレス（半角英数記号のみ）</label>
            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($customer_info['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                   pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}" required>
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
                    <option value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>" <?php echo isset($customer_info['payment_method']) && $customer_info['payment_method'] === $value ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="delivery_time" class="form-label">配送日時</label>
            <select class="form-select" id="delivery_time" name="delivery_time" required>
                <option value="">選択してください</option>
                <option value="morning" <?php echo isset($customer_info['delivery_time']) && $customer_info['delivery_time'] === 'morning' ? 'selected' : ''; ?>>午前中</option>
                <option value="14-16" <?php echo isset($customer_info['delivery_time']) && $customer_info['delivery_time'] === '14-16' ? 'selected' : ''; ?>>14:00-16:00</option>
                <option value="16-18" <?php echo isset($customer_info['delivery_time']) && $customer_info['delivery_time'] === '16-18' ? 'selected' : ''; ?>>16:00-18:00</option>
                <option value="18-20" <?php echo isset($customer_info['delivery_time']) && $customer_info['delivery_time'] === '18-20' ? 'selected' : ''; ?>>18:00-20:00</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">カタログ冊子の送付</label>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="catalog_request" name="catalog_request" value="1" <?php echo isset($customer_info['catalog_request']) && $customer_info['catalog_request'] ? 'checked' : ''; ?>>
                <label class="form-check-label" for="catalog_request">希望する</label>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">注文内容を確認する</button>
    </form>

<script>
const config = <?php echo json_encode($config['shipping'], JSON_UNESCAPED_UNICODE); ?>;
const currency = '<?php echo $config['currency']; ?>';
const totalPrice = <?php echo $total_price; ?>;

function updateCartSummary() {
    const paymentMethodEl = document.getElementById('payment_method');
    const prefectureEl = document.getElementById('prefecture');
    const shippingFeeEl = document.getElementById('shipping-fee');
    const codFeeEl = document.getElementById('cod-fee');
    const grandTotalEl = document.getElementById('grand-total');
    const footerGrandTotalEl = document.getElementById('footer-grand-total');

    // エラーチェック: 要素が見つからないときは何もしない
    if (!paymentMethodEl || !prefectureEl || !shippingFeeEl || !codFeeEl || !grandTotalEl || !footerGrandTotalEl) {
        console.error('必要な要素が見つからないよ！');
        return;
    }

    const paymentMethod = paymentMethodEl.value;
    const prefecture = prefectureEl.value || '';
    let shippingFee = null; // 最初は「まだわからないよ」
    let codFee = 0;

    // PHPの最初の表示をチェック
    const initialShippingText = shippingFeeEl.textContent.trim();

    // 支払い方法がまだ選ばれてないときは、PHPの表示を信じる
    if (!paymentMethod && initialShippingText === '別途送料を承ります') {
        shippingFee = null;
    } else if (paymentMethod === 'cash_on_delivery') {
        shippingFee = config.cash_on_delivery.base_fee; // 770円
        codFee = config.cash_on_delivery.cod_fee; // 330円
        if (totalPrice >= config.cash_on_delivery.free_threshold) { // 11,000円以上
            shippingFee = 0;
            codFee = 0;
        }
    } else if (paymentMethod === 'bank_transfer') {
        shippingFee = config.bank_transfer.remote_areas.includes(prefecture) 
            ? config.bank_transfer.remote_fee // 1,100円
            : config.bank_transfer.base_fee; // 770円
        if (totalPrice >= config.bank_transfer.free_threshold) { // 11,000円以上
            shippingFee = 0;
        }
    } else {
        if (totalPrice >= config.bank_transfer.free_threshold) {
            shippingFee = 0;
        } else {
            shippingFee = null;
        }
    }

    const grandTotal = shippingFee === null ? totalPrice : totalPrice + shippingFee + codFee;

    shippingFeeEl.textContent = shippingFee === 0 ? '無料' : (shippingFee === null ? '別途送料を承ります' : `${shippingFee.toLocaleString()}${currency}`);
    codFeeEl.textContent = codFee === 0 ? (paymentMethod === 'cash_on_delivery' ? '無料' : '-') : `${codFee.toLocaleString()}${currency}`;
    grandTotalEl.textContent = shippingFee === null ? `${totalPrice.toLocaleString()}${currency} + 送料` : `${grandTotal.toLocaleString()}${currency}`;
    footerGrandTotalEl.textContent = shippingFee === null ? `${totalPrice.toLocaleString()} + 送料` : `${grandTotal.toLocaleString()}`;
}

// 郵便番号の自動入力（元のまま）
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
                    updateCartSummary();
                } else {
                    alert('住所が見つかりませんでした。');
                }
            })
            .catch(error => console.error('Error:', error));
    }
});

// 入力制限（元のまま）
document.getElementById('postal_code').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '').slice(0, 7);
});

document.getElementById('phone').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
});

document.getElementById('email').addEventListener('input', function() {
    this.value = this.value.replace(/[^a-zA-Z0-9._%+-@]/g, '');
});

// 支払い方法や都道府県が変わったとき（元のまま）
document.getElementById('payment_method').addEventListener('change', updateCartSummary);
document.getElementById('prefecture').addEventListener('change', updateCartSummary);

// 最初に更新（これでフッター用と競合しない）
setTimeout(updateCartSummary, 0); // すぐ動くけど、タイミングをずらす
</script>

    <?php
    return ob_get_clean();
}

function render_cart_summary($cart_items, $products, $customer_info = [], $show_remove_button = false) {
    global $config;
    $base_path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/';
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
    $grand_total = $shipping_fee === null ? $total_price : $total_price + $shipping_fee + $cod_fee;

    ob_start();
    ?>
    <h4 class="mb-3">カート内容</h4>
    <ul class="list-group mb-4">
    <?php if (empty($cart_items) || !is_array($cart_items)): ?>
        <li class="list-group-item">カートに商品がありません。</li>
    <?php else: ?>
        <?php foreach ($cart_items as $index => $item): ?>
            <?php
            $product = $products[$item['product_id']] ?? null;
            if ($product && isset($product['variants'][$item['variant']])):
                $price = $product['variants'][$item['variant']]['price'] ?? 0;
                $subtotal = $price * $item['quantity'];
                $variant_key = $item['variant'];
                $attributes = $product['attributes'] ?? [];
                $variant_display = [];
                foreach ($attributes as $attr_name => $attr_values) {
                    $variant_parts = explode('-', $variant_key);
                    foreach ($variant_parts as $part) {
                        if (in_array($part, $attr_values)) {
                            $variant_display[] = htmlspecialchars($attr_name, ENT_QUOTES, 'UTF-8') . ': ' . htmlspecialchars($part, ENT_QUOTES, 'UTF-8');
                            break;
                        }
                    }
                }
                if (empty($variant_display)) {
                    $variant_display[] = htmlspecialchars(key($attributes), ENT_QUOTES, 'UTF-8') . ': ' . htmlspecialchars($variant_key, ENT_QUOTES, 'UTF-8');
                }
                $variant_image = get_variant_image($product, $item['variant']);
                $image_src = (strpos($variant_image, 'http') === 0) ? $variant_image : $base_path . $variant_image;
            ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <img src="<?php echo htmlspecialchars($image_src, ENT_QUOTES, 'UTF-8'); ?>" 
                         alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>" 
                         class="img-thumbnail me-2" style="width: 50px; height: 50px;">
                    <div>
                        <h6 class="my-0"><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h6>
                        <small class="text-muted">
                            <?php echo implode('<br>', $variant_display); ?>
                            <br>数量: <?php echo $item['quantity']; ?>
                        </small>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <span class="text-muted me-3"><?php echo number_format($subtotal); ?><?php echo $config['currency']; ?></span>
                    <?php if ($show_remove_button): ?>
                        <a href="cart.php?action=remove&index=<?php echo $index; ?>" class="btn btn-sm btn-danger">削除</a>
                    <?php endif; ?>
                </div>
            </li>
            <?php endif; ?>
        <?php endforeach; ?>
        <li class="list-group-item d-flex justify-content-between">
            <span>商品合計</span>
            <span><?php echo number_format($total_price); ?><?php echo $config['currency']; ?></span>
        </li>
        <li class="list-group-item d-flex justify-content-between">
            <span>送料</span>
            <span id="shipping-fee">
                <?php 
                echo $shipping_fee === 0 ? '無料' : ($shipping_fee === null ? '別途送料を承ります' : number_format($shipping_fee) . $config['currency']); 
                ?>
            </span>
        </li>
        <li class="list-group-item d-flex justify-content-between">
            <span>代引き手数料</span>
            <span id="cod-fee"><?php echo $cod_fee === 0 ? '-' : number_format($cod_fee) . $config['currency']; ?></span>
        </li>
        <li class="list-group-item d-flex justify-content-between">
            <strong>総合計</strong>
            <strong id="grand-total">
                <?php echo $shipping_fee === null ? number_format($total_price) . $config['currency'] . ' + 送料' : number_format($grand_total) . $config['currency']; ?>
            </strong>
        </li>
    <?php endif; ?>
    </ul>
    <?php
    $html = ob_get_clean();
    return ['html' => $html, 'grand_total' => $grand_total];
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
        'payment_method' => $post_data['payment_method'] ?? '',
        'delivery_time' => $post_data['delivery_time'] ?? '',
        'catalog_request' => isset($post_data['catalog_request']) ? 1 : 0
    ];
    return $customer_info;
}

function display_customer_info($customer_info) {
    global $config;
    $payment_methods = $config['payment_methods'];
    $delivery_times = [
        'morning' => '午前中',
        '14-16' => '14:00-16:00',
        '16-18' => '16:00-18:00',
        '18-20' => '18:00-20:00'
    ];
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
        <li class="list-group-item">配送日時: <?php echo htmlspecialchars($delivery_times[$customer_info['delivery_time']] ?? '未指定', ENT_QUOTES, 'UTF-8'); ?></li>
        <li class="list-group-item">カタログ冊子: <?php echo $customer_info['catalog_request'] ? '希望する' : '希望しない'; ?></li>
    </ul>
    <?php
    return ob_get_clean();
}

function calculate_shipping_and_fees($total_price, $customer_info = []) {
    global $config;
    $shipping_fee = null; // nullで「未確定」を表す
    $cod_fee = 0;
    $payment_method = $customer_info['payment_method'] ?? '';
    $prefecture = $customer_info['prefecture'] ?? '';

    if ($payment_method === 'cash_on_delivery') {
        $shipping_fee = $config['shipping']['cash_on_delivery']['base_fee'];
        $cod_fee = $config['shipping']['cash_on_delivery']['cod_fee'];
        if ($total_price >= $config['shipping']['cash_on_delivery']['free_threshold']) {
            $shipping_fee = 0;
            $cod_fee = 0;
        }
    } elseif ($payment_method === 'bank_transfer') {
        $shipping_fee = in_array($prefecture, $config['shipping']['bank_transfer']['remote_areas']) 
            ? $config['shipping']['bank_transfer']['remote_fee'] 
            : $config['shipping']['bank_transfer']['base_fee'];
        if ($total_price >= $config['shipping']['bank_transfer']['free_threshold']) {
            $shipping_fee = 0;
        }
    } else {
        // 支払い方法未選択時
        if ($total_price >= $config['shipping']['bank_transfer']['free_threshold']) {
            $shipping_fee = 0; // 無料条件を満たせば無料
        } else {
            $shipping_fee = null; // それ以外は未確定
        }
    }

    return ['shipping_fee' => $shipping_fee, 'cod_fee' => $cod_fee];
}

function send_order_confirmation_email($order_data) {
    global $config;
    if (!$config['email']['send_to_customer'] && !$config['email']['send_to_admin']) {
        return;
    }

    $delivery_times = [
        'morning' => '午前中',
        '14-16' => '14:00-16:00',
        '16-18' => '16:00-18:00',
        '18-20' => '18:00-20:00'
    ];

    $subject = str_replace('{ORDER_NUMBER}', $order_data['order_id'], $config['email']['subject']);
    $message = "ご注文ありがとうございます。\n\n";
    $message .= "注文番号: " . $order_data['order_id'] . "\n";
    $message .= "注文日時: " . $order_data['order_date'] . "\n";
    $message .= "お届け先: " . $order_data['customer_name'] . " 様\n";
    $message .= $order_data['postal_code'] . "\n";
    $message .= $order_data['prefecture'] . $order_data['address'] . "\n";
    $message .= $order_data['building'] . "\n";
    $message .= "電話番号: " . $order_data['phone'] . "\n";
    $message .= "Email: " . $order_data['email'] . "\n";
    $message .= "通信欄: " . $order_data['comments'] . "\n";
    $message .= "支払い方法: " . $config['payment_methods'][$order_data['payment_method']] . "\n";
    $message .= "配送日時: " . ($delivery_times[$order_data['delivery_time']] ?? '未指定') . "\n";
    $message .= "カタログ冊子: " . ($order_data['catalog_request'] ? '希望する' : '希望しない') . "\n";
    $message .= "商品合計: " . number_format($order_data['total_price']) . $config['currency'] . "\n";
    $message .= "送料: " . number_format($order_data['shipping_fee']) . $config['currency'] . "\n";
    $message .= "代引き手数料: " . number_format($order_data['cod_fee']) . $config['currency'] . "\n";
    $message .= "総合計: " . number_format($order_data['grand_total']) . $config['currency'] . "\n\n";
    $message .= "配送予定日: " . $order_data['delivery_date'] . "\n\n";
    $message .= "注文内容:\n";
    foreach ($order_data['items'] as $item) {
        $variant_parts = explode('-', $item['variant']);
        $size = $variant_parts[0] ?? '';
        $color = $variant_parts[1] ?? '';
        $message .= "- " . $item['product_name'] . " (サイズ: " . $size . ", 色: " . $color . ") x " . $item['quantity'] . " = " . number_format($item['price'] * $item['quantity']) . $config['currency'] . "\n";
    }

    $headers = "From: " . $config['email']['from_email'] . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    if ($config['email']['send_to_customer']) {
        mail($order_data['email'], $subject, $message, $headers);
    }
    if ($config['email']['send_to_admin']) {
        mail($config['email']['admin_email'], $subject, $message, $headers);
    }
}
?>